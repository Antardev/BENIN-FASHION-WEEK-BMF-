@extends('layout')
@section('title', 'Connexion administrateur')
@section('content')
<main class="admin-login-page">
    <div class="admin-login-decoration admin-login-decoration-one" aria-hidden="true"></div>
    <div class="admin-login-decoration admin-login-decoration-two" aria-hidden="true"></div>
    <section class="admin-login-card" aria-labelledby="login-title">
        <div class="admin-login-mark">BFW<span>·</span>ADMIN</div>
        <p class="admin-eyebrow mb-2">Espace sécurisé</p>
        <h1 id="login-title">Bienvenue en coulisses.</h1>
        <p class="text-body-secondary mb-4">Connectez-vous pour piloter les ventes et contrôler les entrées.</p>

        <form method="post" action="{{ route('admin.login.store') }}">
            @csrf
            <div class="form-floating mb-3">
                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Adresse e-mail" value="{{ old('email') }}" autocomplete="email" required autofocus>
                <label for="email">Adresse e-mail</label>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-floating mb-3">
                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Mot de passe" autocomplete="current-password" required>
                <label for="password">Mot de passe</label>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-check mb-4">
                <input id="remember" type="checkbox" name="remember" value="1" class="form-check-input">
                <label class="form-check-label small" for="remember">Rester connecté sur cet appareil</label>
            </div>
            <button class="btn btn-gold btn-lg w-100" type="submit">Accéder au dashboard <span aria-hidden="true">→</span></button>
        </form>
        <a class="admin-login-back" href="{{ route('home') }}">Retour à la billetterie</a>
    </section>
</main>
@endsection
