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
            <p class="mb-0">Suivez les ventes et les entrées validées pour les événements des 23 et 24 octobre 2026.</p>
        </div>
        <a class="btn btn-gold" href="{{ route('admin.scan') }}">Contrôler les entrées <span aria-hidden="true">→</span></a>
    </div>

    @if(session('capacity_updated'))
        <div class="alert alert-success admin-alert" role="status">{{ session('capacity_updated') }}</div>
    @endif

    <div class="row g-3 mb-5">
        <div class="col-md-4"><div class="admin-stat admin-stat-red"><div class="stat-icon">↗</div><div class="text-body-secondary small">Billets vendus</div><div class="stat-value">{{ number_format($sold, 0, ',', ' ') }}</div><div class="small text-body-secondary">Toutes catégories confondues</div></div></div>
        <div class="col-md-4"><div class="admin-stat admin-stat-gold"><div class="stat-icon">₣</div><div class="text-body-secondary small">Recettes</div><div class="stat-value">{{ number_format($revenue, 0, ',', ' ') }} <small>FCFA</small></div><div class="small text-body-secondary">Ventes confirmées</div></div></div>
        <div class="col-md-4"><div class="admin-stat admin-stat-green"><div class="stat-icon">✓</div><div class="text-body-secondary small">Entrées validées</div><div class="stat-value">{{ number_format($checkedIn, 0, ',', ' ') }}</div><div class="small text-body-secondary">sur {{ number_format($paidTickets, 0, ',', ' ') }} billet{{ $paidTickets > 1 ? 's' : '' }} payé{{ $paidTickets > 1 ? 's' : '' }} · contrôlés à l’accueil</div></div></div>
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
                    <thead>
                        <tr>
                            <th>Catégorie</th>
                            <th class="text-end">Vendus</th>
                            <th class="text-end">Capacité</th>
                            <th class="text-end">Recettes</th>
                        </tr>
                    </thead>
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

    <section class="admin-event-panel mb-4" id="billets-scannes">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
                <p class="admin-eyebrow mb-1">Contrôle des entrées</p>
                <h2 class="h3 mb-1">Billets déjà scannés</h2>
                <span class="text-body-secondary small">
                    {{ $scannedTickets->total() }} billet{{ $scannedTickets->total() > 1 ? 's' : '' }} validé{{ $scannedTickets->total() > 1 ? 's' : '' }} à l’entrée, du plus récent au plus ancien
                </span>
            </div>
            <a class="btn btn-sm btn-outline-dark" href="{{ route('admin.scan') }}">Scanner des billets</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Billet</th>
                        <th>Scanné le</th>
                        <th>Acheteur</th>
                        <th>Événement / catégorie</th>
                        <th>Commande</th>
                        <th>Scanné par</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($scannedTickets as $ticket)
                    <tr>
                        <td><strong>{{ $ticket->code }}</strong></td>
                        <td class="scan-time">
                            {{ $ticket->checked_in_at->format('d/m/Y à H:i:s') }}<br>
                            <span class="small text-body-secondary">{{ $ticket->checked_in_at->diffForHumans() }}</span>
                        </td>
                        <td>
                            {{ $ticket->order->buyer_name }}<br>
                            <span class="small text-body-secondary">{{ $ticket->order->buyer_phone }}</span>
                        </td>
                        <td>
                            {{ $ticket->order->ticketType->event->title }}<br>
                            <span class="small text-body-secondary">{{ $ticket->order->ticketType->name }}</span>
                        </td>
                        <td>{{ $ticket->order->reference }}</td>
                        <td>
                            {{ $ticket->checkedInBy?->name ?? '—' }}
                            @if($ticket->check_in_ip)<br><span class="small text-body-secondary">IP {{ $ticket->check_in_ip }}</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-body-secondary">Aucun billet scanné pour le moment.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($scannedTickets->hasPages())
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                <span class="text-body-secondary small">
                    Affichage {{ $scannedTickets->firstItem() }}–{{ $scannedTickets->lastItem() }} sur {{ $scannedTickets->total() }}
                </span>
                {{ $scannedTickets->fragment('billets-scannes')->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>

    <section class="admin-event-panel">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
                <p class="admin-eyebrow mb-1">Suivi des commandes</p>
                <h2 class="h3 mb-1">Tous les achats de tickets</h2>
                <span class="text-body-secondary small">
                    {{ $orders->total() }} commande{{ $orders->total() !== 1 ? 's' : '' }} enregistrée{{ $orders->total() !== 1 ? 's' : '' }}
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Commande</th>
                        <th>Date</th>
                        <th>Acheteur</th>
                        <th>Événement / catégorie</th>
                        <th class="text-end">Qté</th>
                        <th class="text-end">Total</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <strong>{{ $order->reference }}</strong><br>
                            <span class="small text-body-secondary">{{ $order->buyer_phone }}</span>
                        </td>
                        <td>{{ $order->created_at->format('d/m/Y à H:i') }}</td>
                        <td>
                            {{ $order->buyer_name }}<br>
                            <span class="small text-body-secondary">{{ $order->buyer_email }}</span>
                        </td>
                        <td>
                            {{ $order->ticketType->event->title }}<br>
                            <span class="small text-body-secondary">{{ $order->ticketType->name }}</span>
                        </td>
                        <td class="text-end">{{ $order->quantity }}</td>
                        <td class="text-end">{{ number_format($order->total, 0, ',', ' ') }} FCFA</td>
                        <td>{{ ucfirst($order->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-body-secondary">Aucun achat enregistré.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- ═══════════ Pagination ═══════════ --}}
        @if($orders->total() > 0)
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                <span class="text-body-secondary small">
                    Affichage {{ $orders->firstItem() }}–{{ $orders->lastItem() }}
                    sur {{ $orders->total() }} commande{{ $orders->total() !== 1 ? 's' : '' }}
                </span>
                {{ $orders->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>
</main>
@endsection
