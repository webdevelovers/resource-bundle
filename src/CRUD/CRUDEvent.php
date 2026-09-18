<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\CRUD;

enum CRUDEvent: string
{
    // CRUD basic events
    case INDEX = 'index';
    case SHOW = 'show';
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case BULK_DELETE = 'bulk_delete';
    case APPLY_TRANSITION = 'apply_transition';

    // Lifecycle Events
    case INITIALIZE = 'initialize';
    case AUTHORIZATION_CHECK = 'authorization_check';
    case DTO_MAP = 'map';
    case MESSENGER_DISPATCH = 'dispatch';
    case ORM_PERSIST = 'orm_persist';
    case ORM_UPDATE = 'orm_update';
    case ORM_DELETE = 'orm_delete';
    case RENDER = 'render';
}
