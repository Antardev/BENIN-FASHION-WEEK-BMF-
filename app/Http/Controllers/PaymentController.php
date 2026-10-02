<?php

namespace App\Http\Controllers;

use App\Mail\TicketsPurchased;
use App\Models\Order;
use App\Services\PaymentGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Gestion des paiements simulés et de la vérification KkiaPay. */
class PaymentController extends Controller
{
    public function show(Order $order): View|RedirectResponse
    {
        abort_unless(in_array(config('bfw.payment'), ['simulation', 'kkiapay'], true), 404);

        if (config('bfw.payment') === 'kkiapay') {
            abort_if(blank(config('services.kkiapay.public_key')), 503, 'La clé publique KkiaPay n’est pas configurée.');
        }

        // Déjà payée : on n'affiche plus le bouton de paiement.
        if ($order->status === 'paid') {
            return redirect()->route('tickets.show', $order->reference);
        }

        $order->load('ticketType.event');

        return view('payment', compact('order'));
    }

    public function simulate(Order $order): RedirectResponse
    {
        abort_unless(config('bfw.payment') === 'simulation', 404);

        $wasAlreadyPaid = $order->status === 'paid';
        $order->markPaid('SIM-'.$order->reference);

        if (! $wasAlreadyPaid) {
            $this->sendTickets($order);
        }

        return redirect()->route('tickets.show', $order->reference);
    }

    public function verifyKkiapay(Request $request, Order $order, PaymentGateway $gateway): JsonResponse
    {
        abort_unless(config('bfw.payment') === 'kkiapay', 404);

        $data = $request->validate([
            'transactionId' => ['required', 'string', 'max:255'],
        ]);

        if ($order->status === 'paid') {
            return response()->json([
                'redirect' => route('tickets.show', $order->reference),
            ]);
        }

        if (! $gateway->verifyTransaction($order, $data['transactionId'])) {
            return response()->json([
                'message' => 'Le paiement n’a pas pu être vérifié.',
            ], 422);
        }

        $wasAlreadyPaid = false;
        DB::transaction(function () use ($order, $data, &$wasAlreadyPaid): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status === 'paid') {
                $wasAlreadyPaid = true;

                return;
            }

            $lockedOrder->markPaid($data['transactionId']);
        });

        if (! $wasAlreadyPaid) {
            self::sendTickets($order->fresh());
        }

        return response()->json([
            'redirect' => route('tickets.show', $order->reference),
        ]);
    }

    public function confirmation(Order $order): RedirectResponse
    {
        abort_unless($order->status === 'paid', 404);

        return redirect()->route('tickets.show', $order->reference);
    }

    /**
     * Envoi de l'email. Un souci SMTP ne doit pas bloquer le client déjà payé :
     * l'erreur est journalisée et le client garde l'accès à ses billets sur la page suivante.
     * Appelée après une simulation ou une vérification serveur KkiaPay.
     */
    public static function sendTickets(Order $order): bool
    {
        $order->load('tickets', 'ticketType.event');

        try {
            Mail::to($order->buyer_email)->send(new TicketsPurchased($order));

            return true;
        } catch (Throwable $e) {
            Log::error('Échec envoi email billets', ['order' => $order->reference, 'error' => $e->getMessage()]);
            session()->flash('mail_error', true);

            return false;
        }
    }
}
