<!doctype html>
<html lang="fr">
<body style="margin:0;background:#f4f0e8;color:#171315;font-family:Arial,sans-serif;line-height:1.5">
    <div style="max-width:680px;margin:0 auto;padding:32px 16px">
        <div style="background:#171315;color:#f4f0e8;padding:24px;border-bottom:4px solid #d8a83e">
            <h1 style="margin:0;font-family:Georgia,serif;font-size:28px">Benin Fashion Week</h1>
            <p style="margin:8px 0 0;color:#f2c96b">Vos billets sont confirmés</p>
        </div>

        <div style="background:#fff;padding:24px">
            <p>Bonjour {{ $order->buyer_name }},</p>
            <p>Merci pour votre réservation. Votre paiement de <strong>{{ $order->total_label }}</strong>
                pour la commande <strong>{{ $order->reference }}</strong> est confirmé.</p>
            <p><strong>{{ $order->ticketType->event->title }}</strong><br>
                {{ $order->ticketType->name }} · {{ $order->quantity }} place(s)<br>
                {{ $order->ticketType->event->starts_at
                    ? $order->ticketType->event->starts_at->translatedFormat('j F Y, H\hi')
                    : 'Date à confirmer' }}
            </p>

            <p style="text-align:center;margin:24px 0">
                <a href="{{ $downloadAllUrl }}"
                   style="display:inline-block;background:#d8a83e;color:#171315;padding:14px 26px;
                          text-decoration:none;font-weight:bold;border-radius:4px;font-size:15px">
                    Télécharger mes billets (PDF)
                </a><br>
                <span style="font-size:12px;color:#5d5550">Les billets sont aussi joints à cet email en PDF.</span>
            </p>

            @php $ticketImageCid = $ticketImagePath ? $message->embed($ticketImagePath) : null; @endphp
            @foreach($order->tickets as $ticket)
                @php $qrPng = $qr->png($ticket, 260); @endphp
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                       style="margin:20px 0;border:1px solid #d4c9bc;border-left:5px solid #c94b3c">
                    <tr>
                        <td style="width:62%;padding:12px;vertical-align:middle">
                            @if($ticketImageCid)
                                <img src="{{ $ticketImageCid }}"
                                     alt="Billet {{ $order->ticketType->name }}"
                                     style="display:block;width:100%;max-width:380px;height:auto">
                            @else
                                <div style="font-weight:bold;color:#5d5550">{{ $order->ticketType->name }}</div>
                                <div style="font-size:13px;color:#5d5550">Billet électronique</div>
                            @endif
                        </td>
                        <td style="width:38%;padding:12px;text-align:center;vertical-align:middle">
                            @if($qrPng)
                                <img src="{{ $message->embedData($qrPng, 'qr-'.$ticket->code.'.png', 'image/png') }}"
                                     alt="QR code {{ $ticket->code }}" width="140" height="140"
                                     style="display:block;margin:0 auto;width:140px;height:140px">
                            @endif
                            <div style="font-family:monospace;font-weight:bold;font-size:14px;margin-top:6px">{{ $ticket->code }}</div>
                            <a href="{{ $downloadUrls[$ticket->id] }}"
                               style="display:inline-block;margin-top:8px;color:#c94b3c;font-size:13px;font-weight:bold">
                                Télécharger ce billet
                            </a>
                        </td>
                    </tr>
                </table>
            @endforeach

            <p style="color:#5d5550;font-size:14px">
                Présentez le QR code de chaque billet à l'entrée (sur téléphone ou imprimé).
                Conservez cet email jusqu'à l'événement.
            </p>
        </div>
    </div>
</body>
</html>
