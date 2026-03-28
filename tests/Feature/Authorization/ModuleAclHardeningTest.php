<?php

namespace Tests\Feature\Authorization;

use App\Models\Customer;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkMaterial;
use App\Models\WorkTask;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ModuleAclHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_has_access_to_main_modules(): void
    {
        $admin = $this->createUserWithRole('admin');

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('budgets.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('works.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('stock.index'))
            ->assertOk();
    }

    public function test_user_without_users_permission_is_blocked_from_users_module(): void
    {
        $worker = $this->createUserWithRole('funcionario');

        $this->actingAs($worker)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($worker)
            ->get(route('users.create'))
            ->assertForbidden();
    }

    public function test_obras_user_without_stock_edit_cannot_submit_manual_adjustment(): void
    {
        $owner = $this->createUserWithRole('admin');
        $item = $this->createStockItem($owner, 10);

        $obrasUser = $this->createUserWithRole('obras', ['stock.view', 'stock.create']);

        $this->actingAs($obrasUser)
            ->get(route('stock.index'))
            ->assertOk()
            ->assertDontSee('Ajuste manual');

        $response = $this->actingAs($obrasUser)
            ->from(route('stock.index'))
            ->post(route('stock.movements.store'), [
                'item_id' => $item->id,
                'movement_type' => StockMovement::TYPE_MANUAL_ADJUSTMENT,
                'direction' => StockMovement::DIRECTION_ADJUSTMENT,
                'quantity' => -2,
                'manual_reason' => StockMovement::MANUAL_REASON_CORRECTION,
                'notes' => 'Ajuste manual sem permissao.',
            ]);

        $response
            ->assertRedirect(route('stock.index'))
            ->assertSessionHasErrors(['movement_type']);

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_stocks_user_with_stock_create_can_register_manual_entry(): void
    {
        $owner = $this->createUserWithRole('admin');
        $item = $this->createStockItem($owner, 10);

        $stocksUser = $this->createUserWithRole('stocks', ['stock.create']);

        $this->actingAs($stocksUser)
            ->get(route('stock.index'))
            ->assertOk()
            ->assertSee('Novo movimento manual');

        $response = $this->actingAs($stocksUser)
            ->from(route('stock.index'))
            ->post(route('stock.movements.store'), [
                'item_id' => $item->id,
                'movement_type' => StockMovement::TYPE_MANUAL_ENTRY,
                'direction' => StockMovement::DIRECTION_IN,
                'quantity' => 3,
                'manual_reason' => StockMovement::MANUAL_REASON_STOCK_COUNT,
                'notes' => 'Contagem inicial de armazem.',
            ]);

        $response
            ->assertRedirect(route('stock.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $item->id,
            'movement_type' => StockMovement::TYPE_MANUAL_ENTRY,
            'direction' => StockMovement::DIRECTION_IN,
            'manual_reason' => StockMovement::MANUAL_REASON_STOCK_COUNT,
            'source_type' => 'manual',
        ]);

        $this->assertSame(13.0, (float) $item->fresh()->current_stock);
    }

    public function test_user_without_works_update_cannot_create_or_edit_tasks_and_materials(): void
    {
        $owner = $this->createUserWithRole('admin');
        $customer = $this->createCustomerFor($owner);
        $work = $this->createWorkFor($owner, $customer);
        $item = $this->createStockItem($owner, 10);

        $task = WorkTask::query()->create([
            'work_id' => $work->id,
            'title' => 'Tarefa base',
            'status' => WorkTask::STATUS_PLANNED,
            'sort_order' => 1,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $material = WorkMaterial::query()->create([
            'work_id' => $work->id,
            'item_id' => $item->id,
            'description_snapshot' => $item->name,
            'unit_snapshot' => $item->unit?->name,
            'qty' => 1,
            'unit_cost' => 2,
            'total_cost' => 2,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $viewer = $this->createUserWithRole('funcionario');

        $this->actingAs($viewer)
            ->post(route('works.tasks.store', $work), [
                'title' => 'Nao devia criar',
                'status' => WorkTask::STATUS_PLANNED,
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->put(route('works.tasks.update', [$work, $task]), [
                'title' => 'Atualizacao indevida',
                'status' => WorkTask::STATUS_PLANNED,
                'sort_order' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('works.materials.store', $work), [
                'item_id' => $item->id,
                'qty' => 1,
                'unit_cost' => 2,
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->put(route('works.materials.update', [$work, $material]), [
                'item_id' => $item->id,
                'qty' => 2,
                'unit_cost' => 2,
            ])
            ->assertForbidden();
    }

    public function test_work_material_with_stock_flag_creates_stock_movement_and_updates_stock(): void
    {
        $owner = $this->createUserWithRole('admin');
        $customer = $this->createCustomerFor($owner);
        $work = $this->createWorkFor($owner, $customer);
        $item = $this->createStockItem($owner, 10);

        $worksUser = $this->createUserWithRole('obras');

        $response = $this->actingAs($worksUser)
            ->post(route('works.materials.store', $work), [
                'item_id' => $item->id,
                'qty' => 2,
                'unit_cost' => 1.5,
                'apply_stock_movement' => '1',
                'notes' => 'Aplicacao em obra teste.',
            ]);

        $response->assertRedirect(route('works.show', $work));

        $material = WorkMaterial::query()
            ->where('work_id', $work->id)
            ->where('item_id', $item->id)
            ->first();

        $this->assertNotNull($material);
        $this->assertDatabaseHas('stock_movements', [
            'work_material_id' => $material->id,
            'item_id' => $item->id,
            'movement_type' => StockMovement::TYPE_WORK_MATERIAL,
            'direction' => StockMovement::DIRECTION_OUT,
            'source_type' => StockMovement::TYPE_WORK_MATERIAL,
        ]);

        $this->assertSame(8.0, (float) $item->fresh()->current_stock);
    }

    public function test_work_show_hides_operational_actions_for_user_without_works_update(): void
    {
        $owner = $this->createUserWithRole('admin');
        $work = $this->createWorkFor($owner, $this->createCustomerFor($owner));
        $viewer = $this->createUserWithRole('funcionario');

        $this->actingAs($viewer)
            ->get(route('works.show', $work))
            ->assertOk()
            ->assertDontSee('Adicionar tarefa')
            ->assertDontSee('Adicionar material')
            ->assertDontSee('Adicionar custo');
    }

    private function createUserWithRole(string $role, array $permissions = []): User
    {
        $user = User::factory()->create([
            'is_active' => true,
            'is_labor_enabled' => true,
            'hourly_cost' => 10,
        ]);

        $user->assignRole($role);

        if (count($permissions) > 0) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    private function createCustomerFor(User $owner): Customer
    {
        return Customer::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Cliente ' . Str::upper(Str::random(5)),
            'created_by' => $owner->id,
        ]);
    }

    private function createWorkFor(User $owner, Customer $customer): Work
    {
        return Work::query()->create([
            'owner_id' => $owner->id,
            'customer_id' => $customer->id,
            'code' => 'OBR-TST-' . Str::upper(Str::random(8)),
            'name' => 'Obra teste ACL',
            'status' => Work::STATUS_PLANNED,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
    }

    private function createStockItem(User $owner, float $currentStock): Item
    {
        $unit = Unit::query()->create([
            'owner_id' => $owner->id,
            'code' => 'UN' . Str::upper(Str::random(4)),
            'name' => 'Unidade',
            'factor' => 1,
            'is_active' => true,
        ]);

        return Item::query()->create([
            'owner_id' => $owner->id,
            'code' => 'ART-TST-' . Str::upper(Str::random(8)),
            'name' => 'Artigo de teste',
            'unit_id' => $unit->id,
            'type' => 'product',
            'cost_price' => 1.5,
            'sale_price' => 2.5,
            'tracks_stock' => true,
            'current_stock' => $currentStock,
            'is_active' => true,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
    }
}

