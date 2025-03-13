<?php

declare(strict_types=1);

/**
 * ==================================
 * DefaultSessionHandler ============
 * ==================================
 */

namespace Ace\ace\Session\Handlers;

use Ace\ace\Session\SessionHandler;

class DefaultSessionHandler extends SessionHandler
{
    public function __construct()
    {
        $this->start();
    }
}