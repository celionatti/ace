<?php

declare(strict_types=1);

namespace Ace\app\controllers;

use Ace\ace\Controller;
use Ace\ace\Http\Request;
use Ace\ace\Http\Response;

class HomeController extends Controller
{
    /**
     * Display the index page
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return $this->view('index');
    }
}