<?php

namespace App\Http\Controllers;

use App\Mail\TicketsPurchased;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Paiement factice, actif uniquement quand BFW_PAYMENT=simulation. */
class PaymentController extends Controller
{
    public function show(Order $order)
    {
        abort_unless(config('bfw.payment') === 'simulation', 404);

        // Déjà payée : on n'affiche plus le bouton de paiement.
        if ($order->status === 'paid') {
            return redirect()->route('tickets.show', $order->reference);
        }

        $order->load('ticketType.event');

        return view('payment', compact('order'));
    }

    public function simulate(Order $order)
    {
        abort_unless(config('bfw.payment') === 'simulation', 404);

        $wasAlreadyPaid = $order->status === 'paid';
        $order->markPaid('SIM-'.$order->reference);

        if (! $wasAlreadyPaid) {
            $this->sendTickets($order);
        }

        return redirect()->route('tickets.show', $order->reference);
    }

    public function confirmation(Order $order)
    {
        abort_unless($order->status === 'paid', 404);

        return redirect()->route('tickets.show', $order->reference);
    }

    /**
     * Envoi de l'email. Un souci SMTP ne doit pas bloquer le client déjà payé :
     * l'erreur est journalisée et le client garde l'accès à ses billets sur la page suivante.
     * À réutiliser dans le futur webhook FedaPay / Kkiapay.
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
