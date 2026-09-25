<?php

namespace Tests\Feature;

use App\Mail\TicketsPurchased;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentTicketEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_ticket_email_is_sent_after_successful_payment(): void
    {
        Mail::fake();

        $event = Event::create([
            'slug' => 'defile-test',
            'title' => 'Défilé test',
            'starts_at' => now()->addDay(),
            'is_active' => true,
        ]);
        $ticketType = TicketType::create([
            'event_id' => $event->id,
            'name' => 'Standard',
            'price' => 15000,
            'capacity' => 50,
        ]);
        $order = Order::create([
            'reference' => 'TEST1234',
            'ticket_type_id' => $ticketType->id,
            'buyer_name' => 'Acheteur Test',
            'buyer_email' => 'buyer@example.com',
            'buyer_phone' => '97000000',
            'quantity' => 2,
            'total' => 30000,
        ]);

        $response = $this->post(route('payment.simulate', $order->reference));

        $response->assertRedirect(route('tickets.show', $order->reference));
        $this->assertDatabaseHas('orders', ['reference' => 'TEST1234', 'status' => 'paid']);
        $this->assertDatabaseCount('tickets', 2);
        Mail::assertSent(TicketsPurchased::class, function (TicketsPurchased $mail) {
            return $mail->hasTo('buyer@example.com')
                && $mail->attachments()[0]->as === 'billets-BFW-TEST1234.pdf'
                && str_contains($mail->render(), 'Télécharger mes billets (PDF)');
        });
    }

    public function test_the_ticket_pdf_can_be_downloaded_from_the_signed_link(): void
    {
        Mail::fake();
        $event = Event::firstOrCreate(['slug' => 'defile-haute-couture-distinctions'], ['title' => 'Défilé', 'is_active' => true]);
        $type = TicketType::firstOrCreate(['event_id' => $event->id, 'name' => 'VIP'], ['price' => 25000, 'capacity' => 10]);
        $order = Order::create([
            'reference' => 'PDF12345', 'ticket_type_id' => $type->id, 'buyer_name' => 'Awa',
            'buyer_email' => 'awa@example.com', 'buyer_phone' => '97000000', 'quantity' => 1, 'total' => 25000,
        ]);
        $order->markPaid('SIM-PDF12345');
        $ticket = $order->tickets()->first();

        $this->get(route('tickets.download', $ticket->id))->assertForbidden();

        $response = $this->get(\Illuminate\Support\Facades\URL::signedRoute('tickets.download', $ticket->id));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->get(\Illuminate\Support\Facades\URL::signedRoute('orders.tickets.download', $order->reference))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
}
