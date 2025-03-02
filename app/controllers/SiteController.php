<?php

declare(strict_types=1);

namespace Ace\app\controllers;

use Ace\ace\Controller;

class SiteController extends Controller
{
    public function index($request, $response)
    {
        $data = [
            'title' => 'Welcome to Ace Framework',
            'isLoggedIn' => true, // Change to false to test @if
            'username' => 'AceUser',
            'articles' => ['First Post', 'Second Post', 'Third Post'],
            'content' => '<b>Hello</b>'
        ];

        echo $this->render("home", $data);
    }

    public function dashboard($request, $response)
    {
        var_dump("Welcome to the Ace Framework Admin Dashboard Controller");
    }
}