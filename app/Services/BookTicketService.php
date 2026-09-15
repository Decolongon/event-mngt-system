<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Collection;

class BookTicketService
{
   public function getEvents(): Collection
   {
        return Event::query()
            ->where('status', EventStatus::Published->value)
            ->latest()
            ->get();
   }

   public function getTicketTypes(Event $event): Collection
   {
        return TicketType::query()
            ->where('remaining_capacity', '>', 0)
            ->where('sales_start', '<=', now())
            ->where('sales_end', '>=', now())
            ->where('event_id', $event->id)
            ->get();
   }

}
