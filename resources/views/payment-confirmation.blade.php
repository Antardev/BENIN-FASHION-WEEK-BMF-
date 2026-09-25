@extends('layout')
@section('title', 'Réservation confirmée')
@section('content')
<main class="container py-5" style="max-width:680px">
    <div class="summary">
        <p class="admin-eyebrow mb-2">Paiement confirmé</p>
        <h1 class="h2 mb-3">Vos billets ont été envoyés par email</h1>
        <p class="mb-2">La commande <strong>{{ $order->reference }}</strong> est confirmée.</p>
        <p class="text-body-secondary mb-0">
            Consultez <strong>{{ $order->buyer_email }}</strong> pour retrouver vos billets et les QR codes associés.
        </p>
    </div>
    <a class="btn btn-gold mt-4" href="{{ route('home') }}">Retour au programme</a>
</main>
@endsection
