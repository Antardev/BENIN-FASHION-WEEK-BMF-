@extends('layout')
@section('title', 'Contrôle des entrées')
@section('content')
<main class="container py-5" style="max-width:560px">
    <h1 class="h2 mb-4">Contrôle des entrées</h1>
    @if(session('scan'))
        <div class="alert {{ session('scan')[0] === 'ok' ? 'alert-success' : 'alert-danger' }}" role="status">{{ session('scan')[1] }}</div>
    @endif
    <form method="post" action="{{ route('admin.check') }}">
        @csrf
        <div class="form-floating mb-3">
            <input id="code" name="code" class="form-control" placeholder="Code" autocomplete="off" autofocus required>
            <label for="code">Code du billet (BFW-XXXXXXXXXX)</label>
        </div>
        <button class="btn btn-gold btn-lg w-100">Valider l'entrée</button>
    </form>
    <p class="text-body-secondary small mt-4">Un lecteur QR USB se comporte comme un clavier : scannez, le code s'écrit et « Entrée » valide.</p>
    <a href="{{ route('admin.dashboard') }}">Retour aux ventes</a>
</main>
@endsection
