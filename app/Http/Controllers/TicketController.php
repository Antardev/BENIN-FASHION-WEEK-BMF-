<?php

namespace App\Http\Controllers;

use App\Models\Order;

class TicketController extends Controller
{
    public function show(Order $order)
    {
        abort_unless($order->status === 'paid', 404);
        $order->load('tickets', 'ticketType.event');

        return view('tickets', compact('order'));
    }
}
