<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Action;

enum ResourceActionType: string
{
    case CRUD       = 'crud';                // azioni “standard” in alto (edit/back/delete...)
    case TRANSITION = 'transition';          // transizioni, state machine, ecc.
    case EXTRA      = 'extra';               // azioni extra sotto (print/export/...)
    case BULK       = 'bulk';                // azioni massive
}
