# Billetterie Benin Fashion Week (Laravel + Bootstrap 5)

Événements : Défilé Haute Couture & Distinctions · Fashion Brunch · Concours Jeunes Talents.

## Installation (Laravel 11 ou 12, PHP 8.2+)

```bash
composer create-project laravel/laravel bfw
cd bfw
composer require simplesoftwareio/simple-qrcode

# Copiez par-dessus le projet le contenu de ce dossier :
# app/ config/bfw.php database/ resources/views/ routes/web.php public/css public/images

# .env : configurez la base de données (MySQL ou SQLite), puis :
php artisan migrate --seed
php artisan serve
```

- Site public : `/`
- Administration : `/admin` (connexion HTTP : admin@beninfashionweek.com / changez-moi)
- Contrôle d'entrée : `/admin/scan`

## Images
Les photos sont dans `public/images/` (hero.jpg, defile.jpg, talents-1.jpg, talents-2.jpg).
Pour en ajouter : déposez le fichier dans ce dossier puis listez son nom dans la clé `images` de l'événement, dans `DatabaseSeeder.php` (ou dans la colonne `images` en base).
Le Fashion Brunch n'a pas encore de photo : un espace réservé s'affiche.
Bootstrap est chargé par CDN : aucune étape `npm` nécessaire.

## À personnaliser
- `database/seeders/DatabaseSeeder.php` : prix, capacités, dates (`starts_at`), lieux (`venue`) — valeurs provisoires.
- Mot de passe admin (changez-le immédiatement).
- Paiement : `App\Services\PaymentGateway` — mode `BFW_PAYMENT=simulation` par défaut.
  Pour la production, brancher FedaPay ou Kkiapay (MTN / Moov Mobile Money) et appeler
  `$order->markPaid($transactionId)` depuis le webhook de confirmation.
- Envoi du billet par e-mail : ajouter une `Mailable` appelée depuis `Order::markPaid()`.
