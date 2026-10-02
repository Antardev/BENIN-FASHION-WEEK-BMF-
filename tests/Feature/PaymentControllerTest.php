<?php

namespace Tests\Feature;

use App\Mail\TicketsPurchased;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_kkiapay_payment_page_uses_the_order_total_and_reference(): void
    {
        $order = $this->makeOrder();
        config([
            'bfw.payment' => 'kkiapay',
            'services.kkiapay.public_key' => 'public-test-key',
            'services.kkiapay.sandbox' => true,
        ]);

        $this->get(route('payment.show', $order->reference))
            ->assertSee('openKkiapayWidget')
            ->assertSee('amount: 30000', false)
            ->assertSee('partnerId: "BFW12345"', false)
            ->assertSee('public-test-key')
            ->assertSee('Réessayer la vérification')
            ->assertSee('sans repayer')
            ->assertSee('Vérifier mon paiement');
    }

    public function test_successful_server_verification_marks_the_order_paid_and_sends_tickets(): void
    {
        $order = $this->makeOrder();
        $this->configureKkiapay();
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.kkiapay.me/api/v1/transactions/status' => Http::response([
                'status' => 'SUCCESS',
                'type' => 'DEBIT',
                'amount' => 30000,
                'partnerId' => $order->reference,
                'transactionId' => 'txn-123',
            ]),
        ]);
        Mail::fake();

        $response = $this->postJson(route('payment.kkiapay.verify', $order->reference), [
            'transactionId' => 'txn-123',
        ]);

        $response->assertOk()
            ->assertJsonPath('redirect', route('tickets.show', $order->reference));

        $this->get($response->json('redirect'))
            ->assertOk()
            ->assertSee('Vos billets')
            ->assertSee('buyer@example.com')
            ->assertSee('Télécharger tous mes billets (PDF)');

        $this->assertDatabaseHas('orders', [
            'reference' => $order->reference,
            'status' => 'paid',
            'payment_ref' => 'txn-123',
        ]);
        $this->assertDatabaseCount('tickets', 2);
        Mail::assertSent(TicketsPurchased::class, fn (TicketsPurchased $mail): bool => $mail->hasTo('buyer@example.com'));
        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://api-sandbox.kkiapay.me/api/v1/transactions/status'
            && $request->hasHeader('X-API-KEY', 'public-test-key')
            && $request->hasHeader('X-PRIVATE-KEY', 'private-test-key')
            && $request->hasHeader('X-SECRET-KEY', 'secret-test-key')
            && $request['transactionId'] === 'txn-123');
    }

    #[DataProvider('unmatchedTransactions')]
    public function test_unmatched_provider_transactions_do_not_issue_tickets(array $providerResponse): void
    {
        $order = $this->makeOrder();
        $this->configureKkiapay();
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.kkiapay.me/api/v1/transactions/status' => Http::response($providerResponse),
        ]);
        Mail::fake();

        $this->postJson(route('payment.kkiapay.verify', $order->reference), [
            'transactionId' => 'txn-invalid',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Le paiement n’a pas pu être vérifié.');

        $this->assertDatabaseHas('orders', [
            'reference' => $order->reference,
            'status' => 'pending',
            'payment_ref' => null,
        ]);
        $this->assertDatabaseCount('tickets', 0);
        Mail::assertNothingSent();
    }

    public static function unmatchedTransactions(): array
    {
        return [
            'failed status' => [[
                'status' => 'FAILED',
                'type' => 'DEBIT',
                'amount' => 30000,
                'partnerId' => 'BFW12345',
                'transactionId' => 'txn-invalid',
            ]],
            'wrong amount' => [[
                'status' => 'SUCCESS',
                'type' => 'DEBIT',
                'amount' => 1,
                'partnerId' => 'BFW12345',
                'transactionId' => 'txn-invalid',
            ]],
            'wrong order reference' => [[
                'status' => 'SUCCESS',
                'type' => 'DEBIT',
                'amount' => 30000,
                'partnerId' => 'OTHER123',
                'transactionId' => 'txn-invalid',
            ]],
            'different transaction identifier' => [[
                'status' => 'SUCCESS',
                'type' => 'DEBIT',
                'amount' => 30000,
                'partnerId' => 'BFW12345',
                'transactionId' => 'another-transaction',
            ]],
        ];
    }

    public function test_transaction_identifier_is_required_before_contacting_kkiapay(): void
    {
        $order = $this->makeOrder();
        $this->configureKkiapay();
        Http::preventStrayRequests();

        $this->postJson(route('payment.kkiapay.verify', $order->reference))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('transactionId');

        Http::assertNothingSent();
        $this->assertDatabaseHas('orders', [
            'reference' => $order->reference,
            'status' => 'pending',
        ]);
    }

    private function configureKkiapay(): void
    {
        config([
            'bfw.payment' => 'kkiapay',
            'services.kkiapay.public_key' => 'public-test-key',
            'services.kkiapay.private_key' => 'private-test-key',
            'services.kkiapay.secret' => 'secret-test-key',
            'services.kkiapay.sandbox' => true,
        ]);
    }

    private function makeOrder(): Order
    {
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

        return Order::create([
            'reference' => 'BFW12345',
            'ticket_type_id' => $ticketType->id,
            'buyer_name' => 'Acheteur Test',
            'buyer_email' => 'buyer@example.com',
            'buyer_phone' => '97000000',
            'quantity' => 2,
            'total' => 30000,
        ]);
    }
}
