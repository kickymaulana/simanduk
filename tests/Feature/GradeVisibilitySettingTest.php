<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GradeVisibilitySettingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'Operator']);

        $this->admin = User::create([
            'name' => 'Admin',
            'username' => 'grade-admin',
            'email' => 'grade-admin@example.com',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);
        $this->admin->assignRole('admin');

        $this->operator = User::create([
            'name' => 'Operator',
            'username' => 'grade-operator',
            'email' => 'grade-operator@example.com',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);
        $this->operator->assignRole('Operator');
    }

    public function test_grade_visibility_defaults_to_enabled_for_existing_operator_display(): void
    {
        $this->actingAs($this->admin)
            ->get(route('pengaturan'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('tampilkan_grade_scan_packing', true)
                ->where('tampilkan_grade_scan_checking', true));
    }

    public function test_admin_can_update_both_grade_visibility_settings_and_reenable_them(): void
    {
        $this->actingAs($this->admin)
            ->post(route('pengaturan.tampilan_grade'), [
                'scan_packing' => false,
                'scan_checking' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', ['key' => 'tampilkan_grade_scan_packing', 'value' => '0']);
        $this->assertDatabaseHas('settings', ['key' => 'tampilkan_grade_scan_checking', 'value' => '0']);

        $this->actingAs($this->admin)
            ->post(route('pengaturan.tampilan_grade'), [
                'scan_packing' => true,
                'scan_checking' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', ['key' => 'tampilkan_grade_scan_packing', 'value' => '1']);
        $this->assertDatabaseHas('settings', ['key' => 'tampilkan_grade_scan_checking', 'value' => '1']);

        $this->actingAs($this->admin)
            ->get(route('pengaturan'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tampilkan_grade_scan_packing', true)
                ->where('tampilkan_grade_scan_checking', true));
    }

    public function test_non_admin_cannot_read_or_change_grade_visibility_settings(): void
    {
        $this->actingAs($this->operator)
            ->get(route('pengaturan'))
            ->assertForbidden();

        $this->actingAs($this->operator)
            ->post(route('pengaturan.tampilan_grade'), [
                'scan_packing' => false,
                'scan_checking' => false,
            ])
            ->assertForbidden();

        $this->assertSame('1', DB::table('settings')->where('key', 'tampilkan_grade_scan_packing')->value('value'));
        $this->assertSame('1', DB::table('settings')->where('key', 'tampilkan_grade_scan_checking')->value('value'));
    }
}
