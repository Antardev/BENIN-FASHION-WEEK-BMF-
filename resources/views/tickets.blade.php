@extends('layout')
@section('title', 'Vos billets')
@section('content')
<main class="container py-5" style="max-width:760px">
    <h1 class="h2">Vos billets</h1>
    <p class="text-body-secondary">Commande {{ $order->reference }} · {{ $order->buyer_name }}. Conservez cette page ou imprimez-la : le QR code est votre entrée.</p>
    @foreach($order->tickets as $ticket)
    <article class="ticket {{ $ticket->checked_in_at ? 'used' : '' }} p-4 ps-5 my-3 d-flex justify-content-between align-items-center gap-3">
        <div>
            <h2 class="h4 mb-1">{{ $order->ticketType->event->title }}</h2>
            <div class="text-body-secondary">{{ $order->ticketType->name }}</div>
            <div class="text-body-secondary">{{ $order->ticketType->event->starts_at ? $order->ticketType->event->starts_at->translatedFormat('j F Y, H\hi') : 'Date à confirmer' }}</div>
            <div class="code mt-3">{{ $ticket->code }}</div>
            @if($ticket->checked_in_at)<span class="badge text-bg-secondary mt-2">Déjà utilisé</span>@endif
        </div>
        <div>{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(130)->margin(0)->generate($ticket->code) !!}</div>
    </article>
    @endforeach
    <button class="btn btn-gold noprint mt-3" onclick="window.print()">Imprimer mes billets</button>
</main>
@endsection
