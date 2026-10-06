@extends('layout')
@section('title', 'Mot de passe oublié')
@section('content')
<main class="admin-login-page">
    <div class="admin-login-decoration admin-login-decoration-one" aria-hidden="true"></div>
    <div class="admin-login-decoration admin-login-decoration-two" aria-hidden="true"></div>
    <section class="admin-login-card" aria-labelledby="forgot-password-title">
        <div class="admin-login-mark">BFW<span>·</span>ADMIN</div>
        <p class="admin-eyebrow mb-2">Récupération du compte</p>
        <h1 id="forgot-password-title">Mot de passe oublié ?</h1>
        <p class="text-body-secondary mb-4">Saisissez l’adresse e-mail associée à votre compte administrateur. Nous vous enverrons un lien sécurisé pour choisir un nouveau mot de passe.</p>

        @if(session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <form method="post" action="{{ route('password.email') }}">
            @csrf
            <div class="form-floating mb-3">
                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Adresse e-mail" value="{{ old('email') }}" autocomplete="email" required autofocus>
                <label for="email">Adresse e-mail</label>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-gold btn-lg w-100" type="submit">Envoyer le lien de réinitialisation</button>
        </form>
        <a class="admin-login-back" href="{{ route('login') }}">Retour à la connexion</a>
    </section>
</main>
@endsection
