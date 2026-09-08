<?php

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
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name'); // e.g., 'VIP', 'Early Bird'
            $table->decimal('price', 8, 2)->default(0.00); // 0 for free events
            $table->integer('capacity'); // Total tickets available for this tier
            $table->integer('remaining_capacity'); // Track inventory
            $table->dateTime('sales_start')->nullable();
            $table->dateTime('sales_end')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
