<?php

namespace Tests\Feature\Purchases;

use App\Models\Customer;
use App\Models\Item;
use App\Models\CompanyProfile;
use App\Models\PurchaseQuote;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestEmailLog;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Work;
use App\Mail\PurchaseRequestMail;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PurchaseRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_can_create_rfq_register_quotes_and_select_best_quote(): void
    {
        $work = $this->createWork($this->admin);
        $item = $this->createItem($this->admin);
        $item2 = $this->createItem($this->admin, 'COND-001', 'Condutor flexivel');
        $supplierA = Supplier::query()->create([
            'owner_id' => $this->admin->id,
            'name' => 'Fornecedor A',
            'email' => 'a@example.com',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $supplierB = Supplier::query()->create([
            'owner_id' => $this->admin->id,
            'name' => 'Fornecedor B',
            'email' => 'b@example.com',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $createResponse = $this->actingAs($this->admin)->post(route('purchase-requests.store'), [
            'work_id' => $work->id,
            'deadline_at' => now()->addDays(3)->toDateString(),
            'notes' => 'Pedido para comparar preco e lead time.',
            'items' => [
                [
                    'item_id' => $item->id,
                    'description' => 'Cabo eletrico 3x2.5',
                    'qty' => 120,
                    'unit_snapshot' => 'm',
                ],
                [
                    'item_id' => $item2->id,
                    'description' => 'Condutor flexivel 1.5',
                    'qty' => 50,
                    'unit_snapshot' => 'm',
                ],
            ],
        ]);

        $createResponse
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $rfq = PurchaseRequest::query()->latest('id')->firstOrFail();
        $rfqItems = $rfq->items()->orderBy('sort_order')->orderBy('id')->get();
        $this->assertCount(2, $rfqItems);
        $rfqItemA = $rfqItems->get(0);
        $rfqItemB = $rfqItems->get(1);

        $this->actingAs($this->admin)->post(route('purchase-requests.quotes.store', $rfq), [
            'supplier_id' => $supplierA->id,
            'currency' => 'EUR',
            'lead_time_days' => 5,
            'status' => PurchaseQuote::STATUS_RECEIVED,
            'items' => [
                [
                    'purchase_request_item_id' => $rfqItemA->id,
                    'quoted_qty' => 120,
                    'unit_price' => 12.25,
                    'discount_percent' => 2.50,
                    'line_total' => 1433.25,
                    'lead_time_days' => 4,
                ],
                [
                    'purchase_request_item_id' => $rfqItemB->id,
                    'quoted_qty' => 50,
                    'unit_price' => 8.50,
                    'line_total' => 425.00,
                    'lead_time_days' => 5,
                ],
            ],
        ])->assertRedirect(route('purchase-requests.show', $rfq));

        $this->actingAs($this->admin)->post(route('purchase-requests.quotes.store', $rfq), [
            'supplier_id' => $supplierB->id,
            'currency' => 'EUR',
            'lead_time_days' => 8,
            'status' => PurchaseQuote::STATUS_RECEIVED,
            'items' => [
                [
                    'purchase_request_item_id' => $rfqItemA->id,
                    'quoted_qty' => 120,
                    'unit_price' => 11.80,
                    'line_total' => 1416.00,
                    'lead_time_days' => 7,
                ],
                [
                    'purchase_request_item_id' => $rfqItemB->id,
                    'quoted_qty' => null,
                    'unit_price' => null,
                    'line_total' => null,
                    'lead_time_days' => null,
                    'notes' => null,
                ],
            ],
        ])->assertRedirect(route('purchase-requests.show', $rfq));

        $bestQuote = PurchaseQuote::query()
            ->where('purchase_request_id', $rfq->id)
            ->orderBy('total_amount')
            ->firstOrFail();

        $this->actingAs($this->admin)
            ->patch(route('purchase-requests.quotes.select', [$rfq, $bestQuote]))
            ->assertRedirect(route('purchase-requests.show', $rfq));

        $this->assertDatabaseHas('purchase_quotes', [
            'id' => $bestQuote->id,
            'status' => PurchaseQuote::STATUS_SELECTED,
        ]);

        $this->assertDatabaseHas('purchase_requests', [
            'id' => $rfq->id,
            'status' => PurchaseRequest::STATUS_CLOSED,
        ]);

        $this->actingAs($this->admin)
            ->get(route('purchase-requests.show', $rfq))
            ->assertOk()
            ->assertSee('Resumo global das propostas')
            ->assertSee('Comparacao artigo a artigo')
            ->assertSee('Nao cotado');
    }

    public function test_can_send_rfq_email_and_mark_request_as_sent(): void
    {
        Mail::fake();

        $work = $this->createWork($this->admin);
        $item = $this->createItem($this->admin);
        $supplier = Supplier::query()->create([
            'owner_id' => $this->admin->id,
            'name' => 'Fornecedor Email',
            'email' => 'fornecedor@email.test',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        CompanyProfile::query()->create([
            'owner_id' => $this->admin->id,
            'company_name' => 'Empresa Teste',
            'mail_host' => 'smtp.mailtrap.io',
            'mail_port' => 587,
            'mail_username' => 'user',
            'mail_password' => 'secret',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'noreply@test.local',
            'mail_from_name' => 'Empresa Teste',
        ]);

        $this->actingAs($this->admin)->post(route('purchase-requests.store'), [
            'work_id' => $work->id,
            'deadline_at' => now()->addDays(2)->toDateString(),
            'items' => [
                [
                    'item_id' => $item->id,
                    'description' => 'Linha para email',
                    'qty' => 1,
                    'unit_snapshot' => 'un',
                ],
            ],
        ])->assertRedirect();

        $rfq = PurchaseRequest::query()->latest('id')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('purchase-requests.send-email', $rfq), [
                'supplier_id' => $supplier->id,
                'recipient_email' => '',
                'recipient_name' => '',
                'email_notes' => 'Enviar proposta ate amanha.',
            ])
            ->assertRedirect(route('purchase-requests.show', $rfq))
            ->assertSessionHas('success');

        Mail::assertSent(PurchaseRequestMail::class);

        $this->assertDatabaseHas('purchase_requests', [
            'id' => $rfq->id,
            'status' => PurchaseRequest::STATUS_SENT,
        ]);

        $this->assertDatabaseHas('purchase_request_email_logs', [
            'purchase_request_id' => $rfq->id,
            'recipient_email' => $supplier->email,
        ]);

        $this->assertGreaterThan(0, PurchaseRequestEmailLog::query()->count());
    }

    private function createWork(User $owner): Work
    {
        $customer = Customer::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Cliente Compras',
            'email' => 'cliente@example.com',
            'created_by' => $owner->id,
        ]);

        return Work::query()->create([
            'owner_id' => $owner->id,
            'customer_id' => $customer->id,
            'code' => 'OBR-COMP-0001',
            'name' => 'Obra de Teste Compras',
            'status' => Work::STATUS_PLANNED,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
    }

    private function createItem(User $owner, string $code = 'CABO-001', string $name = 'Cabo eletrico'): Item
    {
        $unit = Unit::query()->firstOrCreate(
            ['code' => 'M'],
            [
                'owner_id' => $owner->id,
                'name' => 'Metro',
                'factor' => 1,
                'is_active' => true,
            ]
        );

        return Item::query()->create([
            'owner_id' => $owner->id,
            'code' => $code,
            'name' => $name,
            'unit_id' => $unit->id,
            'type' => 'product',
            'cost_price' => 10,
            'sale_price' => 12,
            'tracks_stock' => true,
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
    }
}
