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
 *  - png()  : via GD (extension quasi toujours active) → utilisé dans l'email,
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

    /** PNG binaire, ou null si ni GD ni imagick ne sont disponibles. */
    public function png(Ticket $ticket, int $size = 300): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $matrix = Encoder::encode($this->payload($ticket), ErrorCorrectionLevel::M(), 'UTF-8')->getMatrix();
        $modules = $matrix->getWidth();
        $quiet = 2;
        $scale = max(1, intdiv($size, $modules + 2 * $quiet));
        $pixels = ($modules + 2 * $quiet) * $scale;

        $image = imagecreatetruecolor($pixels, $pixels);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $px = ($x + $quiet) * $scale;
                    $py = ($y + $quiet) * $scale;
                    imagefilledrectangle($image, $px, $py, $px + $scale - 1, $py + $scale - 1, $black);
                }
            }
        }

        ob_start();
        imagepng($image);
        imagedestroy($image);

        return ob_get_clean();
    }
}
