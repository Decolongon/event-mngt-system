<?php

use App\Enums\EventStatus;
use App\Livewire\Forms\BookTicketForm;
use App\Models\Event;
use App\Models\TicketType;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Book Ticket')] #[Layout('layouts.app.attendee')] class extends Component
{
    public BookTicketForm $form;

    public ?int $selectedEventId = null;

    public ?int $selectedTicketTypeId = null;

    #[Computed]
    public function events(): Collection
    {
        return Event::query()
            ->where('status', EventStatus::Published->value)
            ->latest()
            ->get();
    }

    #[Computed]
    public function getTicketTypes(Event $event): Collection
    {
        return TicketType::query()
            ->where('remaining_capacity', '>', 0)
            ->where('sales_start', '<=', now())
            ->where('sales_end', '>=', now())
            ->where('event_id', $event->id)
            ->get();
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
        unset($this->selectedEvent);
        unset($this->selectedTicketType);
        unset($this->ticketTypes);
    }

    public function selectTicketType(int $ticketTypeId): void
    {
        $ticketType = TicketType::query()
            ->where('remaining_capacity', '>', 0)
            ->where('sales_start', '<=', now())
            ->where('sales_end', '>=', now())
            ->where('event_id', $this->selectedEventId)
            ->findOrFail($ticketTypeId);

        $this->selectedTicketTypeId = $ticketType->id;
        unset($this->selectedTicketType);

        // reset quantity if exceeds capacity or per-person limit (10)
        $max = min(10, $ticketType->remaining_capacity);
        if ($this->form->quantity > $max) {
            $this->form->quantity = $max;
        }

        if ($this->form->quantity < 1) {
            $this->form->quantity = 1;
        }
    }

    public function cancelSelection(): void
    {
        $this->selectedEventId = null;
        $this->selectedTicketTypeId = null;
        $this->form->quantity = 1;
        $this->form->reset('event_id', 'ticket_type_id');

        unset($this->selectedEvent);
        unset($this->selectedTicketType);
        unset($this->ticketTypes);
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
        if ($this->selectedTicketTypeId === null || !$this->selectedTicketType) {
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
        $this->validate([
            'selectedEventId' => 'required|exists:events,id',
            'selectedTicketTypeId' => 'required|exists:ticket_types,id',
            'form.quantity' => 'required|integer|min:1|max:10',
        ]);

        // Enforce per-person (10) and remaining capacity limit
        if ($this->selectedTicketType && $this->form->quantity > min(10, $this->selectedTicketType->remaining_capacity)) {
            $max = min(10, $this->selectedTicketType->remaining_capacity);
            $this->addError('form.quantity', __('You can only book up to :max tickets. Only :remaining remaining.', ['max' => $max, 'remaining' => $this->selectedTicketType->remaining_capacity]));
            return;
        }

        $this->form->event_id = $this->selectedEventId;
        $this->form->ticket_type_id = $this->selectedTicketTypeId;

        $this->form->store();

        Flux::toast(
            heading: __('Booking confirmed'),
            text: __('Your ticket for :event has been booked successfully.', ['event' => $this->selectedEvent?->title]),
            variant: 'success',
        );

        $this->form->reset();
        $this->selectedEventId = null;
        $this->selectedTicketTypeId = null;

        unset($this->events);
        unset($this->selectedEvent);
        unset($this->selectedTicketType);
        unset($this->ticketTypes);
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
                <flux:text class="mt-1 text-sm">{{ __('There are no published events at the moment. Please check back later.') }}</flux:text>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->events as $event)
                    @php
                        $isSelected = $this->selectedEventId === $event->id;
                        $statusVal = $event->status instanceof \BackedEnum ? $event->status->value : $event->status;
                    @endphp
                    <div
                        wire:key="event-card-{{ $event->id }}"
                        class="group flex flex-col overflow-hidden rounded-xl border bg-white shadow-sm transition dark:bg-zinc-900 {{ $isSelected ? 'border-violet-500 ring-2 ring-violet-500/20 dark:border-violet-500' : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' }}"
                    >
                        {{-- Banner --}}
                        <div class="relative h-40 w-full overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                            @if ($event->banner_image)
                                <img
                                    src="{{ asset(Storage::url($event->banner_image)) }}"
                                    alt="{{ $event->title }}"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                                />
                            @else
                                <div class="flex h-full w-full items-center justify-center">
                                    <flux:icon.photo variant="outline" class="size-10 text-zinc-300 dark:text-zinc-600" />
                                </div>
                            @endif
                            <div class="absolute left-3 top-3 flex items-center gap-2">
                                <flux:badge color="green" size="sm">{{ ucfirst($statusVal) }}</flux:badge>
                            </div>
                            @if ($isSelected)
                                <div class="absolute right-3 top-3">
                                    <flux:badge color="violet" size="sm" icon="check-circle">{{ __('Selected') }}</flux:badge>
                                </div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex flex-1 flex-col p-4">
                            <h3 class="line-clamp-1 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $event->title }}</h3>

                            <div class="mt-1.5 flex flex-col gap-1 text-xs text-zinc-500 dark:text-zinc-400">
                                <span class="inline-flex items-center gap-1.5">
                                    <flux:icon.map-pin variant="micro" class="size-3.5 shrink-0" />
                                    <span class="truncate">{{ $event->location }}</span>
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <flux:icon.calendar variant="micro" class="size-3.5 shrink-0" />
                                    <span>{{ $event->start_time?->format('M j, Y g:i A') }} — {{ $event->end_time?->format('M j, Y') }}</span>
                                </span>
                            </div>

                            @if ($event->description)
                                <p class="mt-3 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $event->description }}</p>
                            @endif

                            <div class="mt-4 flex items-center justify-between gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                                <flux:text class="text-xs text-zinc-500">
                                    {{ $event->ticketTypes->count() }} {{ Str::plural('ticket type', $event->ticketTypes->count()) }}
                                </flux:text>
                                <flux:button
                                    wire:click="selectEvent({{ $event->id }})"
                                    variant="{{ $isSelected ? 'primary' : 'ghost' }}"
                                    size="sm"
                                    iconTrailing="{{ $isSelected ? 'check' : 'ticket' }}"
                                    class="shrink-0"
                                >
                                    {{ $isSelected ? __('Selected') : __('Get Ticket Now') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
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
                    <div class="flex gap-4 min-w-0 flex-1">
                        @if ($selectedEvent->banner_image)
                            <img src="{{ Storage::url($selectedEvent->banner_image) }}" alt="{{ $selectedEvent->title }}" class="hidden h-16 w-16 shrink-0 rounded-lg object-cover sm:block" />
                        @endif
                        <div class="min-w-0">
                            <flux:heading size="lg">{{ $selectedEvent->title }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                <span class="inline-flex items-center gap-1"><flux:icon.map-pin variant="micro" class="size-3.5" />{{ $selectedEvent->location }}</span>
                                <span class="mx-1.5 text-zinc-300 dark:text-zinc-600">·</span>
                                <span class="inline-flex items-center gap-1"><flux:icon.calendar variant="micro" class="size-3.5" />{{ $selectedEvent->start_time?->format('M j, Y g:i A') }}</span>
                            </flux:text>
                            @if($selectedEvent->description)
                                <flux:text class="mt-2 line-clamp-2 text-sm">{{ $selectedEvent->description }}</flux:text>
                            @endif
                        </div>
                    </div>
                    <flux:button wire:click="cancelSelection" variant="ghost" size="sm" icon="x-mark">{{ __('Change Event') }}</flux:button>
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
                            <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('There are no tickets on sale for this event right now, or they are sold out.') }}</flux:text>
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
                                                <span
                                                    class="flex size-5 shrink-0 items-center justify-center rounded-full border {{ $isTicketSelected ? 'border-violet-600 bg-violet-600 text-white' : 'border-zinc-300 bg-white dark:border-zinc-600 dark:bg-zinc-800' }}"
                                                >
                                                    @if ($isTicketSelected)
                                                        <flux:icon.check variant="micro" class="size-3" />
                                                    @endif
                                                </span>
                                                <p class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $ticketType->name }}</p>
                                                @if ($isTicketSelected)
                                                    <flux:badge color="violet" size="sm">{{ __('Selected') }}</flux:badge>
                                                @endif
                                            </div>
                                            <div class="ml-7 mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
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
                                            <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100">₱{{ number_format($ticketType->price, 2) }}</p>
                                            <p class="text-xs text-zinc-500">per ticket</p>
                                        </div>
                                    </div>
                                </button>
                            @endforeach
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

                                <div class="rounded-lg bg-white p-4 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
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
                                <flux:button wire:click="cancelSelection" variant="ghost">{{ __('Cancel') }}</flux:button>
                                <flux:button
                                    wire:click="bookTicket"
                                    variant="primary"
                                    icon="ticket"
                                    :disabled="$this->selectedTicketTypeId === null"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading.remove wire:target="bookTicket">{{ __('Confirm Booking') }}</span>
                                    <span wire:loading wire:target="bookTicket">{{ __('Booking…') }}</span>
                                </flux:button>
                            </div>

                            @error('form.event_id') <flux:text color="red" size="sm" class="mt-2">{{ $message }}</flux:text> @enderror
                            @error('form.ticket_type_id') <flux:text color="red" size="sm" class="mt-2">{{ $message }}</flux:text> @enderror
                            @error('form.quantity') <flux:text color="red" size="sm" class="mt-2">{{ $message }}</flux:text> @enderror
                        </div>
                    @endif
                </div>
            </flux:card>
        </section>
    @endif
</div>
