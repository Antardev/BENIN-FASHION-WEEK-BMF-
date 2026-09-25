<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $events = Event::with(['ticketTypes.orders' => fn ($q) => $q->where('status', 'paid')])->orderBy('position')->get();
        $orders = Order::with('ticketType.event')->latest()->get();
        $checkedIn = Ticket::whereNotNull('checked_in_at')->count();

        return view('admin.dashboard', compact('events', 'orders', 'checkedIn'));
    }

    public function scan()
    {
        return view('admin.scan');
    }

    public function updateCapacity(Request $request, TicketType $ticketType): RedirectResponse
    {
        $sold = $ticketType->sold();
        $capacity = $request->validate([
            'capacity' => ['required', 'integer', 'min:'.$sold, 'max:100000'],
        ])['capacity'];

        $ticketType->update(['capacity' => $capacity]);

        return back()->with('capacity_updated', 'Capacité de « '.$ticketType->name.' » mise à jour.');
    }

    public function check(Request $request)
    {
        $code = strtoupper(trim($request->validate(['code' => 'required|string'])['code']));
        // Le QR contient désormais le code + les infos de paiement : on extrait le code BFW-XXXXXXXXXX.
        if (preg_match('/BFW-[A-Z0-9]{10}/', $code, $m)) {
            $code = $m[0];
        }
        $ticket = Ticket::with('order.ticketType.event')->where('code', $code)->first();

        if (! $ticket) {
            return back()->with('scan', ['ko', 'Billet inconnu.']);
        }
        $label = $ticket->order->ticketType->event->title.' · '.$ticket->order->ticketType->name
            .' · '.$ticket->order->buyer_name;

        if ($ticket->checked_in_at) {
            return back()->with('scan', ['ko', 'Déjà utilisé le '.$ticket->checked_in_at->format('d/m à H:i').' — '.$label]);
        }
        $ticket->update(['checked_in_at' => now()]);

        return back()->with('scan', ['ok', 'Entrée validée — '.$label]);
    }
}
