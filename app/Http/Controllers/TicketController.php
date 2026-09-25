<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Ticket;
use App\Services\TicketPdf;
use App\Services\TicketQrCode;

class TicketController extends Controller
{
    /** Page « Vos billets » affichée après le paiement. */
    public function show(Order $order, TicketQrCode $qr)
    {
        abort_unless($order->status === 'paid', 404);
        $order->load('tickets', 'ticketType.event');

        return view('tickets', compact('order', 'qr'));
    }

    /** Téléchargement d'un billet en PDF (lien signé envoyé par email). */
    public function download(Ticket $ticket, TicketPdf $pdf)
    {
        $ticket->load('order.ticketType.event');
        abort_unless($ticket->order->status === 'paid', 404);

        return response($pdf->forTicket($ticket), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="billet-'.$ticket->code.'.pdf"',
        ]);
    }

    /** Téléchargement de tous les billets de la commande en un seul PDF (lien signé). */
    public function downloadOrder(Order $order, TicketPdf $pdf)
    {
        abort_unless($order->status === 'paid', 404);

        return response($pdf->forOrder($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="billets-'.$order->reference.'.pdf"',
        ]);
    }
}
