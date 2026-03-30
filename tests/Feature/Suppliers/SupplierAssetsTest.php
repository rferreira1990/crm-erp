<?php

namespace Tests\Feature\Suppliers;

use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplierAssetsTest extends TestCase
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

    public function test_can_upload_and_remove_supplier_logo(): void
    {
        Storage::fake('public');

        $supplier = Supplier::query()->create([
            'owner_id' => $this->admin->id,
            'name' => 'Fornecedor com logo',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('suppliers.logo.store', $supplier), [
                'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
            ]);

        $response
            ->assertRedirect(route('suppliers.show', $supplier))
            ->assertSessionHas('success');

        $supplier->refresh();
        $this->assertNotNull($supplier->logo_path);
        Storage::disk('public')->assertExists($supplier->logo_path);

        $removeResponse = $this->actingAs($this->admin)
            ->delete(route('suppliers.logo.destroy', $supplier));

        $removeResponse
            ->assertRedirect(route('suppliers.show', $supplier))
            ->assertSessionHas('success');

        $supplier->refresh();
        $this->assertNull($supplier->logo_path);
    }

    public function test_can_upload_open_and_delete_supplier_attachment(): void
    {
        Storage::fake('local');

        $supplier = Supplier::query()->create([
            'owner_id' => $this->admin->id,
            'name' => 'Fornecedor com anexos',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $uploadResponse = $this->actingAs($this->admin)
            ->post(route('suppliers.files.store', $supplier), [
                'files' => [
                    UploadedFile::fake()->image('catalogo.png', 800, 600),
                ],
            ]);

        $uploadResponse
            ->assertRedirect(route('suppliers.show', $supplier))
            ->assertSessionHas('success');

        $file = $supplier->files()->firstOrFail();
        Storage::disk('local')->assertExists($file->file_path);

        $showResponse = $this->actingAs($this->admin)
            ->get(route('suppliers.files.show', [$supplier, $file]));

        $showResponse->assertOk();

        $deleteResponse = $this->actingAs($this->admin)
            ->delete(route('suppliers.files.destroy', [$supplier, $file]));

        $deleteResponse
            ->assertRedirect(route('suppliers.show', $supplier))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('supplier_files', [
            'id' => $file->id,
        ]);
    }
}

