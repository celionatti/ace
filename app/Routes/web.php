<?php

use FastRoute\RouteCollector;

$r->addRoute('GET', '/', 'App\Controllers\HomeController@index');
$r->addRoute('POST', '/login', 'App\Controllers\AuthController@login');