<?php

namespace Tests\Feature;

use App\Models\Aksesori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StokAksesoriTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    private Aksesori $aksesori;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'Operator']);

        $this->admin = User::create([
            'name' => 'Admin',
            'username' => 'admin-stok',
            'email' => 'admin-stok@example.com',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);
        $this->admin->assignRole('admin');

        $this->operator = User::create([
            'name' => 'Operator',
            'username' => 'operator-stok',
            'email' => 'operator-stok@example.com',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);
        $this->operator->assignRole('Operator');

        $this->aksesori = Aksesori::create([
            'aksesori' => 'SC-001',
            'part_number' => 'SC-001',
            'description' => 'Seat Cover',
            'stok' => 5,
        ]);
    }

    public function test_admin_can_add_incoming_stock_and_movement_is_recorded(): void
    {
        $response = $this->actingAs($this->admin)->post("/stok-aksesori/{$this->aksesori->id}/masuk", [
            'quantity' => 7,
            'keterangan' => 'Penerimaan PO-001',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(12, $this->aksesori->fresh()->stok);
        $this->assertDatabaseHas('pergerakan_stok_aksesori', [
            'aksesori_id' => $this->aksesori->id,
            'packing_id' => null,
            'user_id' => $this->admin->id,
            'tipe' => 'MASUK',
            'quantity' => 7,
            'stok_sebelum' => 5,
            'stok_sesudah' => 12,
            'keterangan' => 'Penerimaan PO-001',
        ]);
    }

    public function test_stock_page_and_incoming_stock_are_admin_only(): void
    {
        $this->actingAs($this->operator)
            ->get('/stok-aksesori')
            ->assertForbidden();

        $this->actingAs($this->operator)
            ->post("/stok-aksesori/{$this->aksesori->id}/masuk", ['quantity' => 3])
            ->assertForbidden();

        $this->assertSame(5, $this->aksesori->fresh()->stok);
        $this->assertDatabaseCount('pergerakan_stok_aksesori', 0);
    }

    public function test_incoming_stock_requires_positive_integer_quantity_with_indonesian_message(): void
    {
        $this->actingAs($this->admin)
            ->post("/stok-aksesori/{$this->aksesori->id}/masuk", ['quantity' => 0])
            ->assertSessionHasErrors([
                'quantity' => 'Quantity stok masuk minimal 1.',
            ]);

        $this->assertSame(5, $this->aksesori->fresh()->stok);
        $this->assertDatabaseCount('pergerakan_stok_aksesori', 0);
    }
}
