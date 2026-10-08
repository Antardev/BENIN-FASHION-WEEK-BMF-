<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Services\TicketCheckIn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $events = Event::with(['ticketTypes.orders' => fn ($q) => $q->where('status', 'paid')])
            ->orderBy('position')
            ->get();

        // Pagination des commandes (15 par page)
        $orders = Order::with('ticketType.event')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $checkedIn = Ticket::whereNotNull('checked_in_at')->count();
        $paidTickets = Ticket::whereHas('order', fn ($q) => $q->where('status', 'paid'))->count();

        // Billets déjà scannés, du plus récent au plus ancien (pagination indépendante : ?scans=2)
        $scannedTickets = Ticket::with(['order.ticketType.event', 'checkedInBy'])
            ->whereNotNull('checked_in_at')
            ->orderByDesc('checked_in_at')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'scans')
            ->withQueryString();

        return view('admin.dashboard', compact('events', 'orders', 'checkedIn', 'paidTickets', 'scannedTickets'));
    }

    public function scan()
    {
        $recentScans = Ticket::with(['order.ticketType.event', 'checkedInBy'])
            ->whereNotNull('checked_in_at')
            ->orderByDesc('checked_in_at')
            ->limit(10)
            ->get();

        return view('admin.scan', compact('recentScans'));
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

    public function check(Request $request, TicketCheckIn $checkIn): JsonResponse|RedirectResponse
    {
        $raw = $request->validate(['code' => ['required', 'string', 'max:2000']])['code'];

        $result = $checkIn->check($raw, $request->user(), $request->ip(), $request->userAgent());
        $message = TicketCheckIn::message($result);
        $ticket = $result['ticket'];

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $result['status'] === TicketCheckIn::ACCEPTED,
                'status' => $result['status'],
                'message' => $message,
                'ticket' => $ticket ? [
                    'code' => $ticket->code,
                    'order' => $ticket->order->reference,
                    'order_status' => $ticket->order->status,
                    'buyer' => $ticket->order->buyer_name,
                    'event' => $ticket->order->ticketType->event->title,
                    'type' => $ticket->order->ticketType->name,
                    'checked_in_at' => $ticket->checked_in_at?->format('d/m/Y à H:i:s'),
                    'checked_in_by' => $ticket->checkedInBy?->name,
                ] : null,
            ]);
        }

        $label = $ticket
            ? ' — '.$ticket->order->ticketType->event->title.' · '.$ticket->order->ticketType->name.' · '.$ticket->order->buyer_name
            : '';

        return back()->with('scan', [$result['status'] === TicketCheckIn::ACCEPTED ? 'ok' : 'ko', $message.$label]);
    }
}
