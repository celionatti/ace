<?php

declare(strict_types=1);

/**
 * Framework: ACE Framework
 * Author: Celio Natti
 * Year:   2024
 * Version: 1.0.0
 */

namespace App\Controllers;

use App\Core\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends Controller
{
    public function index(Request $request) {
        $name = $request->query->get('name', 'World');
        $this->render('home/index.twig', ['name' => $name]);
    }
}