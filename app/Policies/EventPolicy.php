<?php

namespace App\Policies;

use App\Enums\EventPermissionEnum;
use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    public function updateEventStatus(User $user, Event $event): bool
    {
        return $user->can(EventPermissionEnum::UPDATE_STATUS_EVENTS->value) && $user->id == $event->organizer_id;
    }

    public function viewAnyEvent(User $user, Event $event): bool
    {
        return $user->can(EventPermissionEnum::VIEW_EVENTS->value);
    }

    public function deleteEvent(User $user, Event $event): bool
    {
        return $user->can(EventPermissionEnum::DELETE_EVENTS->value) && $user->id == $event->organizer_id;
    }

    public function updateEvent(User $user, Event $event): bool
    {
        return $user->can(EventPermissionEnum::UPDATE_EVENTS->value) && $user->id == $event->organizer_id;
    }

    public function createEvent(User $user): bool
    {
        return $user->can(EventPermissionEnum::CREATE_EVENTS->value);
    }
}
