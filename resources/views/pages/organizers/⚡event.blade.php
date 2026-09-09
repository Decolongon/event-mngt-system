<?php

use App\Livewire\Forms\EventForm;
use App\Models\Event;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Create Event')] class extends Component
{
    use WithFileUploads;

    public EventForm $form;

    public function createEvent(): void
    {
        $this->form->store();
        $this->form->reset();

        Flux::toast(
            heading: 'Event created',
            text: 'New event has been created successfully.',
            variant: 'success',
        );
    }

    #[Computed]
    public function events(): Collection
    {
        return Event::query()
            ->where('organizer_id', Auth::id())
            ->latest()
            ->get();
    }
};
?>

<div class="flex w-full flex-col gap-8">
    <section class="w-full max-w-3xl">
        <div class="mb-6">
            <flux:heading size="xl" level="1">{{ __('Create Event') }}</flux:heading>
            <flux:subheading>{{ __('Add a new event and share it with your attendees.') }}</flux:subheading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8 dark:border-zinc-700 dark:bg-zinc-900">
            <form wire:submit="createEvent" class="space-y-6">
                <flux:field>
                    <flux:label badge="{{ __('Required') }}">{{ __('Event Title') }}</flux:label>
                    <flux:input wire:model="form.title" placeholder="{{ __('Summer Music Festival 2026') }}" />
                    <flux:error name="form.title" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Description') }}</flux:label>
                    <flux:textarea
                        wire:model="form.description"
                        placeholder="{{ __('Tell attendees what makes this event special...') }}"
                        rows="4"
                    />
                    <flux:description>{{ __('Max 255 characters.') }}</flux:description>
                    <flux:error name="form.description" />
                </flux:field>

                <flux:field>
                    <flux:label badge="{{ __('Required') }}">{{ __('Location') }}</flux:label>
                    <flux:input
                        wire:model="form.location"
                        placeholder="{{ __('Manila, Philippines or Online') }}"
                        icon="map-pin"
                    />
                    <flux:error name="form.location" />
                </flux:field>

                <div class="grid gap-6 md:grid-cols-2">
                    <flux:field>
                        <flux:label badge="{{ __('Required') }}">{{ __('Start Time') }}</flux:label>
                        <flux:input wire:model="form.start_time" type="datetime-local" max="9999-12-31T23:59" />
                        <flux:error name="form.start_time" />
                    </flux:field>

                    <flux:field>
                        <flux:label badge="{{ __('Required') }}">{{ __('End Time') }}</flux:label>
                        <flux:input wire:model="form.end_time" type="datetime-local" max="9999-12-31T23:59" />
                        <flux:error name="form.end_time" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('Banner Image') }}</flux:label>
                    @if ($form->banner_image && is_string($form->banner_image) === false)
                        <div class="mb-3 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <img
                                src="{{ $form->banner_image->temporaryUrl() }}"
                                alt="Preview"
                                class="h-48 w-full object-cover"
                            />
                        </div>
                    @endif
                    <flux:input wire:model="form.banner_image" type="file" accept="image/*" />
                    <flux:description>{{ __('PNG, JPG up to 2MB.') }}</flux:description>
                    <flux:error name="form.banner_image" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model="form.status" placeholder="{{ __('Choose status...') }}">
                        <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                        <flux:select.option value="published">{{ __('Published') }}</flux:select.option>
                        <flux:select.option value="cancelled">{{ __('Cancelled') }}</flux:select.option>
                        <flux:select.option value="completed">{{ __('Completed') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="form.status" />
                </flux:field>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <flux:button type="submit" variant="primary" icon="plus"> {{ __('Create Event') }} </flux:button>
                </div>
            </form>
        </div>
    </section>

    <section class="w-full max-w-3xl">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading>{{ __('Your Events') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->events->count() }} {{ Str::plural('event', $this->events->count()) }}</flux:text>
        </div>

        <div class="space-y-3">
            @forelse ($this->events as $event)
                <livewire:event-item :event="$event" :wire:key="'event-'.$event->id" />
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50/50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
                    <div class="mx-auto flex size-10 items-center justify-center rounded-full border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:icon.calendar class="size-5 text-zinc-400" />
                    </div>
                    <flux:heading size="sm" class="mt-3">{{ __('No events yet') }}</flux:heading>
                    <flux:text class="mt-1 text-sm">{{ __('Create your first event using the form above.') }}</flux:text>
                </div>
            @endforelse
        </div>
    </section>
</div>
