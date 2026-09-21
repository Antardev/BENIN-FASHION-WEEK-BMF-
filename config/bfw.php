<?php

return [
    // "simulation" : bouton de paiement factice (développement).
    // "fedapay" | "kkiapay" : à brancher dans App\Services\PaymentGateway.
    'payment' => env('BFW_PAYMENT', 'simulation'),
    'contact_email' => env('BFW_CONTACT_EMAIL', 'beninfashionweek@gmail.com'),
];
