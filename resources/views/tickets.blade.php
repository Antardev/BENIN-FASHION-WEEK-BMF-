@extends('layout')
@section('title', 'Vos billets')
@section('content')
<main class="container py-5" style="max-width:760px">
    <h1 class="h2">Vos billets</h1>
    <p class="text-body-secondary">Commande {{ $order->reference }} · {{ $order->buyer_name }}. Vos billets ont été envoyés à <strong>{{ $order->buyer_email }}</strong> (PDF joint). Le QR code est votre entrée.</p>
    @if(session('mail_error'))
    <div class="alert alert-warning">L'email n'a pas pu être envoyé pour le moment. Téléchargez vos billets ci-dessous et conservez-les.</div>
    @endif
    <a class="btn btn-gold noprint" href="{{ URL::signedRoute('orders.tickets.download', $order->reference) }}">Télécharger tous mes billets (PDF)</a>
    @foreach($order->tickets as $ticket)
    <article class="ticket {{ $ticket->checked_in_at ? 'used' : '' }} p-4 ps-5 my-3 d-flex justify-content-between align-items-center gap-3">
        <div>
            <h2 class="h4 mb-1">{{ $order->ticketType->event->title }}</h2>
            <div class="text-body-secondary">{{ $order->ticketType->name }}</div>
            <div class="text-body-secondary">{{ $order->ticketType->event->starts_at ? $order->ticketType->event->starts_at->translatedFormat('j F Y, H\hi') : 'Date à confirmer' }}</div>
            <div class="code mt-3">{{ $ticket->code }}</div>
            @if($ticket->checked_in_at)<span class="badge text-bg-secondary mt-2">Déjà utilisé</span>@endif
        </div>
        <div class="text-center">
            <img src="{{ $qr->svgDataUri($ticket, 150) }}" width="150" height="150" alt="QR code {{ $ticket->code }}" style="background:#fff">
            <a class="d-block small mt-2 noprint" href="{{ URL::signedRoute('tickets.download', $ticket->id) }}">Télécharger (PDF)</a>
        </div>
    </article>
    @endforeach
    <button class="btn btn-gold noprint mt-3" onclick="window.print()">Imprimer mes billets</button>
</main>
@endsection
