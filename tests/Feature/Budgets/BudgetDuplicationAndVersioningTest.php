<?php

namespace Tests\Feature\Budgets;

use App\Models\Budget;
use App\Models\Customer;
use App\Models\DocumentSeries;
use App\Models\User;
use App\Support\ActivityActions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetDuplicationAndVersioningTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Budget $sourceBudget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $customer = Customer::query()->create([
            'owner_id' => $this->admin->id,
            'name' => 'Cliente Teste',
            'email' => 'cliente.teste@example.com',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $series = DocumentSeries::query()->create([
            'owner_id' => $this->admin->id,
            'document_type' => 'budget',
            'prefix' => 'ORC',
            'name' => '2026',
            'year' => 2026,
            'next_number' => 2,
            'is_active' => true,
        ]);

        $this->sourceBudget = Budget::query()->create([
            'owner_id' => $this->admin->id,
            'code' => 'ORC-2026-0001',
            'customer_id' => $customer->id,
            'status' => Budget::STATUS_CREATED,
            'budget_date' => now()->toDateString(),
            'designation' => 'Orçamento base',
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 23,
            'total' => 123,
            'document_series_id' => $series->id,
            'serial_number' => 1,
            'version_number' => 1,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->sourceBudget->items()->create([
            'sort_order' => 1,
            'item_name' => 'Linha 1',
            'item_type' => 'service',
            'quantity' => 1,
            'unit_price' => 100,
            'discount_percent' => 0,
            'tax_percent' => 23,
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 23,
            'total' => 123,
        ]);
    }

    public function test_budget_can_be_duplicated_with_items_and_initial_state(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('budgets.duplicate', $this->sourceBudget));

        $response->assertRedirect();

        $duplicatedBudget = Budget::query()
            ->whereKeyNot($this->sourceBudget->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($duplicatedBudget);
        $this->assertSame(Budget::STATUS_DRAFT, $duplicatedBudget->status);
        $this->assertSame(1, (int) $duplicatedBudget->version_number);
        $this->assertNull($duplicatedBudget->root_budget_id);
        $this->assertNull($duplicatedBudget->parent_budget_id);
        $this->assertNotSame($this->sourceBudget->code, $duplicatedBudget->code);
        $this->assertSame(1, $duplicatedBudget->items()->count());

        $this->assertDatabaseHas('activity_logs', [
            'action' => ActivityActions::DUPLICATED,
            'entity' => 'budget',
            'entity_id' => $duplicatedBudget->id,
        ]);
    }

    public function test_budget_can_create_successive_versions(): void
    {
        $this->actingAs($this->admin)
            ->post(route('budgets.versions.store', $this->sourceBudget))
            ->assertRedirect();

        $v2 = Budget::query()
            ->where('parent_budget_id', $this->sourceBudget->id)
            ->first();

        $this->assertNotNull($v2);
        $this->assertSame($this->sourceBudget->id, (int) $v2->root_budget_id);
        $this->assertSame($this->sourceBudget->id, (int) $v2->parent_budget_id);
        $this->assertSame(2, (int) $v2->version_number);
        $this->assertSame(1, $v2->items()->count());

        $this->actingAs($this->admin)
            ->post(route('budgets.versions.store', $v2))
            ->assertRedirect();

        $v3 = Budget::query()
            ->where('parent_budget_id', $v2->id)
            ->first();

        $this->assertNotNull($v3);
        $this->assertSame($this->sourceBudget->id, (int) $v3->root_budget_id);
        $this->assertSame($v2->id, (int) $v3->parent_budget_id);
        $this->assertSame(3, (int) $v3->version_number);

        $this->assertDatabaseHas('activity_logs', [
            'action' => ActivityActions::VERSION_CREATED,
            'entity' => 'budget',
            'entity_id' => $v3->id,
        ]);
    }
}
