<?php

use App\Models\Event;
use Livewire\Component;

new class extends Component
{
    public Event $event;

    public $isSelected;

    public $statusVal;
};
?>

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
        <div class="absolute top-3 left-3 flex items-center gap-2">
            <flux:badge color="green" size="sm">{{ ucfirst($statusVal) }}</flux:badge>
        </div>
        @if ($isSelected)
            <div class="absolute top-3 right-3">
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
            {{ $slots['select-event'] }}
        </div>
    </div>
</div>
