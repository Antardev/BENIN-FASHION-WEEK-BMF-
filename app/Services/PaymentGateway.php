<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use LogicException;

/**
 * Point d'entrée unique pour le paiement.
 * En mode "simulation", on renvoie vers une page de paiement factice.
 * En mode KkiaPay, la transaction est vérifiée côté serveur avant de confirmer la commande.
 */
class PaymentGateway
{
    public function checkoutUrl(Order $order): string
    {
        return match (config('bfw.payment')) {
            'simulation' => route('payment.show', $order->reference),
            'kkiapay' => route('payment.show', $order->reference),
            default => abort(500, 'Passerelle de paiement non configurée.'),
        };
    }

    public function verifyTransaction(Order $order, string $transactionId): bool
    {
        $publicKey = config('services.kkiapay.public_key');
        $privateKey = config('services.kkiapay.private_key');
        $secret = config('services.kkiapay.secret');

        if (! is_string($publicKey) || $publicKey === ''
            || ! is_string($privateKey) || $privateKey === ''
            || ! is_string($secret) || $secret === '') {
            throw new LogicException('Les clés KkiaPay ne sont pas configurées.');
        }

        $baseUrl = config('services.kkiapay.sandbox')
            ? 'https://api-sandbox.kkiapay.me'
            : 'https://api.kkiapay.me';

        $transaction = Http::acceptJson()
            ->timeout(10)
            ->withHeaders([
                'X-API-KEY' => $publicKey,
                'X-PRIVATE-KEY' => $privateKey,
                'X-SECRET-KEY' => $secret,
            ])
            ->post($baseUrl.'/api/v1/transactions/status', [
                'transactionId' => $transactionId,
            ])
            ->throw()
            ->json();

        return is_array($transaction)
            && ($transaction['status'] ?? null) === 'SUCCESS'
            && ($transaction['type'] ?? null) === 'DEBIT'
            && isset($transaction['amount'])
            && is_numeric($transaction['amount'])
            && (float) $transaction['amount'] === (float) $order->total
            && ($transaction['partnerId'] ?? null) === $order->reference
            && ($transaction['transactionId'] ?? null) === $transactionId;
    }
}
