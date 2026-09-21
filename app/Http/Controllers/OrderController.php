<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\TicketType;
use App\Services\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function create(TicketType $ticketType)
    {
        $ticketType->load('event');

        return view('checkout', compact('ticketType'));
    }

    public function store(Request $request, TicketType $ticketType, PaymentGateway $gateway)
    {
        $data = $request->validate([
            'buyer_name' => 'required|string|max:120',
            'buyer_email' => 'required|email|max:160',
            'buyer_phone' => 'required|string|max:30',
            'quantity' => 'required|integer|min:1|max:10',
        ]);

        $order = DB::transaction(function () use ($ticketType, $data) {
            $type = TicketType::lockForUpdate()->findOrFail($ticketType->id);
            if ($type->remaining() < $data['quantity']) {
                return null;
            }

            return Order::create($data + [
                'reference' => strtoupper(Str::random(8)),
                'ticket_type_id' => $type->id,
                'total' => $type->price * $data['quantity'],
            ]);
        });

        if (! $order) {
            return back()->withInput()->withErrors([
                'quantity' => 'Il ne reste pas assez de places pour cette quantité.',
            ]);
        }

        return redirect($gateway->checkoutUrl($order));
    }
}
