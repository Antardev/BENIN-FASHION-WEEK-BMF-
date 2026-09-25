<!doctype html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Billetterie') · Benin Fashion Week</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/bfw.css') }}">
</head>
<body>
<nav class="navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-3 text-white" href="{{ route('home') }}">
            <img src="{{ asset('images/logo.png') }}" alt="">
            Benin Fashion Week
        </a>
        <div class="navbar-actions d-flex align-items-center gap-2">
            <a class="btn btn-outline-light btn-sm" href="{{ route('home') }}#programme">Programme</a>
            @auth
                <form method="post" action="{{ route('admin.logout') }}" class="d-inline">
                    @csrf
                    <button class="btn btn-dark btn-sm" type="submit">Déconnexion</button>
                </form>
            @else
                <a class="btn btn-dark btn-sm" href="{{ route('login') }}">Espace admin</a>
            @endauth
        </div>
    </div>
</nav>

@yield('content')

<footer class="py-4 mt-5">
    <div class="container">Benin Fashion Week · Questions : <a href="mailto:{{ config('bfw.contact_email') }}">{{ config('bfw.contact_email') }}</a></div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
