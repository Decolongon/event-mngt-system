<?php

namespace App\Enums;

enum EventPermissionEnum: string
{
    case CREATE_EVENTS = 'create_events';
    case VIEW_EVENTS = 'view_events';
    case UPDATE_EVENTS = 'update_events';
    case DELETE_EVENTS = 'delete_events';
    case UPDATE_STATUS_EVENTS = 'update_status_events';
}
