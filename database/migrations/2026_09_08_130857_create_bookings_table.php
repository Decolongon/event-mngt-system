<?php

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'attendee_id')->constrained('users')->cascadeOnDelete(); // User who made the booking
            $table->foreignIdFor(TicketType::class)->constrained('ticket_types')->cascadeOnDelete(); // The ticket type booked
            $table->foreignIdFor(Event::class)->constrained('events')->cascadeOnDelete(); // The event for which the booking is made
            $table->string('booking_reference')->unique(); // Unique reference for the booking
            $table->integer('quantity'); // Number of tickets booked
            $table->decimal('total_price', 8, 2); // Total price for the booking
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending'); // Booking status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
