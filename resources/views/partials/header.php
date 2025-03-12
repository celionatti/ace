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