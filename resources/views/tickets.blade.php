@extends('layout')
@section('title', 'Vos billets')
@section('content')
<main class="container py-5" style="max-width:1100px">
    <h1 class="h2">Vos billets</h1>
    <p class="text-body-secondary">Commande {{ $order->reference }} · {{ $order->buyer_name }}. Vos billets ont été envoyés à <strong>{{ $order->buyer_email }}</strong> (PDF joint). Le QR code est votre entrée.</p>

    @if(session('mail_error'))
        <div class="alert alert-warning">L'email n'a pas pu être envoyé pour le moment. Téléchargez vos billets ci-dessous et conservez-les.</div>
    @endif

    <a class="btn btn-gold noprint mb-4" href="{{ URL::signedRoute('orders.tickets.download', $order->reference) }}">Télécharger tous mes billets (PDF)</a>

    {{-- Grille 2 colonnes --}}
    <div class="row g-3">
        @foreach($order->tickets as $ticket)
            <div class="col-12 col-md-6">
                <article class="ticket {{ $ticket->checked_in_at ? 'used' : '' }} p-4 ps-5 h-100 d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <h2 class="h4 mb-1">{{ $order->ticketType->event->title }}</h2>
                        <div class="text-body-secondary">{{ $order->ticketType->name }}</div>
                        <div class="text-body-secondary">{{ $order->ticketType->event->starts_at ? $order->ticketType->event->date_label : 'Date à confirmer' }}</div>
                        <div class="code mt-3">{{ $ticket->code }}</div>
                        @if($ticket->checked_in_at)
                            <span class="badge text-bg-secondary mt-2">Déjà utilisé</span>
                        @endif
                    </div>
                    <div class="text-center">
                        <img src="{{ $qr->svgDataUri($ticket, 130) }}" width="130" height="130" alt="QR code {{ $ticket->code }}" style="background:#fff">
                        <a class="d-block small mt-2 noprint" href="{{ URL::signedRoute('tickets.download', $ticket->id) }}">Télécharger (PDF)</a>
                    </div>
                </article>
            </div>
        @endforeach
    </div>

    <button class="btn btn-gold noprint mt-4" onclick="window.print()">Imprimer mes billets</button>
    <a class="btn btn-gold mt-4" href="{{ route('home') }}">Retour au programme</a>
</main>
@endsection
