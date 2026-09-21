<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $events = [
            [
                'slug' => 'defile-haute-couture-distinctions',
                'images' => ['defile.jpg'],
                'title' => 'Défilé Haute Couture & Distinctions',
                'tagline' => 'La soirée phare de la semaine.',
                'description' => 'Les créateurs présentent leurs collections haute couture, suivies de la remise des distinctions.',
                'tickets' => [
                    ['Standard', 'Placement en tribune', 15000, 300],
                    ['VIP', 'Placement privilégié et accueil dédié', 25000, 100],
                ],
            ],
            [
                'slug' => 'fashion-brunch',
                'images' => [],
                'title' => 'Fashion Brunch',
                'tagline' => 'Un brunch entre créateurs, invités et passionnés.',
                'description' => 'Un moment convivial pour échanger avec les créateurs autour d\'un brunch.',
                'tickets' => [
                    ['Place brunch', 'Une place à table', 15000, 120],
                ],
            ],
            [
                'slug' => 'concours-jeunes-talents',
                'images' => ['talents-1.jpg', 'talents-2.jpg'],
                'title' => 'Concours Jeunes Talents',
                'tagline' => 'La nouvelle génération de créateurs béninois.',
                'description' => 'De jeunes stylistes présentent leurs créations devant un jury et le public.',
                'tickets' => [
                    ['Standard', 'Accès à la salle', 5000, 400],
                    ['VIP', 'Accès privilégié et placement réservé', 10000, 100],
                ],
            ],
        ];

        foreach ($events as $position => $eventData) {
            $ticketTypes = $eventData['tickets'];
            unset($eventData['tickets']);

            $eventId = DB::table('events')->where('slug', $eventData['slug'])->value('id');

            if (! $eventId) {
                $eventId = DB::table('events')->insertGetId([
                    ...$eventData,
                    'images' => json_encode($eventData['images']),
                    'position' => $position,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($ticketTypes as [$name, $description, $price, $capacity]) {
                if (! DB::table('ticket_types')->where('event_id', $eventId)->where('name', $name)->exists()) {
                    DB::table('ticket_types')->insert([
                        'event_id' => $eventId,
                        'name' => $name,
                        'description' => $description,
                        'price' => $price,
                        'capacity' => $capacity,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('events')
            ->whereIn('slug', [
                'defile-haute-couture-distinctions',
                'fashion-brunch',
                'concours-jeunes-talents',
            ])
            ->delete();
    }
};
