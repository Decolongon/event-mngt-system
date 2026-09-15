<?php

use App\Concerns\HasUnset;
use App\Enums\EventStatus;
use App\Enums\PaymentMethodEnum;
use App\Livewire\Forms\BookTicketForm;
use App\Models\Event;
use App\Models\TicketType;
use App\Services\BookTicketService;
use App\Services\PaymentIntentService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Book Ticket')] #[Layout('layouts.app.attendee')] class extends Component
{
    use HasUnset;

    public BookTicketForm $form;

    public ?int $selectedEventId = null;

    public ?int $selectedTicketTypeId = null;

    public string $payment_method = 'card';

    public string $card_number = '';

    public string $exp_month = '';

    public string $exp_year = '';

    public string $cvc = '';

    public ?string $paymentRedirectUrl = null;

    protected PaymentIntentService $service;

    protected BookTicketService $book_ticket_service;

    public function boot(PaymentIntentService $service, BookTicketService $bookTicketService)
    {
        $this->service = $service;
        $this->book_ticket_service = $bookTicketService;
    }

    #[Computed]
    public function events(): Collection
    {
        return $this->book_ticket_service->getEvents();
    }

    #[Computed]
    public function getTicketTypes(Event $event): Collection
    {
        return $this->book_ticket_service->getTicketTypes($event);
    }

    #[Computed]
    public function selectedEvent(): ?Event
    {
        if ($this->selectedEventId === null) {
            return null;
        }

        return Event::find($this->selectedEventId);
    }

    #[Computed]
    public function selectedTicketType(): ?TicketType
    {
        if ($this->selectedTicketTypeId === null) {
            return null;
        }

        return TicketType::find($this->selectedTicketTypeId);
    }

    public function selectEvent(int $eventId): void
    {
        $event = Event::where('status', EventStatus::Published->value)->findOrFail($eventId);

        $this->selectedEventId = $event->id;
        $this->selectedTicketTypeId = null;
        $this->form->quantity = 1;

        // Bust computed caches - computed properties are memoized per-request
        $this->unsetAttributes(['selectedEvent', 'ticketTypes', 'selectedTicketType']);
    }

    public function selectTicketType(int $ticketTypeId): void
    {
        $ticketType = TicketType::query()->where('remaining_capacity', '>', 0)->where('sales_start', '<=', now())->where('sales_end', '>=', now())->where('event_id', $this->selectedEventId)->findOrFail($ticketTypeId);

        $this->selectedTicketTypeId = $ticketType->id;
        $this->unsetAttributes(['selectedTicketType']);

        // reset quantity if exceeds capacity or per-person limit (10)
        $max = min(10, $ticketType->remaining_capacity);
        if ($this->form->quantity > $max) {
            $this->form->quantity = $max;
        }

        if ($this->form->quantity < 1) {
            $this->form->quantity = 1;
        }
    }

    public function selectPaymentMethod(string $method): void
    {
        if (! in_array($method, PaymentMethodEnum::values(), true)) {
            return;
        }

        $this->payment_method = $method;
    }

    public function cancelSelection(): void
    {
        $this->form->quantity = 1;
        $this->form->reset('event_id', 'ticket_type_id');
        $this->payment_method = PaymentMethodEnum::CARD->value;
        $this->reset('card_number', 'exp_month', 'exp_year', 'cvc', 'paymentRedirectUrl', 'selectedEventId', 'selectedTicketTypeId');
        $this->unsetAttributes(['selectedEvent', 'ticketTypes', 'selectedTicketType']);
    }

    public function updatedFormQuantity(): void
    {
        if ($this->form->quantity < 1) {
            $this->form->quantity = 1;
        }

        if ($this->selectedTicketType) {
            $max = min(10, $this->selectedTicketType->remaining_capacity);
            if ($this->form->quantity > $max) {
                $this->form->quantity = $max;
            }
        } elseif ($this->form->quantity > 10) {
            $this->form->quantity = 10;
        }
    }

    public function addQuantity(): void
    {
        if ($this->selectedTicketTypeId === null || ! $this->selectedTicketType) {
            return;
        }

        $max = min(10, $this->selectedTicketType->remaining_capacity);

        if ($this->form->quantity < $max) {
            $this->form->quantity++;
        }
    }

    public function subtractQuantity(): void
    {
        if ($this->form->quantity > 1) {
            $this->form->quantity--;
        }
    }

    // alias for previous typo - keep backwards compatible
    public function addQuity(): void
    {
        $this->addQuantity();
    }

    public function bookTicket(): void
    {
        $rules = [
            'selectedEventId' => 'required|exists:events,id',
            'selectedTicketTypeId' => 'required|exists:ticket_types,id',
            'form.quantity' => 'required|integer|min:1|max:10',
            'payment_method' => 'required|in:card,gcash,paymaya,grabpay',
        ];

        if ($this->payment_method === PaymentMethodEnum::CARD->value) {
            $rules['card_number'] = ['required', 'string', 'regex:/^[0-9\s]+$/', 'min:13', 'max:19'];
            $rules['exp_month'] = 'required|integer|min:1|max:12';
            $rules['exp_year'] = 'required|integer|min:'.now()->year.'|max:'.(now()->year + 20);
            $rules['cvc'] = 'required|string|digits_between:3,4';
        }

        $this->validate($rules);

        // Enforce per-person (10) and remaining capacity limit
        if ($this->selectedTicketType && $this->form->quantity > min(10, $this->selectedTicketType->remaining_capacity)) {
            $max = min(10, $this->selectedTicketType->remaining_capacity);
            $this->addError('form.quantity', __('You can only book up to :max tickets. Only :remaining remaining.', ['max' => $max, 'remaining' => $this->selectedTicketType->remaining_capacity]));

            return;
        }

        $this->form->event_id = $this->selectedEventId;
        $this->form->ticket_type_id = $this->selectedTicketTypeId;

        // Transaction: booking + payment + PayMongo intent
        DB::beginTransaction();

        try {
            $booking = $this->form->store();
            $total = (float) $booking->total_price;

            $status = 'successful';
            $redirectUrl = null;

            // Use PaymentIntentService if keys are configured, otherwise simulate
            if (empty(config('paymongo.secret_key'))) {
                Log::warning('PayMongo secret_key not set – simulating successful payment', ['booking_id' => $booking->id]);
            } else {
                try {
                    $cardDetails = [];
                    if ($this->payment_method === PaymentMethodEnum::CARD->value) {
                        $cardDetails = [
                            'card_number' => preg_replace('/\s+/', '', $this->card_number),
                            'exp_month' => $this->exp_month,
                            'exp_year' => $this->exp_year,
                            'cvc' => $this->cvc,
                        ];
                    }

                    $returnUrl = route('attendee.dashboard');

                    $attached = $this->service->createAndAttach($total, $this->payment_method, $cardDetails, $returnUrl);

                    // Extract status and next_action correctly (BaseModel flattens attributes)
                    $all = $attached->getAttributes();
                    $intentStatus = $all['status'] ?? ($attached->status ?? null);
                    $nextAction = $all['next_action'] ?? null;

                    if (is_array($nextAction) && isset($nextAction['redirect']['url'])) {
                        $redirectUrl = $nextAction['redirect']['url'];
                    } elseif (is_object($nextAction) && isset($nextAction->redirect->url)) {
                        $redirectUrl = $nextAction->redirect->url;
                    }

                    if (in_array($intentStatus, ['succeeded', 'awaiting_next_action', 'awaiting_payment_method'], true)) {
                        $status = 'successful';
                        $this->paymentRedirectUrl = $redirectUrl;
                    } else {
                        $status = 'failed';
                    }
                } catch (\Throwable $e) {
                    Log::error('PayMongo payment failed', ['error' => $e->getMessage(), 'booking_id' => $booking->id]);
                    $status = 'failed';
                    // Save failed payment then rollback booking? Keep failed payment for audit
                    $booking->payment()->create([
                        'payment_method' => $this->payment_method,
                        'amount' => $total,
                        'status' => $status,
                    ]);
                    DB::commit();

                    Flux::toast(heading: __('Payment failed'), text: $e->getMessage(), variant: 'danger');

                    return;
                }
            }

            $booking->payment()->create([
                'payment_method' => $this->payment_method,
                'amount' => $total,
                'status' => $status,
            ]);

            if ($status === 'successful') {
                $booking->update(['status' => 'confirmed']);
            }

            DB::commit();

            if ($status === 'failed') {
                Flux::toast(heading: __('Payment failed'), text: __('Your booking was created but payment failed.'), variant: 'danger');

                return;
            }

            $message = __('Your ticket for :event has been booked successfully.', ['event' => $this->selectedEvent?->title]);
            if ($redirectUrl) {
                $message .= ' '.__('Complete your payment via the redirect link.');
            }

            Flux::toast(heading: __('Booking confirmed'), text: $message, variant: 'success');

            if ($this->paymentRedirectUrl) {
                Flux::modal('payment-redirect')->show();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Booking transaction failed', ['error' => $e->getMessage()]);
            Flux::toast(heading: __('Booking failed'), text: $e->getMessage(), variant: 'danger');

            return;
        }

        $this->form->reset();
        $this->payment_method = PaymentMethodEnum::CARD->value;
        $this->reset('card_number', 'exp_month', 'exp_year', 'cvc', 'selectedEventId', 'selectedTicketTypeId');
        $this->unsetAttributes(['events', 'selectedEvent', 'ticketTypes', 'selectedTicketType']);
    }
};
?>

<div class="flex w-full flex-col gap-8">
    {{-- Header --}}
    <section class="w-full">
        <div class="mb-1">
            <flux:heading size="xl" level="1">{{ __('Book Tickets') }}</flux:heading>
            <flux:subheading>{{ __('Discover published events and secure your tickets instantly.') }}</flux:subheading>
        </div>
    </section>

    {{-- Events Grid --}}
    <section class="w-full">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading>{{ __('Available Events') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->events->count() }} {{ Str::plural('event', $this->events->count()) }}</flux:text>
        </div>

        @if ($this->events->isEmpty())
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50/50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
                <div class="mx-auto flex size-10 items-center justify-center rounded-full border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:icon.calendar class="size-5 text-zinc-400" />
                </div>
                <flux:heading size="sm" class="mt-3">{{ __('No events available') }}</flux:heading>
                <flux:text class="mt-1 text-sm">
                    {{ __('There are no published events at the moment. Please check back later.') }}</flux:text>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->events as $event)
                    @php
                        $isSelected = $this->selectedEventId === $event->id;
                        $statusVal = $event->status instanceof \BackedEnum ? $event->status->value : $event->status;
                    @endphp
                    <livewire:book-ticket-item
                        :event="$event"
                        :isSelected="$isSelected"
                        :statusVal="$statusVal"
                        :wire:key="'event-card-'.$event->id"
                    >
                        <livewire:slot name="select-event">
                            <flux:button
                                wire:click="selectEvent({{ $event->id }})"
                                variant="{{ $isSelected ? 'primary' : 'ghost' }}"
                                size="sm"
                                iconTrailing="{{ $isSelected ? 'check' : 'ticket' }}"
                                class="shrink-0"
                            >
                                {{ $isSelected ? __('Selected') : __('Get Ticket Now') }}
                            </flux:button>
                        </livewire:slot>
                    </livewire:book-ticket-item>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Ticket Selection --}}
    @if ($this->selectedEvent)
        @php $selectedEvent = $this->selectedEvent; @endphp
        <section class="w-full scroll-mt-4" wire:key="ticket-selection-{{ $selectedEvent->id }}">
            <flux:card class="border-zinc-200 shadow-sm dark:border-zinc-700">
                {{-- Selected Event Header --}}
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex min-w-0 flex-1 gap-4">
                        @if ($selectedEvent->banner_image)
                            <img
                                src="{{ Storage::url($selectedEvent->banner_image) }}"
                                alt="{{ $selectedEvent->title }}"
                                class="hidden h-16 w-16 shrink-0 rounded-lg object-cover sm:block"
                            />
                        @endif
                        <div class="min-w-0">
                            <flux:heading size="lg">{{ $selectedEvent->title }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                <span class="inline-flex items-center gap-1"
                                    ><flux:icon.map-pin
                                        variant="micro"
                                        class="size-3.5"
                                    />{{ $selectedEvent->location }}</span>
                                <span class="mx-1.5 text-zinc-300 dark:text-zinc-600">·</span>
                                <span class="inline-flex items-center gap-1">
                                    <flux:icon.calendar variant="micro" class="size-3.5" />
                                    {{ $selectedEvent->start_time?->format('M j, Y g:i A') }}
                                </span>
                            </flux:text>
                            @if ($selectedEvent->description)
                                <flux:text class="mt-2 line-clamp-2 text-sm">{{ $selectedEvent->description }}</flux:text>
                            @endif
                        </div>
                    </div>
                    <flux:button wire:click="cancelSelection" variant="ghost" size="sm" icon="x-mark">
                        {{ __('Change Event') }}</flux:button>
                </div>

                <flux:separator class="my-6" />

                @php $ticketTypes = $this->getTicketTypes($selectedEvent); @endphp

                <div>
                    <div class="mb-4 flex items-center justify-between">
                        <flux:heading size="sm">{{ __('Choose Your Ticket') }}</flux:heading>
                        <flux:text class="text-xs text-zinc-500">{{ $ticketTypes->count() }} {{ __('available') }}</flux:text>
                    </div>

                    @if ($ticketTypes->isEmpty())
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center dark:border-amber-900/50 dark:bg-amber-950/30">
                            <flux:icon.exclamation-triangle class="mx-auto size-6 text-amber-500" />
                            <flux:heading size="sm" class="mt-2">{{ __('No tickets available') }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('There are no tickets on sale for this event right now, or they are sold out.') }}
                            </flux:text>
                        </div>
                    @else
                        <div class="grid gap-3">
                            @foreach ($ticketTypes as $ticketType)
                                @php $isTicketSelected = $this->selectedTicketTypeId === $ticketType->id; @endphp
                                <button
                                    type="button"
                                    wire:click="selectTicketType({{ $ticketType->id }})"
                                    wire:key="tt-{{ $ticketType->id }}"
                                    class="w-full rounded-xl border p-4 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 {{ $isTicketSelected ? 'border-violet-500 bg-violet-50/70 ring-1 ring-violet-500/20 dark:border-violet-600 dark:bg-violet-950/20' : 'border-zinc-200 bg-white hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600 dark:hover:bg-zinc-800' }}"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="flex size-5 shrink-0 items-center justify-center rounded-full border {{ $isTicketSelected ? 'border-violet-600 bg-violet-600 text-white' : 'border-zinc-300 bg-white dark:border-zinc-600 dark:bg-zinc-800' }}">
                                                    @if ($isTicketSelected)
                                                        <flux:icon.check variant="micro" class="size-3" />
                                                    @endif
                                                </span>
                                                <p class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                                    {{ $ticketType->name }}
                                                </p>
                                                @if ($isTicketSelected)
                                                    <flux:badge color="violet" size="sm"
                                                        >{{ __('Selected') }}
                                                    </flux:badge>
                                                @endif
                                            </div>
                                            <div class="mt-1.5 ml-7 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                <span class="inline-flex items-center gap-1 font-medium text-zinc-900 dark:text-zinc-100">
                                                    <flux:icon.currency-dollar variant="micro" class="size-3.5" />
                                                    {{ number_format($ticketType->price, 2) }}
                                                </span>
                                                <span class="hidden text-zinc-300 sm:inline dark:text-zinc-600">·</span>
                                                <span class="inline-flex items-center gap-1">
                                                    <flux:icon.users variant="micro" class="size-3.5" />
                                                    {{ __(':count remaining', ['count' => $ticketType->remaining_capacity]) }} / {{ $ticketType->capacity }}
                                                </span>
                                                <span class="hidden text-zinc-300 sm:inline dark:text-zinc-600">·</span>
                                                <span class="inline-flex items-center gap-1">
                                                    <flux:icon.clock variant="micro" class="size-3.5" />
                                                    {{ $ticketType->sales_start?->format('M j, g:i A') }} – {{ $ticketType->sales_end?->format('M j, g:i A') }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                                ₱{{ number_format($ticketType->price, 2) }}
                                            </p>
                                            <p class="text-xs text-zinc-500">per ticket</p>
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>

                        {{-- Payment Method --}}
                        <div class="mt-6 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                            <flux:heading size="sm">{{ __('Payment Method') }}</flux:heading>
                            <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Choose how you want to pay. Card shows extra fields.') }}</flux:text>

                            <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                                @php
                                    $methods = [
                                        'card' => ['label' => 'Card', 'icon' => 'credit-card', 'desc' => 'Visa / MC'],
                                        'gcash' => [
                                            'label' => 'GCash',
                                            'icon' => 'device-phone-mobile',
                                            'desc' => 'E-wallet',
                                        ],
                                        'paymaya' => ['label' => 'PayMaya', 'icon' => 'wallet', 'desc' => 'Maya'],
                                        'grabpay' => ['label' => 'GrabPay', 'icon' => 'banknotes', 'desc' => 'Grab'],
                                    ];
                                @endphp
                                @foreach ($methods as $value => $meta)
                                    @php $isActive = $this->payment_method === $value; @endphp
                                    <button
                                        type="button"
                                        wire:click="selectPaymentMethod('{{ $value }}')"
                                        class="flex flex-col items-center justify-center gap-1.5 rounded-xl border p-3 sm:p-4 text-center transition focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 {{ $isActive ? 'border-violet-500 bg-violet-50 ring-1 ring-violet-500/20 dark:border-violet-600 dark:bg-violet-950/30' : 'border-zinc-200 bg-white hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600' }}"
                                    >
                                        <span class="flex size-8 items-center justify-center rounded-full {{ $isActive ? 'bg-violet-600 text-white' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                            <flux:icon :name="$meta['icon']" variant="outline" class="size-4" />
                                        </span>
                                        <span class="text-xs font-semibold sm:text-sm {{ $isActive ? 'text-violet-700 dark:text-violet-300' : 'text-zinc-900 dark:text-zinc-100' }}">{{ $meta['label'] }}</span>
                                        <span class="hidden text-[11px] text-zinc-500 sm:block dark:text-zinc-400">{{ $meta['desc'] }}</span>
                                        @if ($isActive)
                                            <flux:badge
                                                color="violet"
                                                size="sm"
                                                class="mt-0.5 hidden sm:inline-flex"
                                            >{{ __('Selected') }}</flux:badge>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                            <flux:error name="payment_method" />

                            {{-- Card fields - shown only when card is selected --}}
                            @if ($this->payment_method === 'card')
                                <div class="mt-5 rounded-lg border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                                    <div class="mb-3 flex items-center gap-2">
                                        <flux:icon.credit-card class="size-4 text-zinc-500" />
                                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Card Details') }}</span>
                                    </div>
                                    <div class="grid gap-4">
                                        <flux:field>
                                            <flux:label badge="{{ __('Required') }}">{{ __('Card Number') }}</flux:label>
                                            <flux:input
                                                wire:model="card_number"
                                                inputmode="numeric"
                                                autocomplete="cc-number"
                                                placeholder="4242 4242 4242 4242"
                                                maxlength="19"
                                            />
                                            <flux:error name="card_number" />
                                        </flux:field>
                                        <div class="grid grid-cols-3 gap-3 sm:gap-4">
                                            <flux:field>
                                                <flux:label badge="{{ __('Required') }}">{{ __('Exp. Month') }}</flux:label>
                                                <flux:input
                                                    wire:model="exp_month"
                                                    type="number"
                                                    inputmode="numeric"
                                                    placeholder="12"
                                                    min="1"
                                                    max="12"
                                                />
                                                <flux:error name="exp_month" />
                                            </flux:field>
                                            <flux:field>
                                                <flux:label badge="{{ __('Required') }}">{{ __('Exp. Year') }}</flux:label>
                                                <flux:input
                                                    wire:model="exp_year"
                                                    type="number"
                                                    inputmode="numeric"
                                                    placeholder="{{ now()->year + 1 }}"
                                                    min="{{ now()->year }}"
                                                    max="{{ now()->year + 20 }}"
                                                />
                                                <flux:error name="exp_year" />
                                            </flux:field>
                                            <flux:field>
                                                <flux:label badge="{{ __('Required') }}">{{ __('CVC') }} </flux:label>
                                                <flux:input
                                                    wire:model="cvc"
                                                    type="text"
                                                    inputmode="numeric"
                                                    autocomplete="cc-csc"
                                                    placeholder="123"
                                                    maxlength="4"
                                                />
                                                <flux:error name="cvc" />
                                            </flux:field>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="mt-4 rounded-lg border border-dashed border-zinc-200 bg-zinc-50/50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900/30">
                                    <flux:text class="text-xs text-zinc-600 dark:text-zinc-400">
                                        @if ($this->payment_method === 'gcash')
                                            {{ __('You will be redirected to GCash to complete the payment.') }}
                                        @elseif ($this->payment_method === 'paymaya')
                                            {{ __('You will be redirected to PayMaya (Maya) to complete the payment.') }}
                                        @elseif ($this->payment_method === 'grabpay')
                                            {{ __('You will be redirected to GrabPay to complete the payment.') }}
                                        @endif
                                    </flux:text>
                                </div>
                            @endif
                        </div>

                        {{-- Quantity & Summary --}}
                        <div class="mt-6 rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                            <div class="grid gap-6 sm:grid-cols-2 sm:items-end">
                                <flux:field>
                                    <flux:label badge="{{ __('Required') }}">{{ __('Quantity') }}</flux:label>
                                    @php $maxPerPerson = $this->selectedTicketType ? min(10, $this->selectedTicketType->remaining_capacity) : 10; @endphp
                                    <div class="flex items-center gap-3">
                                        <flux:button
                                            wire:click="subtractQuantity"
                                            variant="ghost"
                                            size="sm"
                                            icon="minus"
                                            :disabled="$this->selectedTicketTypeId === null || $this->form->quantity <= 1"
                                            aria-label="{{ __('Decrease quantity') }}"
                                            class="shrink-0"
                                        />
                                        <div class="flex h-9 min-w-[4rem] items-center justify-center rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 {{ $this->selectedTicketTypeId === null ? 'opacity-50' : '' }}">
                                            {{ $this->form->quantity }}
                                        </div>
                                        <flux:button
                                            wire:click="addQuantity"
                                            variant="ghost"
                                            size="sm"
                                            icon="plus"
                                            :disabled="$this->selectedTicketTypeId === null || $this->form->quantity >= $maxPerPerson"
                                            aria-label="{{ __('Increase quantity') }}"
                                            class="shrink-0"
                                        />
                                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                            / {{ $maxPerPerson }} {{ __('max') }}
                                        </flux:text>
                                    </div>
                                    <flux:description>
                                        @if ($this->selectedTicketType)
                                            {{ __(':remaining remaining • Max 10 tickets per person.', ['remaining' => $this->selectedTicketType->remaining_capacity]) }}
                                        @else
                                            {{ __('Select a ticket type first.') }}
                                        @endif
                                    </flux:description>
                                    <flux:error name="form.quantity" />
                                    <flux:error name="selectedTicketTypeId" />
                                    <flux:error name="selectedEventId" />
                                </flux:field>

                                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Ticket Price') }}</span>
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                            @if ($this->selectedTicketType)
                                                ₱{{ number_format($this->selectedTicketType->price, 2) }}
                                            @else
                                                —
                                            @endif
                                        </span>
                                    </div>
                                    <div class="mt-1 flex items-center justify-between text-sm">
                                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Quantity') }}</span>
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">× {{ $this->form->quantity }}</span>
                                    </div>
                                    <flux:separator class="my-3" />
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Total') }}</span>
                                        <span class="text-lg font-bold text-violet-600 dark:text-violet-400">
                                            @if ($this->selectedTicketType)
                                                ₱{{ number_format($this->selectedTicketType->price * $this->form->quantity, 2) }}
                                            @else
                                                ₱0.00
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <flux:button wire:click="cancelSelection" variant="ghost"
                                    >{{ __('Cancel') }}
                                </flux:button>
                                <flux:button
                                    wire:click="bookTicket"
                                    variant="primary"
                                    icon="ticket"
                                    :disabled="$this->selectedTicketTypeId === null"
                                    wire:loading.attr="disabled"
                                >
                                    <span
                                        wire:loading.remove
                                        wire:target="bookTicket"
                                    >{{ __('Confirm Booking') }}</span>
                                    <span wire:loading wire:target="bookTicket">{{ __('Booking…') }}</span>
                                </flux:button>
                            </div>

                            @error('form.event_id')
                                <flux:text color="red" size="sm" class="mt-2">{{ $message }}</flux:text>
                            @enderror
                            @error('form.ticket_type_id')
                                <flux:text color="red" size="sm" class="mt-2">{{ $message }}</flux:text>
                            @enderror
                            @error('form.quantity')
                                <flux:text color="red" size="sm" class="mt-2">{{ $message }}</flux:text>
                            @enderror
                        </div>

                    @endif
                </div>
            </flux:card>
        </section>
    @endif

    {{-- Payment Redirect Modal --}}
    <flux:modal name="payment-redirect" class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="flex items-center gap-2">
                    <flux:icon.arrow-top-right-on-square class="size-5 text-violet-600" />
                    {{ __('Complete your payment') }}
                </flux:heading>
                <flux:subheading
                    >{{ __('Your booking was confirmed. Complete your payment via the link below.') }}
                </flux:subheading>
            </div>

            @if ($this->paymentRedirectUrl)
                <div class="rounded-xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-800 dark:bg-violet-950/30">
                    <flux:text class="text-sm break-all text-violet-700 dark:text-violet-300">
                        {{ $this->paymentRedirectUrl }}</flux:text>
                    <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('Close') }}</flux:button>
                        </flux:modal.close>
                        <flux:button
                            :href="$this->paymentRedirectUrl"
                            target="_blank"
                            variant="primary"
                            iconTrailing="arrow-top-right-on-square"
                        >{{ __('Open Payment Link') }}</flux:button>
                    </div>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
