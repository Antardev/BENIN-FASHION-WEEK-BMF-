@extends('layout')
@section('title', 'Réservez vos billets')

@section('content')

@if(session('ticket_coming_soon'))
    <div class="container pt-4">
        <div class="alert alert-info mb-0">{{ session('ticket_coming_soon') }}</div>
    </div>
@endif

{{--
    Informations publiques de la page d'accueil.
    Ces textes sont volontairement statiques : ils ne sont plus lus depuis
    les colonnes title/tagline/description/starts_at/venue de la table events.
    La base de données reste utilisée uniquement pour les billets, capacités
    et réservations.
--}}
@php
    $programme = [
        'concours-jeunes-talents' => [
            'title'       => 'Concours Jeunes Talents',
            'tagline'     => 'La nouvelle génération de créateurs béninois.',
            'description' => 'De jeunes stylistes présentent leurs créations devant un jury et le public.',
            'date'        => '22 octobre 2026',
            'venue'       => 'BENIN ROYAL HOTEL',
            'images'      => ['talents.jpeg'],
            'tickets'     => [
                'Standard' => ['description' => 'Accès à la salle', 'price' => 5000],
                'VIP'      => ['description' => 'Accès privilégié et placement réservé', 'price' => 10000],
            ],
        ],
        'defile-haute-couture-distinctions' => [
            'title'       => 'Défilé Haute Couture & Distinctions',
            'tagline'     => 'La soirée phare de la semaine.',
            'description' => 'Les créateurs présentent leurs collections haute couture, suivies de la remise des distinctions.',
            'date'        => '23 octobre 2026',
            'venue'       => "FRANCOISE'S GARDEN COTONOU",
            'images'      => ['defile.jpeg'],
            'tickets'     => [
                'Standard' => ['description' => 'Placement en tribune', 'price' => 15000],
                'VIP'      => ['description' => 'Placement privilégié et accueil dédié', 'price' => 25000],
            ],
        ],
        'fashion-brunch' => [
            'title'       => 'Fashion Brunch',
            'tagline'     => 'Un brunch entre créateurs, invités et passionnés.',
            'description' => 'Un moment convivial pour échanger avec les créateurs autour d’un brunch.',
            'date'        => '24 octobre 2026',
            'venue'       => 'AMBA YARD FERME (TORI)',
            'images'      => ['Fashionbrunch.jpeg'],
            'tickets'     => [
                'Place brunch'          => ['description' => 'Une place à table', 'price' => 15000],
                'Reservation de stand'  => ['description' => 'Stand 3m x 3m table + 2 chaises', 'price' => 50000],
            ],
        ],
    ];

    /**
     * Retourne uniquement les images réellement présentes dans public/images.
     * Évite les <img> cassées quand un fichier est renommé ou absent.
     */
    $existingImages = function (array $files): array {
        return array_values(array_filter(
            $files,
            fn ($file) => $file && file_exists(public_path('images/' . $file))
        ));
    };
@endphp

<header class="hero text-white"
        style="background-image:url('{{ asset('images/hero.jpg') }}')"
        role="img"
        aria-label="Mannequins en robes brodées sur le podium de la Benin Fashion Week">
    <div class="container pb-5 pt-5">
        <p class="hero-kicker">Cotonou · Édition 2026</p>
        <img class="hero-logo mb-4" src="{{ asset('images/logo.png') }}" alt="Logo Benin Fashion Week">
        <h1 class="mb-3">Réservez vos places pour la Benin Fashion Week</h1>
        <p class="lead text-body-secondary mb-4" style="max-width:48ch">
            Trois rendez-vous, un billet par événement. Payez en ligne, recevez votre billet
            avec QR code et présentez-le à l'entrée.
        </p>
        <div class="d-flex flex-wrap align-items-center gap-4">
            <a class="btn btn-gold btn-lg" href="#programme">Voir le programme</a>
            <div class="strokes" aria-hidden="true"><i></i><i></i><i></i></div>
        </div>
    </div>
</header>

<main id="programme" class="programme">
@foreach($programme as $slug => $eventInfo)
    @php
        $event = $events->firstWhere('slug', $slug);
        if (! $event) {
            continue;
        }
        $imgs = $existingImages($eventInfo['images']);
    @endphp

    <section class="event py-5 border-top" id="{{ $slug }}">
        <div class="container">
            <div class="row g-4 g-lg-5 align-items-center">

                {{-- Média de l'événement --}}
                <div class="col-lg-6 {{ $loop->even ? 'order-lg-2' : '' }}">
                    <div class="event-media">
                        <div class="event-index" aria-hidden="true">0{{ $loop->iteration }}</div>

                        @if(count($imgs) >= 2)
                            {{-- Collage à deux images --}}
                            <div class="row g-3">
                                <div class="col-7">
                                    <img class="event-img tall"
                                         src="{{ asset('images/'.$imgs[0]) }}"
                                         alt="{{ $eventInfo['title'] }}"
                                         loading="lazy" decoding="async">
                                </div>
                                <div class="col-5 d-flex align-items-end">
                                    <img class="event-img" style="aspect-ratio:3/4; object-fit:cover"
                                         src="{{ asset('images/'.$imgs[1]) }}"
                                         alt="{{ $eventInfo['title'] }} : atelier de couture"
                                         loading="lazy" decoding="async">
                                </div>
                            </div>
                        @elseif(count($imgs) === 1)
                            {{-- Image unique : même traitement visuel que la grande image du collage --}}
                            <img class="event-img tall"
                                 src="{{ asset('images/'.$imgs[0]) }}"
                                 alt="{{ $eventInfo['title'] }}"
                                 style="aspect-ratio:4/5; object-fit:cover; width:100%"
                                 decoding="async">
                        @else
                            {{-- Aucune image trouvée : placeholder décoratif --}}
                            <div class="event-ph" aria-hidden="true">
                                <div class="strokes"><i></i><i></i><i></i></div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Contenu de l'événement --}}
                <div class="col-lg-6">
                    <h2>{{ $eventInfo['title'] }}</h2>

                    @if($eventInfo['tagline'])
                        <p class="tagline mt-2 mb-0">{{ $eventInfo['tagline'] }}</p>
                    @endif

                    <p class="text-body-secondary mt-3">{{ $eventInfo['description'] }}</p>

                    <p class="text-body-secondary small mb-4">
                        {{ $eventInfo['date'] }}<br>
                        {{ $eventInfo['venue'] }}
                    </p>

                    <ul class="list-group list-group-flush ticket-list border-top">
                        @foreach($event->ticketTypes as $type)
                            @php
                                $ticket = $eventInfo['tickets'][$type->name] ?? null;
                                if (! $ticket) {
                                    continue;
                                }
                                $left = $type->remaining();
                            @endphp

                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <strong>{{ $type->name }}</strong>
                                        <div class="small text-body-secondary">{{ $ticket['description'] }}</div>
                                    </div>
                                    <div class="price">{{ number_format($ticket['price'], 0, ',', ' ') }} FCFA</div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-2">
    @if($left === 0)
        <span class="badge badge-sold">Complet</span>
    @else
        @if($left <= 20)
            <span class="badge badge-low">Places limitées</span>
        @endif
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
