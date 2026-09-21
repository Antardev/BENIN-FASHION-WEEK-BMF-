@extends('layout')
@section('title', 'Réserver')
@section('content')
@php $img = $ticketType->event->images[0] ?? null; @endphp
<main class="container py-5">
    <div class="row g-5 justify-content-center">
        @if($img)
        <div class="col-lg-5 d-none d-lg-block">
            <img class="event-img tall" src="{{ asset('images/'.$img) }}" alt="{{ $ticketType->event->title }}">
        </div>
        @endif
        <div class="col-lg-6">
            <div class="strokes mb-3" style="width:110px" aria-hidden="true"><i></i><i></i><i></i></div>
            <h1 class="h2 mb-3">{{ $ticketType->event->title }}</h1>
            <div class="summary mb-4">
                <strong>{{ $ticketType->name }}</strong> · {{ $ticketType->price_label }} par place
                <div class="small text-body-secondary">{{ $ticketType->remaining() }} places restantes</div>
            </div>

            <form method="post" action="{{ route('order.store', $ticketType) }}" novalidate>
                @csrf
                <div class="form-floating mb-3">
                    <input id="buyer_name" name="buyer_name" class="form-control @error('buyer_name') is-invalid @enderror" placeholder="Nom complet" value="{{ old('buyer_name') }}" autocomplete="name" required>
                    <label for="buyer_name">Nom complet</label>
                    @error('buyer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-floating mb-3">
                    <input id="buyer_email" type="email" name="buyer_email" class="form-control @error('buyer_email') is-invalid @enderror" placeholder="Adresse e-mail" value="{{ old('buyer_email') }}" autocomplete="email" required>
                    <label for="buyer_email">Adresse e-mail</label>
                    @error('buyer_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-floating mb-3">
                    <input id="buyer_phone" type="tel" name="buyer_phone" class="form-control @error('buyer_phone') is-invalid @enderror" placeholder="Téléphone" value="{{ old('buyer_phone') }}" autocomplete="tel" required>
                    <label for="buyer_phone">Téléphone (Mobile Money), ex. +229 ...</label>
                    @error('buyer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-floating mb-4">
                    <input id="quantity" type="number" name="quantity" min="1" max="10" class="form-control @error('quantity') is-invalid @enderror" placeholder="Nombre de places" value="{{ old('quantity', 1) }}" required>
                    <label for="quantity">Nombre de places</label>
                    @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-gold btn-lg w-100">Payer ma réservation</button>
            </form>
        </div>
    </div>
</main>
@endsection
