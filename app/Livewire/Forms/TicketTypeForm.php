<?php

namespace App\Livewire\Forms;

use App\Models\TicketType;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TicketTypeForm extends Form
{
    #[Locked]
    public int $event_id;

    public string $name = '';

    public string $description = '';

    public float $price = 0;

    public int $capacity = 0;

    public $sale_start;

    public $sale_end;

    protected function rules(): array
    {
        return [
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:100|min:3',
            'description' => 'nullable|string|min:3|max:255',
            'price' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:0',
            'sales_start' => 'required|date',
            'sales_end' => 'required|date|after_or_equal:sales_start',
        ];
    }

    public function store(): void
    {
        $validate = $this->validate();

        TicketType::create($validate);
    }

    public function update(TicketType $ticketType): void
    {
        $validate = $this->validate();

        $ticketType->update($validate);
    }

    public function setTicketType(TicketType $ticketType): void
    {
        $this->name = $ticketType->name;
        $this->description = $ticketType->description;
        $this->price = $ticketType->price;
        $this->capacity = $ticketType->capacity;
        $this->sale_start = $ticketType->sale_start;
        $this->sale_end = $ticketType->sale_end;
    }
}
