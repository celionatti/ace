<?php

namespace Ace\ace\Exception;

use Psr\Container\NotFoundExceptionInterface;
use Ace\ace\Exception\AceException;

class ContainerNotFoundException extends AceException implements NotFoundExceptionInterface
{
    // No additional code needed here
}