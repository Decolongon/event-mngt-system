<?php

namespace App\Models;

use App\Models\Event;
use App\Models\Payment;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['attendee_id', 'event_id', 'ticket_type_id', 'quantity', 'total_price', 'status', 'booking_reference'])]
class Booking extends Model
{
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendee_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
