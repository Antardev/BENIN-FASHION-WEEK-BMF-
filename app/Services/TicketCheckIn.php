<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Contrôle d'entrée d'un billet.
 *
 * Règles :
 *  - seuls les billets rattachés à une commande au statut « paid » sont acceptés ;
 *  - un billet ne peut être validé qu'une seule fois, y compris si deux appareils
 *    le scannent au même instant.
 *
 * L'unicité repose sur une seule requête UPDATE conditionnelle
 * (« … WHERE code = ? AND checked_in_at IS NULL AND commande payée »).
 * La base verrouille la ligne pendant l'écriture : si deux scans arrivent en même
 * temps, le second attend la fin du premier, relit la ligne, constate que
 * checked_in_at est renseigné et ne modifie rien. Une seule requête obtient
 * donc « 1 ligne modifiée » : c'est elle, et elle seule, qui valide l'entrée.
 */
class TicketCheckIn
{
    public const ACCEPTED = 'accepted';

    public const ALREADY_USED = 'already_used';

    public const UNPAID = 'unpaid';

    public const UNKNOWN = 'unknown';

    /**
     * Le QR contient le code billet suivi des informations de paiement :
     * on n'en garde que le code BFW-XXXXXXXXXX. Une saisie manuelle en minuscules est acceptée.
     */
    public static function extractCode(string $raw): string
    {
        $raw = strtoupper(trim($raw));

        return preg_match('/BFW-[A-Z0-9]{10}/', $raw, $m) ? $m[0] : Str::limit($raw, 64, '');
    }

    /**
     * @return array{status: string, code: string, ticket: ?Ticket}
     */
    public function check(string $raw, User $admin, ?string $ip = null, ?string $device = null): array
    {
        $code = self::extractCode($raw);

        // Deux tentatives au plus : la seconde ne sert que si la commande est passée
        // au statut « payé » exactement entre l'UPDATE et la relecture.
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $validated = Ticket::query()
                ->where('code', $code)
                ->whereNull('checked_in_at')
                ->whereHas('order', fn ($q) => $q->where('status', 'paid'))
                ->update([
                    'checked_in_at' => now(),
                    'checked_in_by' => $admin->id,
                    'check_in_ip' => $ip !== null ? Str::limit($ip, 45, '') : null,
                    'check_in_device' => $device !== null ? Str::limit($device, 255, '') : null,
                ]);

            $ticket = Ticket::with(['order.ticketType.event', 'checkedInBy'])->where('code', $code)->first();

            if (! $ticket) {
                return ['status' => self::UNKNOWN, 'code' => $code, 'ticket' => null];
            }
            if ($validated === 1) {
                return ['status' => self::ACCEPTED, 'code' => $code, 'ticket' => $ticket];
            }
            if ($ticket->order?->status !== 'paid') {
                return ['status' => self::UNPAID, 'code' => $code, 'ticket' => $ticket];
            }
            if ($ticket->checked_in_at !== null) {
                return ['status' => self::ALREADY_USED, 'code' => $code, 'ticket' => $ticket];
            }
        }

        return ['status' => self::ALREADY_USED, 'code' => $code, 'ticket' => $ticket];
    }

    /** Message affiché à l'agent d'accueil. */
    public static function message(array $result): string
    {
        $ticket = $result['ticket'];

        return match ($result['status']) {
            self::ACCEPTED => 'Entrée validée',
            self::ALREADY_USED => 'Billet déjà utilisé le '.$ticket->checked_in_at->format('d/m/Y à H:i:s')
                .($ticket->checkedInBy ? ' (scanné par '.$ticket->checkedInBy->name.')' : ''),
            self::UNPAID => 'Billet refusé : la commande '.$ticket->order->reference.' n’est pas payée'
                .' (statut : '.$ticket->order->status.')',
            default => 'Billet inconnu : '.($result['code'] !== '' ? $result['code'] : 'code illisible'),
        };
    }
}
