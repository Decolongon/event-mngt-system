<?php

use App\Enums\EventStatus;
use App\Livewire\Forms\TicketTypeForm;
use App\Models\Event;
use App\Models\TicketType;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Ticket Types')] class extends Component
{
    public TicketTypeForm $form;

    #[Locked]
    public ?int $editingTicketTypeId = null;

    public function openCreateModal(): void
    {
        $this->form->reset();
        $this->editingTicketTypeId = null;

        Flux::modal('create-ticket-type')->show();
    }

    public function cancelCreate(): void
    {
        $this->form->reset();

        Flux::modal('create-ticket-type')->close();
    }

    public function createTicketType(): void
    {
        $this->form->store();
        $this->dispatch('ticket-type-updated')->to(component: 'ticket-type-item');
        $this->form->reset();
        unset($this->ticketTypes);

        Flux::modal('create-ticket-type')->close();

        Flux::toast(
            heading: 'Ticket type created',
            text: 'New ticket type has been created successfully.',
            variant: 'success',
        );
    }

    #[Computed]
    public function events(): Collection
    {
        return Event::query()
            ->where('organizer_id', Auth::id())
            ->where('status', EventStatus::Published->value)
            ->get(['id', 'title']);
    }

    #[Computed]
    public function ticketTypes(): Collection
    {
        return TicketType::query()
            ->whereHas('event', fn ($q) => $q->where('organizer_id', Auth::id()))
            ->with('event')
            ->latest()
            ->get();
    }

    public function editTicketType(int $ticketTypeId): void
    {
        $ticketType = TicketType::whereHas('event', fn ($q) => $q->where('organizer_id', Auth::id()))->findOrFail($ticketTypeId);
        $this->form->setTicketType($ticketType);
        $this->editingTicketTypeId = $ticketType->id;

        Flux::modal('edit-ticket-type')->show();
    }

    public function updateTicketType(): void
    {
        $ticketType = TicketType::whereHas('event', fn ($q) => $q->where('organizer_id', Auth::id()))->findOrFail($this->editingTicketTypeId);
        $this->form->update($ticketType);
        $this->dispatch('ticket-type-updated')->to(component: 'ticket-type-item');
        $this->editingTicketTypeId = null;
        $this->form->reset();

        unset($this->ticketTypes);

        Flux::modal('edit-ticket-type')->close();

        Flux::toast(
            heading: __('Ticket type updated'),
            text: __('Ticket type has been updated successfully.'),
            variant: 'success',
        );
    }

    public function cancelEdit(): void
    {
        $this->editingTicketTypeId = null;
        $this->form->reset();
        Flux::modal('edit-ticket-type')->close();
    }

    public function deleteTicketType(int $ticketTypeId): void
    {
        $ticketType = TicketType::whereHas('event', fn ($q) => $q->where('organizer_id', Auth::id()))->findOrFail($ticketTypeId);
        $ticketType->delete();
        $this->dispatch('ticket-type-updated')->to(component: 'ticket-type-item');

        unset($this->ticketTypes);

        Flux::toast(
            heading: __('Ticket type deleted'),
            text: __('Ticket type has been deleted successfully.'),
            variant: 'success',
        );
    }
};
?>

