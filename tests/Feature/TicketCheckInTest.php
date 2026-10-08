<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\TicketCheckIn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TicketCheckInTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicket(string $status = 'paid', string $code = 'BFW-ABCDE12345'): Ticket
    {
        $event = Event::create(['slug' => 'defile-scan-'.uniqid(), 'title' => 'Défilé scan test', 'is_active' => true]);
        $type = TicketType::create(['event_id' => $event->id, 'name' => 'VIP', 'price' => 25000, 'capacity' => 50]);
        $order = Order::create([
            'reference' => 'SCAN'.strtoupper(uniqid()),
            'ticket_type_id' => $type->id,
            'buyer_name' => 'Client Scan',
            'buyer_email' => 'scan@example.com',
            'buyer_phone' => '97000002',
            'quantity' => 1,
            'total' => 25000,
            'status' => $status,
        ]);

        return $order->tickets()->create(['code' => $code]);
    }

    public function test_paid_ticket_is_accepted_and_scan_details_are_recorded(): void
    {
        Carbon::setTestNow('2026-10-23 19:42:07');
        $admin = User::factory()->create(['name' => 'Agent Accueil']);
        $ticket = $this->makeTicket();

        $this->actingAs($admin)
            ->postJson(route('admin.check'), ['code' => 'BFW-ABCDE12345 | Commande X | Paiement: PAYE'])
            ->assertOk()
            ->assertJson(['ok' => true, 'status' => 'accepted', 'ticket' => [
                'code' => 'BFW-ABCDE12345',
                'checked_in_at' => '23/10/2026 à 19:42:07',
                'checked_in_by' => 'Agent Accueil',
            ]]);

        $ticket->refresh();
        $this->assertSame('2026-10-23 19:42:07', $ticket->checked_in_at->format('Y-m-d H:i:s'));
        $this->assertSame($admin->id, $ticket->checked_in_by);
        $this->assertNotNull($ticket->check_in_ip);
    }

    public function test_second_scan_is_refused_and_keeps_the_first_scan_time(): void
    {
        $admin = User::factory()->create();
        $other = User::factory()->create(['name' => 'Deuxième agent']);
        $ticket = $this->makeTicket();

        Carbon::setTestNow('2026-10-23 19:00:00');
        $this->actingAs($admin)->postJson(route('admin.check'), ['code' => $ticket->code])->assertJson(['ok' => true]);

        Carbon::setTestNow('2026-10-23 19:05:00');
        $this->actingAs($other)->postJson(route('admin.check'), ['code' => $ticket->code])
            ->assertJson(['ok' => false, 'status' => 'already_used', 'ticket' => ['checked_in_at' => '23/10/2026 à 19:00:00']]);

        $ticket->refresh();
        $this->assertSame('2026-10-23 19:00:00', $ticket->checked_in_at->format('Y-m-d H:i:s'));
        $this->assertSame($admin->id, $ticket->checked_in_by);
    }

    public function test_simultaneous_validations_only_succeed_once(): void
    {
        $admin = User::factory()->create();
        $ticket = $this->makeTicket();
        $service = app(TicketCheckIn::class);

        // Les deux appareils ont lu le billet « non scanné » au même instant :
        // seule l'écriture conditionnelle tranche, et une seule peut réussir.
        $results = [
            $service->check($ticket->code, $admin)['status'],
            $service->check($ticket->code, $admin)['status'],
        ];

        $this->assertSame([TicketCheckIn::ACCEPTED, TicketCheckIn::ALREADY_USED], $results);
        $this->assertSame(1, Ticket::whereNotNull('checked_in_at')->count());
    }

    public function test_ticket_of_unpaid_order_is_refused(): void
    {
        $admin = User::factory()->create();
        $ticket = $this->makeTicket('pending');

        $this->actingAs($admin)->postJson(route('admin.check'), ['code' => $ticket->code])
            ->assertJson(['ok' => false, 'status' => 'unpaid']);

        $this->assertNull($ticket->refresh()->checked_in_at);
    }

    public function test_unknown_ticket_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.check'), ['code' => 'BFW-ZZZZZZZZZZ'])
            ->assertJson(['ok' => false, 'status' => 'unknown']);
    }

    public function test_manual_form_still_works_with_lowercase_code(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs(User::factory()->create())
            ->from(route('admin.scan'))
            ->post(route('admin.check'), ['code' => 'bfw-abcde12345'])
            ->assertRedirect(route('admin.scan'))
            ->assertSessionHas('scan', fn ($scan) => $scan[0] === 'ok');

        $this->assertNotNull($ticket->refresh()->checked_in_at);
    }

    public function test_guest_cannot_scan(): void
    {
        $ticket = $this->makeTicket();

        $this->postJson(route('admin.check'), ['code' => $ticket->code])->assertUnauthorized();
        $this->assertNull($ticket->refresh()->checked_in_at);
    }

    public function test_dashboard_lists_scanned_tickets_with_scan_time(): void
    {
        Carbon::setTestNow('2026-10-23 20:15:33');
        $admin = User::factory()->create(['name' => 'Agent Tableau']);
        $ticket = $this->makeTicket();
        app(TicketCheckIn::class)->check($ticket->code, $admin, '10.0.0.8');

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Billets déjà scannés')
            ->assertSee('BFW-ABCDE12345')
            ->assertSee('23/10/2026 à 20:15:33')
            ->assertSee('Agent Tableau')
            ->assertSee('IP 10.0.0.8');
    }
}
