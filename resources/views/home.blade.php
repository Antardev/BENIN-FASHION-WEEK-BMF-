@extends('layout')
@section('title', 'Réservez vos billets')
@section('content')
<header class="hero text-white" style="background-image:url('{{ asset('images/hero.jpg') }}')" role="img" aria-label="Mannequins en robes brodées sur le podium de la Benin Fashion Week">
    <div class="container pb-5 pt-5">
        <p class="hero-kicker">Cotonou · Édition 2026</p>
        <img class="hero-logo mb-4" src="{{ asset('images/logo.png') }}" alt="Logo Benin Fashion Week">
        <h1 class="mb-3">Réservez vos places pour la Benin Fashion Week</h1>
        <p class="lead text-body-secondary mb-4" style="max-width:48ch">Trois rendez-vous, un billet par événement. Payez en ligne, recevez votre billet avec QR code et présentez-le à l'entrée.</p>
        <div class="d-flex flex-wrap align-items-center gap-4">
            <a class="btn btn-gold btn-lg" href="#programme">Voir le programme</a>
            <div class="strokes" aria-hidden="true"><i></i><i></i><i></i></div>
        </div>
    </div>
</header>

<main id="programme" class="programme">
@foreach($events as $event)
@php $imgs = $event->images ?? []; @endphp
@php
    $ticketDetails = match ($event->slug) {
        'defile-haute-couture-distinctions' => [
            ['name' => 'Standard', 'description' => 'Placement en tribune', 'price' => 15000],
            ['name' => 'VIP', 'description' => 'Placement privilégié et accueil dédié', 'price' => 25000],
        ],
        'fashion-brunch' => [
            ['name' => 'Place brunch', 'description' => 'Une place à table', 'price' => 15000],
            ['name' => 'Reservation de stand', 'description' => 'Stand 3m x 3m table + 2 chaises', 'price' => 50000],
        ],
        'concours-jeunes-talents' => [
            ['name' => 'Standard', 'description' => 'Accès à la salle', 'price' => 5000],
            ['name' => 'VIP', 'description' => 'Accès privilégié et placement réservé', 'price' => 10000],
        ],
        default => [],
    };
@endphp
<section class="event py-5 border-top" id="{{ $event->slug }}">
    <div class="container">
        <div class="row g-4 g-lg-5 align-items-center">
            <div class="col-lg-6 {{ $loop->even ? 'order-lg-2' : '' }}">
                <div class="event-media">
                    <div class="event-index" aria-hidden="true">0{{ $loop->iteration }}</div>
                @if(count($imgs) >= 2)
                    <div class="row g-3">
                        <div class="col-7"><img class="event-img tall" src="{{ asset('images/'.$imgs[0]) }}" alt="{{ $event->title }}" loading="lazy"></div>
                        <div class="col-5 d-flex align-items-end"><img class="event-img" style="aspect-ratio:3/4" src="{{ asset('images/'.$imgs[1]) }}" alt="{{ $event->title }} : atelier de couture" loading="lazy"></div>
                    </div>
                @elseif(count($imgs) === 1)
                    <img class="event-img" src="{{ asset('images/'.$imgs[0]) }}" alt="{{ $event->title }}" loading="lazy">
                @else
                    <div class="event-ph" aria-hidden="true"><div class="strokes"><i></i><i></i><i></i></div></div>
                @endif
                </div>
            </div>

            <div class="col-lg-6">
                <h2>{{ $event->title }}</h2>
                @if($event->tagline)<p class="tagline mt-2 mb-0">{{ $event->tagline }}</p>@endif
                <p class="text-body-secondary mt-3">{{ $event->description }}</p>
                <p class="text-body-secondary small mb-4">
                    Du 21 au 24 octobre 2026<br>
                    {{ $event->venue ?: 'Lieu à confirmer' }}
                </p>

                <ul class="list-group list-group-flush ticket-list border-top">
                    @foreach($event->ticketTypes as $index => $type)
                    @php $ticket = $ticketDetails[$index] ?? null; @endphp
                    @continue(! $ticket)
                    @php $left = $type->remaining(); @endphp
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <strong>{{ $ticket['name'] }}</strong>
                                <div class="small text-body-secondary">{{ $ticket['description'] }}</div>
                            </div>
                            <div class="price">{{ number_format($ticket['price'], 0, ',', ' ') }} FCFA</div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            @if($left === 0)
                                <span class="badge badge-sold">Complet</span>
                            @else
                                <span class="small {{ $left <= 20 ? '' : 'text-body-secondary' }}">
                                    @if($left <= 20)<span class="badge badge-low">Plus que {{ $left }} places</span>@else {{ $left }} places disponibles @endif
                                    <span class="capacity-note">sur {{ $type->capacity }}</span>
                                </span>
                                <a class="btn btn-gold btn-sm" href="{{ route('checkout', $type) }}">Réserver</a>
                            @endif
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>
@endforeach
</main>
@endsection
