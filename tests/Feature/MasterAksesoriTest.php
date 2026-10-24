<?php

namespace Tests\Feature;

use App\Http\Controllers\Master\AksesoriController;
use App\Models\Aksesori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MasterAksesoriTest extends TestCase
{
    use RefreshDatabase;

    public function test_accessory_can_be_created_with_part_number_and_description(): void
    {
        $request = Request::create('/master/aksesori', 'POST', [
            'part_number' => 'ACC-001',
            'description' => 'Seat Cover Putih',
        ]);

        app(AksesoriController::class)->store($request);

        $this->assertDatabaseHas('aksesori', [
            'part_number' => 'ACC-001',
            'description' => 'Seat Cover Putih',
            'aksesori' => 'ACC-001',
        ]);
    }

    public function test_part_number_must_be_unique(): void
    {
        Aksesori::create([
            'aksesori' => 'ACC-001',
            'part_number' => 'ACC-001',
            'description' => 'Seat Cover',
        ]);
        $request = Request::create('/master/aksesori', 'POST', [
            'part_number' => 'ACC-001',
            'description' => 'Description Lain',
        ]);

        try {
            app(AksesoriController::class)->store($request);
            $this->fail('Duplicate part number was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('part_number', $exception->errors());
        }

        $this->assertDatabaseCount('aksesori', 1);
    }

    public function test_part_number_and_description_can_be_updated(): void
    {
        $aksesori = Aksesori::create([
            'aksesori' => 'ACC-001',
            'part_number' => 'ACC-001',
            'description' => 'Seat Cover',
        ]);
        $request = Request::create("/master/aksesori/{$aksesori->id}", 'PUT', [
            'part_number' => 'ACC-002',
            'description' => 'Seat Cover Baru',
        ]);

        app(AksesoriController::class)->update($request, $aksesori);

        $this->assertDatabaseHas('aksesori', [
            'id' => $aksesori->id,
            'part_number' => 'ACC-002',
            'description' => 'Seat Cover Baru',
            'aksesori' => 'ACC-002',
        ]);
    }
}
