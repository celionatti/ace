<?php

declare(strict_types=1);

namespace Ace\app\controllers;

use Ace\ace\Controller;
use Ace\app\models\User;

class SiteController extends Controller
{
    public function index($request, $response)
    {
        $this->view->setLayout('test');
        $this->view->setTitle("Testing Page");
        $user = new User();
        // echo "<pre>";
        // var_dump($user->paginate());
        // var_dump($user->findByEmail("amisuusman@gmail.com"));
        // die;
        $data = [
            'title' => 'Welcome to Ace Framework',
            'isLoggedIn' => true, // Change to false to test @if
            'user' => 'AceUser',
            'articles' => ['First Post', 'Second Post', 'Third Post'],
            'content' => '<b>Hello</b>',
            'flashMessage' => ['type' => 'danger', 'message' => 'Welcome to the ace community.']
        ];


        $this->view->render("test/home", $data);
    }
}