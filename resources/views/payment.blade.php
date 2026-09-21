@extends('layout')
@section('title', 'Paiement')
@section('content')
<main class="container py-5" style="max-width:600px">
    <h1 class="h2 mb-4">Paiement de la commande {{ $order->reference }}</h1>
    <div class="summary mb-4">
        {{ $order->ticketType->event->title }} · {{ $order->ticketType->name }}
        <div class="text-body-secondary">{{ $order->quantity }} place(s) · <strong class="text-white">{{ $order->total_label }}</strong></div>
    </div>
    <div class="alert alert-warning" role="note">Mode simulation : aucun argent n'est prélevé. En production, cette page est remplacée par la passerelle Mobile Money / carte.</div>
    <form method="post" action="{{ route('payment.simulate', $order->reference) }}">
        @csrf
        <button class="btn btn-gold btn-lg w-100">Simuler un paiement réussi</button>
    </form>
</main>
@endsection
