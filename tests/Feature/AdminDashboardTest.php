<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_see_all_ticket_purchase_information(): void
    {
        $user = User::factory()->create();
        $event = Event::create([
            'slug' => 'defile-dashboard-test',
            'title' => 'Défilé dashboard test',
            'starts_at' => now()->addDay(),
            'is_active' => true,
        ]);
        $ticketType = TicketType::create([
            'event_id' => $event->id,
            'name' => 'VIP',
            'price' => 25000,
            'capacity' => 50,
        ]);
        Order::create([
            'reference' => 'DASHBOARD123',
            'ticket_type_id' => $ticketType->id,
            'buyer_name' => 'Client Dashboard',
            'buyer_email' => 'dashboard@example.com',
            'buyer_phone' => '97000001',
            'quantity' => 2,
            'total' => 50000,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertSee('DASHBOARD123');
        $response->assertSee('Client Dashboard');
        $response->assertSee('dashboard@example.com');
        $response->assertSee('Défilé dashboard test');
        $response->assertSee('50 000 FCFA');
        $response->assertSee('Paid');
    }
}
