<?php

namespace App\Controllers;

use Ace\Controller;
use Ace\Request;

class HomeController extends Controller
{
    /**
     * Render the landing page
     */
    public function index(Request $request): string
    {
        return $this->render('home', [
            'title' => 'Welcome to Mini MVC'
        ]);
    }
}

