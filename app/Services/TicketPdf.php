<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

/** Construit le PDF des billets : image du billet à gauche, QR code (infos de paiement) à droite. */
class TicketPdf
{
    public function __construct(private TicketQrCode $qr) {}

    /** PDF d'un seul billet (lien « Télécharger » de l'email). */
    public function forTicket(Ticket $ticket): string
    {
        $ticket->loadMissing('order.ticketType.event');

        return $this->render($ticket->order, collect([$ticket]));
    }

    /** PDF de tous les billets d'une commande, un billet par page (pièce jointe de l'email). */
    public function forOrder(Order $order): string
    {
        $order->loadMissing('tickets', 'ticketType.event');

        return $this->render($order, $order->tickets);
    }

    private function render(Order $order, Collection $tickets): string
    {
        $imagePath = $order->ticketType->ticketImagePath();

        return Pdf::loadView('tickets.pdf', [
            'order' => $order,
            'tickets' => $tickets->map(fn (Ticket $t) => [
                'ticket' => $t->setRelation('order', $order),
                'qr' => $this->qr->svgDataUri($t, 400),
            ]),
            'ticketImage' => $imagePath
                ? 'data:'.mime_content_type($imagePath).';base64,'.base64_encode(file_get_contents($imagePath))
                : null,
        ])->setPaper('a4', 'landscape')->output();
    }
}
