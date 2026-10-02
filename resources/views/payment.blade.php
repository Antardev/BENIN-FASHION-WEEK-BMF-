@extends('layout')
@section('title', 'Paiement')
@section('content')
<main class="container py-5" style="max-width:600px">
    <h1 class="h2 mb-4">Paiement de la commande {{ $order->reference }}</h1>
    <div class="summary mb-4">
        {{ $order->ticketType->event->title }} · {{ $order->ticketType->name }}
        <div class="text-body-secondary">{{ $order->quantity }} place(s) · <strong class="text-white">{{ $order->total_label }}</strong></div>
    </div>
    @if(config('bfw.payment') === 'simulation')
        <div class="alert alert-warning" role="note">Mode simulation : aucun argent n'est prélevé.</div>
        <form method="post" action="{{ route('payment.simulate', $order->reference) }}">
            @csrf
            <button class="btn btn-gold btn-lg w-100">Simuler un paiement réussi</button>
        </form>
    @elseif(config('bfw.payment') === 'kkiapay')
        <p class="text-body-secondary mb-3">Choisissez Mobile Money ou carte pour régler votre réservation.</p>
        <button class="btn btn-gold btn-lg w-100" id="kkiapay-launch" type="button">Payer maintenant</button>
        <p class="mt-3 mb-0" id="kkiapay-status" role="status" aria-live="polite"></p>
        <button class="btn btn-outline-light mt-3 w-100" id="kkiapay-retry" type="button" hidden>Réessayer la vérification</button>
        <form class="mt-4" id="kkiapay-recovery">
            <label class="form-label" for="kkiapay-transaction-id">Déjà débité sans avoir reçu vos billets ? Saisissez l’identifiant de transaction KkiaPay.</label>
            <input class="form-control mb-2" id="kkiapay-transaction-id" type="text" maxlength="255" autocomplete="off">
            <button class="btn btn-gold btn-lg w-100" type="submit">Vérifier mon paiement</button>
        </form>
    @endif
</main>
@endsection

@push('scripts')
    @if(config('bfw.payment') === 'kkiapay')
        <script>
            (() => {
                const button = document.getElementById('kkiapay-launch');
                const status = document.getElementById('kkiapay-status');
                const retryButton = document.getElementById('kkiapay-retry');
                const recoveryForm = document.getElementById('kkiapay-recovery');
                const transactionInput = document.getElementById('kkiapay-transaction-id');
                const transactionStorageKey = @json('kkiapay-transaction-'.$order->reference);
                let verificationStarted = false;
                let pendingTransactionId = null;

                if (typeof openKkiapayWidget !== 'function'
                    || typeof addSuccessListener !== 'function'
                    || typeof addFailedListener !== 'function') {
                    button.disabled = true;
                    status.textContent = 'Le service de paiement est indisponible. Veuillez réessayer plus tard.';

                    return;
                }

                const verifyTransaction = async (transactionId) => {
                    if (verificationStarted) {
                        return;
                    }

                    verificationStarted = true;
                    button.disabled = true;
                    retryButton.hidden = true;
                    status.textContent = 'Vérification du paiement…';

                    try {
                        const result = await fetch(@json(route('payment.kkiapay.verify', $order->reference)), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': @json(csrf_token()),
                            },
                            body: JSON.stringify({ transactionId }),
                        });
                        const payload = await result.json();

                        if (! result.ok) {
                            status.textContent = payload.message || 'Le paiement n’a pas pu être vérifié.';
                            verificationStarted = false;
                            retryButton.hidden = false;

                            return;
                        }

                        sessionStorage.removeItem(transactionStorageKey);
                        window.location.assign(payload.redirect);
                    } catch {
                        status.textContent = 'Paiement reçu, mais la vérification a échoué. Réessayez la vérification sans repayer.';
                        verificationStarted = false;
                        retryButton.hidden = false;
                    }
                };

                button.addEventListener('click', () => {
                    openKkiapayWidget({
                        amount: @json($order->total),
                        key: @json(config('services.kkiapay.public_key')),
                        position: 'center',
                        phone: @json($order->buyer_phone),
                        name: @json($order->buyer_name),
                        email: @json($order->buyer_email),
                        partnerId: @json($order->reference),
                        data: @json($order->reference),
                        sandbox: @json(config('services.kkiapay.sandbox')),
                    });
                });

                retryButton.addEventListener('click', () => {
                    if (pendingTransactionId) {
                        verifyTransaction(pendingTransactionId);
                    }
                });

                recoveryForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    pendingTransactionId = transactionInput.value.trim();

                    if (! pendingTransactionId) {
                        status.textContent = 'Saisissez l’identifiant de la transaction KkiaPay.';

                        return;
                    }

                    button.disabled = true;
                    try {
                        sessionStorage.setItem(transactionStorageKey, pendingTransactionId);
                    } catch {
                        status.textContent = 'La vérification sera lancée sans mémoriser la transaction dans ce navigateur.';
                    }

                    verifyTransaction(pendingTransactionId);
                });

                addSuccessListener((response) => {
                    if (! response || typeof response.transactionId !== 'string') {
                        status.textContent = 'La transaction reçue est invalide. Veuillez contacter l’assistance.';

                        return;
                    }

                    pendingTransactionId = response.transactionId;
                    transactionInput.value = pendingTransactionId;
                    try {
                        sessionStorage.setItem(transactionStorageKey, pendingTransactionId);
                    } catch {
                        status.textContent = 'Paiement reçu. Gardez cette page ouverte pendant la vérification.';
                    }

                    verifyTransaction(pendingTransactionId);
                });

                addFailedListener(() => {
                    status.textContent = 'Le paiement a échoué ou a été annulé.';
                });

                try {
                    pendingTransactionId = sessionStorage.getItem(transactionStorageKey);
                } catch {
                    status.textContent = 'Impossible de restaurer automatiquement la vérification. Contactez l’assistance avec votre référence de commande.';
                }

                if (pendingTransactionId) {
                    transactionInput.value = pendingTransactionId;
                    button.disabled = true;
                    verifyTransaction(pendingTransactionId);
                }
            })();
        </script>
    @endif
@endpush
