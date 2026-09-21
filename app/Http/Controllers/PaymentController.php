<?php

namespace App\Http\Controllers;

use App\Models\Order;

/** Paiement factice, actif uniquement quand BFW_PAYMENT=simulation. */
class PaymentController extends Controller
{
    public function show(Order $order)
    {
        abort_unless(config('bfw.payment') === 'simulation', 404);
        $order->load('ticketType.event');

        return view('payment', compact('order'));
    }

    public function simulate(Order $order)
    {
        abort_unless(config('bfw.payment') === 'simulation', 404);
        $order->markPaid('SIM-'.$order->reference);

        return redirect()->route('tickets.show', $order->reference);
    }
}
