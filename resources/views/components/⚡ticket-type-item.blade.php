<?php

use App\Models\TicketType;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public TicketType $ticketType;

    #[On('ticket-type-updated')]
    public function reloadTicket()
    {
        $this->ticketType->refresh();
    }
};
?>

    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-700 dark:bg-zinc-900" wire:key="ticket-type-{{ $ticketType->id }}">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $ticketType->name }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                            <span class="inline-flex items-center gap-1.5">
                                <flux:icon.calendar variant="micro" class="size-3.5" />
                                {{ $ticketType->event->title }}
                            </span>
                            <span class="hidden text-zinc-300 sm:inline dark:text-zinc-600">·</span>
                            <span class="inline-flex items-center gap-1.5">
                                <flux:icon.currency-dollar variant="micro" class="size-3.5" />
                                {{ number_format($ticketType->price, 2) }}
                            </span>
                            <span class="hidden text-zinc-300 sm:inline dark:text-zinc-600">·</span>
                            <span>{{ __('Capacity: :count', ['count' => $ticketType->capacity]) }}</span>
                        </div>
                        <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-zinc-400 dark:text-zinc-500">
                            @if ($ticketType->sales_start)
                                <span>{{ __('Sales start: :date', ['date' => \Illuminate\Support\Carbon::parse($ticketType->sales_start)->format('M j, Y g:i A')]) }}</span>
                            @endif
                            @if ($ticketType->sales_end)
                                <span>{{ __('Sales end: :date', ['date' => \Illuminate\Support\Carbon::parse($ticketType->sales_end)->format('M j, Y g:i A')]) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2 self-start sm:self-center">
                        <flux:badge color="zinc" size="sm">{{ __('Remaining: :count', ['count' => $ticketType->remaining_capacity]) }}</flux:badge>
                        {{-- <flux:dropdown position="bottom" align="end">
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
                        </flux:dropdown> --}}
                        {{ $slots['action'] }}
                    </div>
                </div>