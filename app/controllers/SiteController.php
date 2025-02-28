<?php

declare(strict_types=1);

namespace Ace\app\controllers;

class SiteController
{
    public function index($request, $response, $id)
    {
        var_dump("Welcome to the Ace Framework Site Controller: {$id}");
    }

    public function dashboard($request, $response)
    {
        var_dump("Welcome to the Ace Framework Admin Dashboard Controller");
    }
}