<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $this->getTitle() }} | My Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="/assets/css/main.css">

    <!-- Yield to the 'styles' section if defined -->
    @yield('styles')
</head>
<body class="container">
    <header>
        <nav>
            <div class="logo text-danger" style="color: red;">My Website</div>
            <ul>
                <li><a href="/">Home</a></li>
                <li><a href="/about">About</a></li>
                <li><a href="/contact">Contact</a></li>

                @auth
                    <li><a href="/dashboard">Dashboard</a></li>
                    <li><a href="/logout">Logout</a></li>
                @else
                    <li><a href="/login">Login</a></li>
                @endauth
            </ul>
        </nav>

        <h1>{{ $this->getHeader() }}</h1>
    </header>

    <main>
        <div class="container">
            <!-- This is where the content from the template will be displayed -->
            @yield('content')
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; {{ date('Y') }} My Website. All rights reserved.</p>
        </div>
    </footer>

    <script src="/assets/js/main.js"></script>

    <!-- Yield to the 'scripts' section if defined -->
    @yield('scripts')
</body>
</html>