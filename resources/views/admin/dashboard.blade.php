@extends('layout')
@section('title', 'Tableau de bord')
@section('content')
@php
    $types = $events->flatMap->ticketTypes;
    $sold = $types->sum(fn($t) => $t->orders->sum('quantity'));
    $revenue = $types->sum(fn($t) => $t->orders->sum('quantity') * $t->price);
@endphp
<main class="container py-5">
    <div class="admin-hero mb-5">
        <div>
            <p class="admin-eyebrow mb-2">Administration · BFW 2026</p>
            <h1 class="mb-2">Piloter la billetterie</h1>
            <p class="mb-0">Suivez les ventes et les entrées validées du 21 au 24 octobre 2026.</p>
        </div>
        <a class="btn btn-gold" href="{{ route('admin.scan') }}">Contrôler les entrées <span aria-hidden="true">→</span></a>
    </div>

    @if(session('capacity_updated'))
        <div class="alert alert-success admin-alert" role="status">{{ session('capacity_updated') }}</div>
    @endif

    <div class="row g-3 mb-5">
        <div class="col-md-4"><div class="admin-stat admin-stat-red"><div class="stat-icon">↗</div><div class="text-body-secondary small">Billets vendus</div><div class="stat-value">{{ number_format($sold, 0, ',', ' ') }}</div><div class="small text-body-secondary">Toutes catégories confondues</div></div></div>
        <div class="col-md-4"><div class="admin-stat admin-stat-gold"><div class="stat-icon">₣</div><div class="text-body-secondary small">Recettes</div><div class="stat-value">{{ number_format($revenue, 0, ',', ' ') }} <small>FCFA</small></div><div class="small text-body-secondary">Ventes confirmées</div></div></div>
        <div class="col-md-4"><div class="admin-stat admin-stat-green"><div class="stat-icon">✓</div><div class="text-body-secondary small">Entrées validées</div><div class="stat-value">{{ number_format($checkedIn, 0, ',', ' ') }}</div><div class="small text-body-secondary">Billets contrôlés à l’accueil</div></div></div>
    </div>

    @foreach($events as $event)
        @php
            $eventSold = $event->ticketTypes->sum(fn ($t) => $t->orders->sum('quantity'));
            $eventCapacity = $event->ticketTypes->sum('capacity');
            $eventRate = $eventCapacity > 0 ? min(100, round(($eventSold / $eventCapacity) * 100)) : 0;
        @endphp
        <section class="admin-event-panel mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <p class="admin-eyebrow mb-1">Événement 0{{ $loop->iteration }}</p>
                    <h2 class="h3 mb-1">{{ $event->title }}</h2>
                    <span class="text-body-secondary small">{{ $eventSold }} billets vendus sur {{ $eventCapacity }}</span>
                </div>
                <div class="event-rate"><strong>{{ $eventRate }}%</strong><span>rempli</span></div>
            </div>
            <div class="progress admin-progress mb-4" role="progressbar" aria-label="{{ $eventRate }} pour cent des places vendues" aria-valuenow="{{ $eventRate }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: {{ $eventRate }}%"></div>
            </div>
            <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Catégorie</th><th class="text-end">Vendus</th><th class="text-end">Capacité</th><th class="text-end">Recettes</th></tr></thead>
            <tbody>
            @foreach($event->ticketTypes as $t)
                @php $qty = $t->orders->sum('quantity'); @endphp
                <tr>
                    <td>{{ $t->name }}</td>
                    <td class="text-end">{{ $qty }}</td>
                    <td class="text-end">
                        <form method="post" action="{{ route('admin.capacity.update', $t) }}" class="admin-capacity-form">
                            @csrf
                            <label class="visually-hidden" for="capacity-{{ $t->id }}">Capacité de {{ $t->name }}</label>
                            <input id="capacity-{{ $t->id }}" type="number" name="capacity" min="{{ $qty }}" max="100000" value="{{ $t->capacity }}" required>
                            <button type="submit" class="btn btn-sm btn-outline-dark" title="Enregistrer la capacité de {{ $t->name }}">Enregistrer</button>
                        </form>
                    </td>
                    <td class="text-end">{{ number_format($qty * $t->price, 0, ',', ' ') }} FCFA</td>
                </tr>
            @endforeach
            </tbody>
        </table>
            </div>
        </section>
    @endforeach
</main>
@endsection
