<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] #[Layout('layouts.app.attendee')] class extends Component
{

};
?>

<div>
    {{-- Live as if you were to die tomorrow. Learn as if you were to live forever. - Mahatma Gandhi --}}

    <p class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
        {{ __('Welcome to the Attendee Dashboard!') }}
    </p>
</div>