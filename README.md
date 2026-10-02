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
- Administration : `/admin` (identifiants à définir dans les variables `ADMIN_EMAIL` et `ADMIN_PASSWORD` de l'environnement)
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
  Pour KkiaPay, définissez `BFW_PAYMENT=kkiapay` et renseignez `KKIAPAY_PUBLIC_KEY`,
  `KKIAPAY_PRIVATE_KEY` et `KKIAPAY_SECRET` dans l'environnement. Gardez les deux dernières
  clés côté serveur uniquement. Utilisez
  `KKIAPAY_SANDBOX=true` pour les tests. La commande n'est confirmée et les billets ne sont
  émis qu'après vérification serveur du statut, du montant et de la référence de commande.
- Envoi du billet par e-mail : `TicketsPurchased`, envoyé après confirmation du paiement.