<div class="flex w-full flex-col gap-8">
    <section class="w-full max-w-3xl">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('Ticket Types') }}</flux:heading>
                <flux:subheading>{{ __('Create ticket types for your published events.') }}</flux:subheading>
            </div>
            <flux:button wire:click="openCreateModal" variant="primary" icon="plus" class="shrink-0">{{ __('Create Ticket Type') }}</flux:button>
        </div>
    </section>

    <section class="w-full max-w-3xl">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading>{{ __('Your Ticket Types') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->ticketTypes->count() }} {{ Str::plural('ticket type', $this->ticketTypes->count()) }}</flux:text>
        </div>

        <div class="space-y-3">
            @forelse ($this->ticketTypes as $ticketType)
                <livewire:ticket-type-item :ticket-type="$ticketType" :wire:key="'ticket-type-'.$ticketType->id">
                    <livewire:slot name="action">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="shrink-0" />
                            <flux:menu>
                                <flux:menu.item wire:click="editTicketType({{ $ticketType->id }})" icon="pencil-square">{{ __('Edit') }}</flux:menu.item>
                                <flux:menu.item
                                    wire:click="deleteTicketType({{ $ticketType->id }})"
                                    wire:confirm="{{ __('Are you sure you want to delete this ticket type? This cannot be undone.') }}"
                                    icon="trash"
                                    variant="danger"
                                >{{ __('Delete') }}</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </livewire:slot>
                </livewire:ticket-type-item>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50/50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
                    <div class="mx-auto flex size-10 items-center justify-center rounded-full border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon.ticket class="size-5 text-zinc-400" />
                    </div>
                    <flux:heading size="sm" class="mt-3">{{ __('No ticket types yet') }}</flux:heading>
                    <flux:text class="mt-1 text-sm">{{ __('Create your first ticket type for a published event.') }}</flux:text>
                </div>
            @endforelse
        </div>
    </section>

    <flux:modal name="create-ticket-type" class="max-w-2xl" variant="flyout">
        <form wire:submit="createTicketType" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Create Ticket Type') }}</flux:heading>
                <flux:subheading>{{ __('Add a new ticket type for your event.') }}</flux:subheading>
            </div>

            <flux:field>
                <flux:label badge="{{ __('Required') }}">{{ __('Event') }}</flux:label>
                <flux:select wire:model="form.event_id" placeholder="{{ __('Choose event...') }}">
                    @foreach ($this->events as $event)
                        <flux:select.option value="{{ $event->id }}">{{ $event->title }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="form.event_id" />
            </flux:field>

            <flux:field>
                <flux:label badge="{{ __('Required') }}">{{ __('Ticket Name') }}</flux:label>
                <flux:input wire:model="form.name" placeholder="{{ __('VIP, Early Bird, General Admission') }}" />
                <flux:error name="form.name" />
            </flux:field>

            <div class="grid gap-6 md:grid-cols-2">
                <flux:field>
                    <flux:label badge="{{ __('Required') }}">{{ __('Price') }}</flux:label>
                    <flux:input wire:model="form.price" type="number" step="0.01" min="0" placeholder="0.00" />
                    <flux:error name="form.price" />
                </flux:field>

                <flux:field>
                    <flux:label badge="{{ __('Required') }}">{{ __('Capacity') }}</flux:label>
                    <flux:input wire:model="form.capacity" type="number" min="0" placeholder="100" />
                    <flux:error name="form.capacity" />
                </flux:field>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <flux:field>
                    <flux:label badge="{{ __('Required') }}">{{ __('Sales Start') }}</flux:label>
                    <flux:input wire:model="form.sales_start" type="datetime-local" max="9999-12-31T23:59" />
                    <flux:error name="form.sales_start" />
                    <flux:error name="form.sales_start" />
                </flux:field>

                <flux:field>
                    <flux:label badge="{{ __('Required') }}">{{ __('Sales End') }}</flux:label>
                    <flux:input wire:model="form.sales_end" type="datetime-local" max="9999-12-31T23:59" />
                    <flux:error name="form.sales_end" />
                    <flux:error name="form.sales_end" />
                </flux:field>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="cancelCreate">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="plus">{{ __('Create Ticket Type') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-ticket-type" class="max-w-2xl" variant="flyout">
        <form wire:submit="updateTicketType" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Edit Ticket Type') }}</flux:heading>
                <flux:subheading>{{ __('Update the ticket type details and save changes.') }}</flux:subheading>
            </div>

            <flux:field>
                <flux:label badge="{{ __('Required') }}">{{ __('Event') }}</flux:label>
                <flux:select wire:model="form.event_id" placeholder="{{ __('Choose event...') }}">
                    @foreach ($this->events as $event)
                        <flux:select.option value="{{ $event->id }}">{{ $event->title }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="form.event_id" />
            </flux:field>

            <flux:field>
                <flux:label badge="{{ __('Required') }}">{{ __('Ticket Name') }}</flux:label>
                <flux:input wire:model="form.name" placeholder="{{ __('VIP, Early Bird, General Admission') }}" />
                <flux:error name="form.name" />
            </flux:field>


            <div class="grid gap-6 md:grid-cols-2">
                <flux:field>
                    <flux:label badge="{{ __('Required') }}">{{ __('Price') }}</flux:label>
                    <flux:input wire:model="form.price" type="number" step="0.01" min="0" placeholder="0.00" />
                    <flux:error name="form.price" />
                </flux:field>

                <flux:field>
                    <flux:label badge="{{ __('Required') }}">{{ __('Capacity') }}</flux:label>
                    <flux:input wire:model="form.capacity" type="number" min="0" placeholder="100" />
                    <flux:error name="form.capacity" />
                </flux:field>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <flux:field>
                    <flux:label badge="{{ __('Required') }}">{{ __('Sales Start') }}</flux:label>
                    <flux:input wire:model="form.sales_start" type="datetime-local" max="9999-12-31T23:59" />
                    <flux:error name="form.sales_start" />
                    <flux:error name="form.sales_start" />
                </flux:field>

                <flux:field>
                    <flux:label badge="{{ __('Required') }}">{{ __('Sales End') }}</flux:label>
                    <flux:input wire:model="form.sales_end" type="datetime-local" max="9999-12-31T23:59" />
                    <flux:error name="form.sales_end" />
                    <flux:error name="form.sales_end" />
                </flux:field>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="cancelEdit">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="pencil-square">{{ __('Save Changes') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>