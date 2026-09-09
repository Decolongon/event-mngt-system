<?php

namespace App\Livewire\Forms;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Livewire\WithFileUploads;

class EventForm extends Form
{
    use WithFileUploads;

    public $title = '';

    public $description = '';

    public $location = '';

    public $start_time = '';

    public $end_time = '';

    public $banner_image = null;

    public $status = 'draft';

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:100|min:3',
            'description' => 'nullable|string|min:3|max:255',
            'location' => 'required|string|max:255|min:3',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time',
            'banner_image' => 'nullable|image|max:2048',
            'status' => Rule::enum(EventStatus::class),
        ];
    }

    public function store(): void
    {
        $validated = $this->validate();

        $validated['organizer_id'] = Auth::id();
        $validated['slug'] = Str::slug($validated['title']);

        if ($validated['banner_image']) {
            $validated['banner_image'] = $validated['banner_image']->store('events', 'public');
        }

        Event::create($validated);
    }

    public function setEvent(Event $event): void
    {
        $this->title = $event->title;
        $this->description = $event->description;
        $this->location = $event->location;
        $this->start_time = $event->start_time instanceof \DateTimeInterface ? $event->start_time->format('Y-m-d\TH:i') : $event->start_time;
        $this->end_time = $event->end_time instanceof \DateTimeInterface ? $event->end_time->format('Y-m-d\TH:i') : $event->end_time;
        $this->banner_image = $event->banner_image;
        $this->status = $event->status instanceof \BackedEnum ? $event->status->value : $event->status;
    }

    public function update(Event $event): void
    {
        $validated = $this->validate();

        $validated['slug'] = Str::slug($validated['title']);

        $event->update($validated);
    }
}
