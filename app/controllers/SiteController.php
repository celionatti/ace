<?php

declare(strict_types=1);

namespace Ace\app\controllers;

use Ace\ace\Controller;
use Ace\app\models\User;

class SiteController extends Controller
{
    public function index($request, $response)
    {
        $user = new User();
        echo "<pre>";
        var_dump($user->find(1)->toJson());
        // var_dump($user->findByEmail("amisuusman@gmail.com"));
        die;
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