# MiniMVC Framework — Official Developer Guide Book

Welcome to the **MiniMVC Framework Guide Book**! This guide is designed to help you understand the architecture, features, and usage of your custom, lightweight PHP MVC framework. 

---

## 🗺️ Framework Directory Structure

Here is a visual map of the framework components:

```text
├── app/
│   ├── Controllers/   # Application controllers (Handles requests & returns views)
│   ├── Core/          # Core framework components (Router, Request, Model, Session, etc.)
│   ├── Middlewares/   # Middlewares (Csrf, Auth, RateLimit, RBAC, etc.)
│   └── Models/        # Active Record database models
├── config/
│   ├── app.php        # General framework settings
│   └── database.php   # Database credentials
├── migrations/        # CLI migration scripts
├── public/            # Entrypoint (index.php) and static assets (CSS, JS, uploads)
├── routes/
│   └── web.php        # Router definitions for web requests
├── storage/
│   └── views/         # Compiled template file caches
├── views/
│   ├── errors/        # Redesigned custom error views (404, 500)
│   ├── layouts/       # Main layouts
│   └── emails/        # HTML email templates
└── ace                # CLI runner script
```

---

## ⚡ 1. Routing & Requests

### Registering Routes
Routes are registered in `routes/web.php` mapping URIs to Controller Actions:
```php
// Static GET
$router->get('/profile', [AuthController::class, 'profile']);

// Dynamic Parameter GET (extracted as $id inside Controller)
$router->get('/blog/{id}', [BlogController::class, 'show']);

// POST Request
$router->post('/pay/initialize', [PaymentController::class, 'initialize']);
```

### Retrieving Input safely
Controllers read inputs from the `Request` instance. By default, inputs are cleaned from XSS:
```php
public function saveComment(Request $request)
{
    // Returns sanitized body inputs (stripping script tags/inline events)
    $data = $request->getBody();
    $comment = $data['content'];

    // Returns completely unescaped inputs (for raw HTML editors or passwords)
    $rawData = $request->getRawBody();
}
```

---

## 🔒 2. Controller & Middlewares

Middlewares intercept incoming requests before reaching controller actions. They can be registered in the controller's constructor:

```php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\RoleMiddleware;

class AdminController extends Controller
{
    public function __construct()
    {
        // 1. Only logged-in users can access any action in this controller
        $this->registerMiddleware(new AuthMiddleware());

        // 2. Only users with the 'admin' role can access the dashboard action
        $this->registerMiddleware(new RoleMiddleware(['admin'], ['dashboard']));

        // 3. Apply Csrf checks to POST actions
        $this->registerMiddleware(new CsrfMiddleware(['saveConfig']));
    }
}
```

---

## 💾 3. Models & Database (Active Record)

Models inherit from `App\Core\Model` and act as Active Records.

### Defining a Model
```php
namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    public static function tableName(): string { return 'products'; }
    public function primaryKey(): string { return 'id'; }

    // Validation rules
    public function rules(): array
    {
        return [
            'title' => 'required',
            'price' => 'required',
            'email' => 'unique:users,email' // Unique field checking
        ];
    }
}
```

### CRUD Operations
```php
// Find a single record
$user = User::findOne(['email' => 'admin@example.com']);

// Create a new record
$product = new Product();
$product->loadData([
    'title' => 'Space Boots',
    'price' => 150.00
]);

if ($product->validate() && $product->save()) {
    echo "Product saved with ID: " . $product->id;
} else {
    // Print validation errors
    print_r($product->errors);
}
```

---

## 🎨 4. Blade-Like View Engine

Views are written in standard PHP files under `views/` and compiled to `storage/views/`. They use dynamic, clean tags:

### Layouts & Sections
To wrap a view inside `views/layouts/main.php`:
```php
@extends('layouts/main')

@section('title')
My Page Title
@endsection

@section('content')
<h1>Welcome!</h1>
@endsection
```

### Echoing Variables (Auto-Escaping)
```html
<!-- Escaped echo (Defends against XSS) -->
<h3>Hello, {{ $user->name }}</h3>

<!-- Raw, unescaped echo (Use only for trusted HTML) -->
<div>{!! $post->content !!}</div>

<!-- Safe URL output (keeps & intact, blocks javascript:/data: schemes) -->
<img src="@url($imageUrl)">
<a href="@url($link)">Visit</a>
```

> **When to use which?**
> - `{{ $var }}` — For text content. Escapes HTML entities (XSS safe).
> - `{!! $var !!}` — For trusted raw HTML (e.g. rich text editor output).
> - `@url($var)` — For URLs in `src`, `href`, `action` attributes. Keeps `&` intact for query strings while blocking dangerous schemes like `javascript:` and `data:`.

### Directives
```html
@if(hasRole('admin'))
    <a href="/admin">Dashboard</a>
@endif

@auth
    <p>Logged in as {{ user()->name }}</p>
@endauth

@guest
    <a href="/login">Login</a>
@endguest

<!-- New Helper Directives -->
@csrf  <!-- Outputs hidden CSRF token input tag -->

@isset($user)
    <p>User exists: {{ $user->name }}</p>
@endisset

@empty($items)
    <p>No items found</p>
@endempty

@session('success')
    <div class="alert alert-success">{{ $value }}</div>
@endsession
```

### 🧱 Includes & Reusable Components

