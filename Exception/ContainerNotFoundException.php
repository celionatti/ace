<?php

namespace Ace\Exception;

use Psr\Container\NotFoundExceptionInterface;
use Ace\Exception\AceException;

class ContainerNotFoundException extends AceException implements NotFoundExceptionInterface
{
    // No additional code needed here
}