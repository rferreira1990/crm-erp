<?php

namespace Tests\Feature\Purchases;

use App\Models\Customer;
use App\Models\Item;
use App\Models\PurchaseQuote;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Work;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'title' => 'Materiais eletricos para obra',
            'work_id' => $work->id,
            'status' => PurchaseRequest::STATUS_DRAFT,
            'needed_at' => now()->addDays(7)->toDateString(),
            'deadline_at' => now()->addDays(3)->toDateString(),
            'notes' => 'Pedido para comparar preco e lead time.',
            'items' => [
                [
                    'item_id' => $item->id,
                    'description' => 'Cabo eletrico 3x2.5',
                    'qty' => 120,
                    'unit_snapshot' => 'm',
                ],
            ],
        ]);

        $createResponse
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $rfq = PurchaseRequest::query()->latest('id')->firstOrFail();
        $this->assertSame('Materiais eletricos para obra', $rfq->title);
        $this->assertSame(1, $rfq->items()->count());

        $this->actingAs($this->admin)->post(route('purchase-requests.quotes.store', $rfq), [
            'supplier_id' => $supplierA->id,
            'total_amount' => 1850.00,
            'currency' => 'EUR',
            'lead_time_days' => 5,
            'status' => PurchaseQuote::STATUS_RECEIVED,
        ])->assertRedirect(route('purchase-requests.show', $rfq));

        $this->actingAs($this->admin)->post(route('purchase-requests.quotes.store', $rfq), [
            'supplier_id' => $supplierB->id,
            'total_amount' => 1760.00,
            'currency' => 'EUR',
            'lead_time_days' => 8,
            'status' => PurchaseQuote::STATUS_RECEIVED,
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
            ->assertSee('Comparacao de propostas')
            ->assertSee('Mais barata');
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

    private function createItem(User $owner): Item
    {
        $unit = Unit::query()->create([
            'owner_id' => $owner->id,
            'code' => 'M',
            'name' => 'Metro',
            'factor' => 1,
            'is_active' => true,
        ]);

        return Item::query()->create([
            'owner_id' => $owner->id,
            'code' => 'CABO-001',
            'name' => 'Cabo eletrico',
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