You can split views into partial files using `@include`, and package reusable HTML snippets (like cards, modals, or alerts) using `@component`.

#### Including Sub-views
Sub-views inherit all variables from the parent view automatically. You can also pass additional parameter overrides:
```html
<!-- Simple include -->
@include('partials/sidebar')

<!-- Include with additional parameter overrides -->
@include('partials/header', ['title' => 'Product Detail'])
```

#### Reusable Layout Components
Components let you build template slots. In your component file (e.g., `views/components/card.php`):
```html
<div class="card">
    <div class="card-header">{!! $header !!}</div>
    <div class="card-body">{!! $slot !!}</div>
    <div class="card-footer">Footer: {{ $footer }}</div>
</div>
```
Then, render the component and feed contents into the default slot and named slots:
```html
@component('components/card', ['footer' => 'Copyright 2026'])
    @slot('header')
        <h3>Custom Card Header</h3>
    @endslot

    <p>This is the main card body content (mapped to $slot).</p>
@endcomponent
```

---

## ⚙️ 5. Ace CLI Console Tool

The `ace` script provides commands to automate framework actions. Run commands using:
`php ace <command>`

### Generator commands:
* `php ace make:controller <Name>` — Generates a new Controller class.
* `php ace make:model <Name>` — Generates a new Active Record Model class.
* `php ace make:middleware <Name>` — Generates a new Middleware class.
* `php ace make:migration <description>` — Generates a new timestamped migration class.

### Database Migration commands:
* `php ace migrate` — Runs all pending database migration files under a new batch number.
* `php ace migrate:rollback` — Rolls back the last batch of migrations (reverting changes).
* `php ace migrate:status` — Displays status of all migrations (applied or pending, batch, and date).

### View Cache commands:
* `php ace view:clear` — Wipes all compiled, cached view templates.

---

## 🖥️ 6. Reusable Default Admin Panel Dashboard

MiniMVC has a Filament-inspired, responsive default admin panel interface:
* **Layout Structure**: Defined in `views/layouts/admin.php`, this features a modern sidebar navigation menu that collapses into a slide-out drawer on mobile and responsive screens.
* **Controller Routing**: Exposes routes in `routes/web.php` for dashboard metrics, blog posts management, user account management, and roles/permissions overview.
* **Interactive Charting**: Embedded Chart.js line charts representing daily payment transaction volume logs with hovering tooltips.
* **Role Assignments**: Administrators can change user roles (`admin`, `editor`, `user`) directly via dashboard forms.

---

## 🔐 7. Built-in Security Features

1. **CSRF Protection**: Apply `CsrfMiddleware` to POST routes and include `@csrf` in your forms.
2. **Remember Me**: Persistent secure cookie login. Automatically rotates session tokens on every visit.
3. **XSS Protection**: Requests auto-clean values. Views auto-escape standard echos (`{{ ... }}`).
4. **Global Security Headers & CSP**: Global middleware `SecurityHeadersMiddleware` appends `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, and a strict `Content-Security-Policy` (CSP) response header to secure views against XSS, clickjacking, and content sniffing.
5. **Enterprise Role-Based Access Control (RBAC)**: Build fine-grained systems using:
   - Database tables: `roles`, `permissions`, `role_permissions`, `user_roles`.
   - Helper methods: `$user->hasRole('admin')`, `$user->hasPermission('create-posts')`.
   - View directives: `hasRole('slug')` / `hasPermission('slug')`.

---

## 📦 8. Publishing Ace Core to Packagist

The Ace core framework code has been completely isolated under the `src/` directory inside the `Ace\` namespace. This makes it ready to be published on Packagist as a standalone package (e.g. `your-vendor/ace-core`).

### Repository Split Structure

To release Ace, split your codebase into two separate repositories:

1. **`ace-core` (The Package)**
   Contains only the core library files:
   - `src/` directory (includes all framework classes like `Router`, `Request`, `Response`, `helpers.php`, etc.)
   - `composer.json` defining the package autoloading:
     ```json
     {
         "name": "your-vendor/ace-core",
         "description": "Core library for the Ace MVC Framework",
         "license": "MIT",
         "require": {
             "php": ">=8.0",
             "phpmailer/phpmailer": "^7.1"
         },
         "autoload": {
             "psr-4": {
                 "Ace\\": "src/"
             },
             "files": [
                 "src/helpers.php"
             ]
         }
     }
     ```

2. **`ace-project` (The Application Skeleton)**
   The starter template cloned by users to build their app. It has:
   - `app/` (Controllers, Models, Middlewares under `App\` namespace)
   - `config/`, `public/`, `routes/`, `views/`
   - `ace` CLI runner
   - `composer.json` requiring the core package:
     ```json
     {
         "name": "your-vendor/ace-project",
         "description": "Ace MVC Framework Application Skeleton",
         "require": {
             "php": ">=8.0",
             "your-vendor/ace-core": "^1.0"
         },
         "autoload": {
             "psr-4": {
                 "App\\": "app/"
             }
         }
     }
     ```

### Publishing to Packagist
1. Create a GitHub repository for your `ace-core` folder.
2. Log into [Packagist.org](https://packagist.org/) and submit the URL of your repository.
3. Set up a GitHub webhook to automatically update Packagist when new tags or commits are pushed.
4. Users can now build applications using `composer create-project your-vendor/ace-project`.
