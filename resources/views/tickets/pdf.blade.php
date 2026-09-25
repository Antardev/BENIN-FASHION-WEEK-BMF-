<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Billets {{ $order->reference }}</title>
    <style>
        @page { margin: 22mm 15mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #171315; margin: 0; font-size: 10pt; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }

        .brand { font-size: 16pt; font-weight: bold; margin: 0 0 2mm; }
        .brand small { font-size: 9pt; font-weight: normal; color: #5d5550; }

        /* Billet (gauche) + talon QR (droite) */
        .ticket { width: 100%; border-collapse: collapse; border: 1px solid #d4c9bc; }
        .ticket td { vertical-align: middle; }
        .cell-image { width: 70%; padding: 0; background: #171315; }
        .cell-image img { width: 100%; display: block; }
        .fallback { padding: 18mm 10mm; color: #f4f0e8; text-align: center; }
        .fallback strong { display: block; font-size: 18pt; color: #f2c96b; }
        .cell-qr { width: 30%; padding: 4mm; text-align: center;
                   border-left: 2px dashed #d4c9bc; background: #fff; }
        .cell-qr img { width: 52mm; height: 52mm; }
        .code { font-family: DejaVu Sans Mono, monospace; font-weight: bold; font-size: 11pt; margin-top: 2mm; }
        .hint { font-size: 7.5pt; color: #5d5550; margin-top: 1mm; }

        .details { width: 100%; margin-top: 7mm; border-collapse: collapse; }
        .details td { padding: 1.6mm 3mm; border-bottom: 1px solid #ebe4da; }
        .details td.label { color: #5d5550; width: 32%; }
        .paid { color: #1e7a3c; font-weight: bold; }
        .footer { margin-top: 6mm; font-size: 8pt; color: #5d5550; }
    </style>
</head>
<body>
@foreach($tickets as $i => $item)
    @php $ticket = $item['ticket']; $type = $order->ticketType; @endphp
    <div class="page">
        <p class="brand">Benin Fashion Week
            <small>· Billet {{ $i + 1 }}/{{ count($tickets) }} · Commande {{ $order->reference }}</small></p>

        <table class="ticket">
            <tr>
                <td class="cell-image">
                    @if($ticketImage)
                        <img src="{{ $ticketImage }}" alt="Billet {{ $type->name }}">
                    @else
                        <div class="fallback">
                            <strong>{{ $type->event->title }}</strong>
                            {{ $type->name }} · Billet électronique
                        </div>
                    @endif
                </td>
                <td class="cell-qr">
                    <img src="{{ $item['qr'] }}" alt="QR code">
                    <div class="code">{{ $ticket->code }}</div>
                    <div class="hint">QR code : billet + informations de paiement</div>
                </td>
            </tr>
        </table>

        <table class="details">
            <tr><td class="label">Événement</td><td>{{ $type->event->title }}
                @if($type->event->starts_at) · {{ $type->event->starts_at->translatedFormat('j F Y, H\hi') }} @endif
                @if($type->event->venue) · {{ $type->event->venue }} @endif</td></tr>
            <tr><td class="label">Type de billet</td><td>{{ $type->name }} · {{ $type->price_label }}</td></tr>
            <tr><td class="label">Titulaire</td><td>{{ $order->buyer_name }} · {{ $order->buyer_email }}</td></tr>
            <tr><td class="label">Paiement</td><td><span class="paid">Payé</span> · {{ $order->total_label }}
                pour {{ $order->quantity }} place(s)
                @if($order->payment_ref) · Réf. {{ $order->payment_ref }} @endif
                · {{ ($order->updated_at ?? $order->created_at)->format('d/m/Y H:i') }}</td></tr>
        </table>

        <p class="footer">Présentez ce QR code à l'entrée. Chaque billet n'est valable qu'une seule fois.</p>
    </div>
@endforeach
</body>
</html>
