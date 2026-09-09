<?php

use App\Models\Event;
use Livewire\Attributes\Reactive;
use Livewire\Component;

new class extends Component
{
    public Event $event;
};
?>

<div
    class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-700 dark:bg-zinc-900"
    wire:key="event-{{ $event->id }}. '-'. {{ $event->status }}"
>
    <div class="min-w-0 flex-1">
        <p class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $event->title }}</p>
        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
            <span class="inline-flex items-center gap-1.5">
                <flux:icon.map-pin variant="micro" class="size-3.5" />
                {{ $event->location }}
            </span>
            <span class="hidden text-zinc-300 sm:inline dark:text-zinc-600">·</span>
            <span class="inline-flex items-center gap-1.5">
                <flux:icon.calendar variant="micro" class="size-3.5" />
                {{ $event->start_time?->format('M j, Y g:i A') }}
            </span>
        </div>
        @if ($event->description)
            <p class="mt-2 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $event->description }}</p>
        @endif
    </div>
    @php
        $status = $event->status instanceof \BackedEnum ? $event->status->value : $event->status;
        $badgeColor = match ($status) {
            'published' => 'green',
            'completed' => 'blue',
            'cancelled' => 'red',
            default => 'zinc',
        };
    @endphp
    <div class="flex items-center gap-2 shrink-0 self-start sm:self-center">
        <flux:badge :color="$badgeColor" size="sm">{{ ucfirst($status) }}</flux:badge>
        {{ $slots['statusUpdate'] }}
    </div>
</div>
