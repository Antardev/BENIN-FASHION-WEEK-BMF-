@extends('layout')
@section('title', 'Réinitialiser le mot de passe')
@section('content')
<main class="admin-login-page">
    <div class="admin-login-decoration admin-login-decoration-one" aria-hidden="true"></div>
    <div class="admin-login-decoration admin-login-decoration-two" aria-hidden="true"></div>
    <section class="admin-login-card" aria-labelledby="reset-password-title">
        <div class="admin-login-mark">BFW<span>·</span>ADMIN</div>
        <p class="admin-eyebrow mb-2">Sécurisation du compte</p>
        <h1 id="reset-password-title">Choisissez un nouveau mot de passe.</h1>
        <p class="text-body-secondary mb-4">Utilisez au moins 12 caractères pour protéger votre compte administrateur.</p>

        <form method="post" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-floating mb-3">
                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Adresse e-mail" value="{{ old('email', $email) }}" autocomplete="email" required>
                <label for="email">Adresse e-mail</label>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-floating mb-3">
                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Nouveau mot de passe" autocomplete="new-password" minlength="12" required>
                <label for="password">Nouveau mot de passe</label>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-floating mb-4">
                <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" placeholder="Confirmer le mot de passe" autocomplete="new-password" minlength="12" required>
                <label for="password_confirmation">Confirmer le mot de passe</label>
            </div>
            <button class="btn btn-gold btn-lg w-100" type="submit">Enregistrer le nouveau mot de passe</button>
        </form>
        <a class="admin-login-back" href="{{ route('login') }}">Retour à la connexion</a>
    </section>
</main>
@endsection
