<?php

namespace App\Services;

use App\Models\Ticket;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Génère le QR code d'un billet SANS l'extension imagick
 * (c'est elle qui faisait planter le PDF : "You need to install the imagick extension").
 *
 *  - svg()  : aucune extension requise → utilisé dans le PDF (dompdf) et sur la page web.
 *  - png()  : encodeur PHP pur (aucune extension) → utilisé dans l'email,
 *             car Gmail / Outlook n'affichent pas les images SVG.
 */
class TicketQrCode
{
    /**
     * Contenu du QR : le code billet en premier (lu par le scanner d'entrée),
     * puis les informations du paiement, sur une seule ligne lisible par n'importe quel téléphone.
     */
    public function payload(Ticket $ticket): string
    {
        $order = $ticket->order;
        $type = $order->ticketType;
        $paidAt = $order->updated_at ?? $order->created_at;

        return implode(' | ', array_filter([
            $ticket->code,
            'Commande '.$order->reference,
            'Acheteur: '.$order->buyer_name,
            'Evenement: '.$type->event->title,
            'Billet: '.$type->name,
            'Prix: '.number_format($type->price, 0, ',', ' ').' FCFA',
            'Total commande: '.number_format($order->total, 0, ',', ' ').' FCFA ('.$order->quantity.' place(s))',
            'Paiement: '.($order->status === 'paid' ? 'PAYE' : strtoupper($order->status)),
            $order->payment_ref ? 'Ref paiement: '.$order->payment_ref : null,
            'Date: '.$paidAt->format('d/m/Y H:i'),
        ]));
    }

    public function svg(Ticket $ticket, int $size = 300): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd));

        return $writer->writeString($this->payload($ticket), 'UTF-8', ErrorCorrectionLevel::M());
    }

    /** Data-URI SVG pour une balise <img> (dompdf n'interprète pas le <svg> inline). */
    public function svgDataUri(Ticket $ticket, int $size = 300): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($ticket, $size));
    }

    /**
     * QR code en PNG pour l'email (Gmail / Outlook n'affichent pas le SVG).
     * Encodeur PNG écrit en PHP pur : ne dépend NI de GD NI d'imagick,
     * le QR s'affiche donc dans la boîte mail quelle que soit la config de PHP.
     */
    public function png(Ticket $ticket, int $size = 300): string
    {
        $matrix = Encoder::encode($this->payload($ticket), ErrorCorrectionLevel::M(), 'UTF-8')->getMatrix();
        $modules = $matrix->getWidth();
        $quiet = 4; // marge blanche réglementaire (4 modules) pour une lecture fiable
        $scale = max(1, intdiv($size, $modules + 2 * $quiet));
        $pixels = ($modules + 2 * $quiet) * $scale;

        $raw = '';
        for ($y = 0; $y < $pixels; $y++) {
            $my = intdiv($y, $scale) - $quiet;
            $line = "\x00"; // filtre PNG « None »
            for ($x = 0; $x < $pixels; $x++) {
                $mx = intdiv($x, $scale) - $quiet;
                $dark = $mx >= 0 && $my >= 0 && $mx < $modules && $my < $modules && $matrix->get($mx, $my) === 1;
                $line .= $dark ? "\x00" : "\xFF";
            }
            $raw .= $line;
        }

        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data
            .pack('N', crc32($type.$data));

        return "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $pixels, $pixels, 8, 0, 0, 0, 0)) // 8 bits, niveaux de gris
            .$chunk('IDAT', gzcompress($raw, 9))
            .$chunk('IEND', '');
    }
}
