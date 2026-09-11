<?php

namespace App\Livewire\Forms;

use App\Models\TicketType;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TicketTypeForm extends Form
{
    public int $event_id;

    public string $name = '';

    public float $price = 0;

    public int $capacity = 0;

    public $sales_start;

    public $sales_end;

    protected function rules(): array
    {
        return [
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:100|min:3',
            'price' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:0',
            'sales_start' => 'required|date',
            'sales_end' => 'required|date|after_or_equal:sales_start',
        ];
    }

    public function store(): void
    {
        $validate = $this->validate();
        $validate['remaining_capacity'] = $validate['capacity'];
        TicketType::create($validate);
    }

    public function update(TicketType $ticketType): void
    {
        $validate = $this->validate();

        $ticketType->update($validate);
    }

    public function setTicketType(TicketType $ticketType): void
    {
        $this->event_id = $ticketType->event_id;
        $this->name = $ticketType->name;
        $this->price = $ticketType->price;
        $this->capacity = $ticketType->capacity;
        $this->sales_start = $ticketType->sales_start;
        $this->sales_end = $ticketType->sales_end;
    }
}
