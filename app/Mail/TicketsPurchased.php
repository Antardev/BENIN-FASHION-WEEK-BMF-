<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\TicketPdf;
use App\Services\TicketQrCode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class TicketsPurchased extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing('tickets', 'ticketType.event');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vos billets Benin Fashion Week · '.$this->order->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tickets-purchased',
            with: [
                'order' => $this->order,
                'qr' => app(TicketQrCode::class),
                'ticketImagePath' => $this->order->ticketType->ticketImagePath(),
                'downloadAllUrl' => URL::signedRoute('orders.tickets.download', $this->order->reference),
                'downloadUrls' => $this->order->tickets->mapWithKeys(fn ($t) => [
                    $t->id => URL::signedRoute('tickets.download', $t->id),
                ]),
            ],
        );
    }

    /** Les billets en PDF sont joints au mail : le client les a même hors connexion. */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => app(TicketPdf::class)->forOrder($this->order),
                'billets-BFW-'.$this->order->reference.'.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
