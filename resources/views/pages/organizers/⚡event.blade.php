<?php

use App\Livewire\Forms\EventForm;
use App\Models\Event;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Create Event')] class extends Component
{
    use WithFileUploads;

    public EventForm $form;

    #[Locked]
    public ?int $editingEventId = null;

    public function openCreateModal(): void
    {
        Gate::authorize('createEvent', Event::class);
        $this->form->reset();
        $this->editingEventId = null;

        Flux::modal('create-event')->show();
    }

    public function cancelCreate(): void
    {
        $this->form->reset();

        Flux::modal('create-event')->close();
    }

    public function createEvent(): void
    {
        Gate::authorize('createEvent', Event::class);
        $this->form->store();
        $this->form->reset();
        unset($this->events);
        $this->dispatch('event-updated');

        Flux::modal('create-event')->close();

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

    public function updateStatus(int $eventId, string $status): void
    {
        $validated = validator(
            ['status' => $status],
            ['status' => \Illuminate\Validation\Rule::enum(\App\Enums\EventStatus::class)]
        )->validate();

        $event = Event::where('organizer_id', Auth::id())->findOrFail($eventId);
        Gate::authorize('updateEventStatus', $event);
        $event->update(['status' => $validated['status']]);

        unset($this->events);

        Flux::toast(
            heading: __('Status updated'),
            text: __('Event status changed to :status.', ['status' => ucfirst($validated['status'])]),
            variant: 'success',
        );
    }

    public function editEvent(int $eventId): void
    {
        $event = Event::where('organizer_id', Auth::id())->findOrFail($eventId);
        Gate::authorize('updateEvent', $event);
        $this->form->setEvent($event);
        $this->editingEventId = $event->id;

        Flux::modal('edit-event')->show();
    }

    public function updateEvent(): void
    {
        $event = Event::where('organizer_id', Auth::id())->findOrFail($this->editingEventId);
        Gate::authorize('updateEvent', $event);
        $this->form->update($event);

        $this->editingEventId = null;
        $this->form->reset();
        unset($this->events);

        $this->dispatch('event-updated');
        Flux::modal('edit-event')->close();

        Flux::toast(
            heading: __('Event updated'),
            text: __('Event has been updated successfully.'),
            variant: 'success',
        );
    }

    public function cancelEdit(): void
    {
        $this->editingEventId = null;
        $this->form->reset();
        Flux::modal('edit-event')->close();
    }

    public function deleteEvent(int $eventId): void
    {
        $event = Event::where('organizer_id', Auth::id())->findOrFail($eventId);
        Gate::authorize('deleteEvent', $event);
        $event->delete();

        $this->dispatch('event-updated');
        unset($this->events);

        Flux::toast(
            heading: __('Event deleted'),
            text: __('Event has been deleted successfully.'),
            variant: 'success',
        );
    }
};
?>

<div class="flex w-full flex-col gap-8">
    <section class="w-full max-w-3xl">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('Create Event') }}</flux:heading>
                <flux:subheading>{{ __('Add a new event and share it with your attendees.') }}</flux:subheading>
            </div>
            @can(\App\Enums\EventPermissionEnum::CREATE_EVENTS->value)
                <flux:button
                    wire:click="openCreateModal"
                    variant="primary"
                    icon="plus"
                    class="shrink-0"
                >{{ __('Create Event') }}</flux:button>
            @endcan
        </div>
    </section>

    <section class="w-full max-w-3xl">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading>{{ __('Your Events') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->events->count() }} {{ Str::plural('event', $this->events->count()) }}</flux:text>
        </div>

        <div class="space-y-3">
            @can(\App\Enums\EventPermissionEnum::VIEW_EVENTS->value)
                @forelse ($this->events as $event)
                    @php $statusVal = $event->status instanceof \BackedEnum ? $event->status->value : $event->status; @endphp
                    <livewire:event-item :event="$event" :wire:key="'event-'.$event->id.'-'.$statusVal">
                        <livewire:slot name="statusUpdate">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="shrink-0" />
                                <flux:menu>
                                    @can(\App\Enums\EventPermissionEnum::UPDATE_EVENTS->value)
                                        <flux:menu.item
                                            wire:click="editEvent({{ $event->id }})"
                                            icon="pencil-square"
                                        >
                                            {{ __('Edit') }}</flux:menu.item>
                                    @endcan

                                    @can(\App\Enums\EventPermissionEnum::DELETE_EVENTS->value)
                                        <flux:menu.item
                                            wire:click="deleteEvent({{ $event->id }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this event? This cannot be undone.') }}"
                                            icon="trash"
                                            variant="danger"
                                        >
                                            {{ __('Delete') }}</flux:menu.item>
                                    @endcan
                                    <flux:menu.separator />
                                    @can(\App\Enums\EventPermissionEnum::UPDATE_STATUS_EVENTS->value)
                                        <flux:menu.group heading="{{ __('Change status') }}">
                                            <flux:menu.item wire:click="updateStatus({{ $event->id }}, 'draft')">
                                                {{ __('Draft') }}</flux:menu.item>
                                            <flux:menu.item wire:click="updateStatus({{ $event->id }}, 'published')">
                                                {{ __('Published') }}</flux:menu.item>
                                            <flux:menu.item wire:click="updateStatus({{ $event->id }}, 'cancelled')">
                                                {{ __('Cancelled') }}</flux:menu.item>
                                            <flux:menu.item wire:click="updateStatus({{ $event->id }}, 'completed')">
                                                {{ __('Completed') }}</flux:menu.item>
                                        </flux:menu.group>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        </livewire:slot>
                    </livewire:event-item>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50/50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
                        <div class="mx-auto flex size-10 items-center justify-center rounded-full border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                            <flux:icon.calendar class="size-5 text-zinc-400" />
                        </div>
                        <flux:heading size="sm" class="mt-3">{{ __('No events yet') }}</flux:heading>
                        <flux:text class="mt-1 text-sm">{{ __('Create your first event using the form above.') }}</flux:text>
                    </div>
                @endforelse
            @endcan
        </div>
    </section>

    @can(\App\Enums\EventPermissionEnum::CREATE_EVENTS->value)
        <flux:modal name="create-event" class="max-w-2xl" variant="flyout">
            <form wire:submit="createEvent" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Create Event') }}</flux:heading>
                    <flux:subheading>{{ __('Add a new event and share it with your attendees.') }}</flux:subheading>
                </div>

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
                    @elseif (is_string($form->banner_image) && $form->banner_image)
                        <div class="mb-3 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <img
                                src="{{ asset(Storage::url($form->banner_image)) }}"
                                alt="Current banner"
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

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button
                            variant="ghost"
                            type="button"
                            wire:click="cancelCreate"
                        >{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="plus">{{ __('Create Event') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan

    @can(\App\Enums\EventPermissionEnum::UPDATE_EVENTS->value)
        <flux:modal name="edit-event" class="max-w-2xl" variant="flyout">
            <form wire:submit="updateEvent" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Edit Event') }}</flux:heading>
                    <flux:subheading>{{ __('Update the event details and save changes.') }}</flux:subheading>
                </div>

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
                    @elseif (is_string($form->banner_image) && $form->banner_image)
                        <div class="mb-3 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <img
                                src="{{ asset(Storage::url($form->banner_image)) }}"
                                alt="Current banner"
                                class="h-48 w-full object-cover"
                            />
                        </div>
                    @endif
                    <flux:input wire:model="form.banner_image" type="file" accept="image/*" />
                    <flux:description>{{ __('PNG, JPG up to 2MB. Leave empty to keep current image.') }}</flux:description>
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

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button
                            variant="ghost"
                            type="button"
                            wire:click="cancelEdit"
                        >{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button
                        type="submit"
                        variant="primary"
                        icon="pencil-square"
                    >{{ __('Save Changes') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan
</div>
