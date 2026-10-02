<?php

return [
    // "simulation" : bouton de paiement factice (développement).
    // "kkiapay" : widget KKiaPay et vérification serveur de la transaction.
    'payment' => strtolower((string) env('BFW_PAYMENT', 'simulation')),
    'contact_email' => env('BFW_CONTACT_EMAIL', 'beninfashionweek@gmail.com'),
];
