<?php

namespace App\Services;

use App\Models\Order;

/**
 * Point d'entrée unique pour le paiement.
 * En mode "simulation", on renvoie vers une page de paiement factice.
 * Pour la production, branchez ici FedaPay ou Kkiapay (Mobile Money MTN / Moov, cartes),
 * puis marquez la commande payée dans le webhook via $order->markPaid($transactionId).
 */
class PaymentGateway
{
    public function checkoutUrl(Order $order): string
    {
        return match (config('bfw.payment')) {
            'simulation' => route('payment.show', $order->reference),
            // 'fedapay' => ...créer la transaction et retourner l'URL de paiement,
            default => abort(500, 'Passerelle de paiement non configurée.'),
        };
    }
}
