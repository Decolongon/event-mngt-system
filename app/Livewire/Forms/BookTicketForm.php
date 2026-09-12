<?php

namespace App\Livewire\Forms;

use App\Models\Booking;
use App\Models\TicketType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class BookTicketForm extends Form
{
    public ?int $event_id;
    public ?int $ticket_type_id;
    public ?int $quantity = 1;
    public ?float $total_price = 0;
    public string $booking_reference = '';

    protected function rules(): array
    {
        return [
            'event_id' => 'required|exists:events,id',
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'quantity' => 'required|integer|min:1',
            'total_price' => 'sometimes|numeric|min:0',
        ];
    }

    public function store(): void
    {
        $validated = $this->validate();
        $validated['booking_reference'] = $this->generateBookingRef();
        $validated['total_price'] = $this->calculateTotalPrice();
        Auth::user()->bookings()->create($validated);
    }

    protected function generateBookingRef(): string
    {
        do {
            // 7 random characters (letters) + 5 random integers = 12 char reference
            $letters = Str::upper(Str::random(7));
            // Ensure the 7-char string is letters only (Str::random is alphanumeric, so replace digits with random letters)
            $letters = preg_replace_callback('/[0-9]/', fn () => chr(random_int(65, 90)), $letters);

            $numbers = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);

            $reference = $letters . $numbers;
        } while (Booking::where('booking_reference', $reference)->exists());

        return $reference;
    }

    protected function calculateTotalPrice(): float
    {
        $this->total_price = TicketType::findOrFail($this->ticket_type_id)->price * $this->quantity;
        return $this->total_price;
    }
}
