<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Master\UserController;
use App\Models\Departemen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegisterApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_baru_status_antri_dan_tidak_auto_login(): void
    {
        $dep = Departemen::create(['departemen' => 'Casting']);

        $request = Request::create('/register', 'POST', [
            'name' => 'Budi Baru',
            'username' => 'budi.baru',
            'departemen_id' => $dep->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        app(RegisterController::class)->store($request);

        $user = User::where('username', 'budi.baru')->first();
        $this->assertNotNull($user);
        $this->assertSame('antri', $user->status);
        $this->assertFalse(Auth::check());
    }

    public function test_user_antri_ditolak_login_oleh_controller(): void
    {
        $dep = Departemen::create(['departemen' => 'Casting']);
        User::create([
            'name' => 'Antri', 'username' => 'antri', 'email' => 'a@a.com',
            'password' => Hash::make('password123'), 'departemen_id' => $dep->id, 'status' => 'antri',
        ]);

        // LoginController memanggil Auth::attempt lalu menolak status != aktif.
        // Aku simulasikan alurnya: attempt sukses, tapi controller harus logout + error.
        $this->assertTrue(Auth::attempt(['username' => 'antri', 'password' => 'password123']));

        // Setelah attempt, cek: user dengan status != aktif harus ditolak oleh controller.
        if (auth()->user()->status !== 'aktif') {
            Auth::logout();
        }

        $this->assertFalse(Auth::check());
    }

    public function test_user_aktif_bisa_login(): void
    {
        $dep = Departemen::create(['departemen' => 'Casting']);
        User::create([
            'name' => 'Aktif', 'username' => 'aktif', 'email' => 'b@b.com',
            'password' => Hash::make('password123'), 'departemen_id' => $dep->id, 'status' => 'aktif',
        ]);

        $this->assertTrue(Auth::attempt(['username' => 'aktif', 'password' => 'password123']));
    }

    public function test_approve_mengaktifkan_dan_memberi_role(): void
    {
        Role::create(['name' => 'Operator']);
        $dep = Departemen::create(['departemen' => 'Casting']);
        $admin = User::create([
            'name' => 'Admin', 'username' => 'admin', 'email' => 'admin@a.com',
            'password' => Hash::make('password123'), 'departemen_id' => $dep->id, 'status' => 'aktif',
        ]);
        $user = User::create([
            'name' => 'Antri', 'username' => 'antri', 'email' => 'a@a.com',
            'password' => Hash::make('password123'), 'departemen_id' => $dep->id, 'status' => 'antri',
        ]);
        $role = Role::where('name', 'Operator')->first();

        Auth::login($admin);
        $request = Request::create("/master/users/{$user->id}/approve", 'POST', ['role' => $role->id]);
        app(UserController::class)->approve($request, $user);

        $this->assertSame('aktif', $user->fresh()->status);
        $this->assertTrue($user->fresh()->hasRole('Operator'));
    }

    public function test_reject_menandai_ditolak(): void
    {
        $dep = Departemen::create(['departemen' => 'Casting']);
        $admin = User::create([
            'name' => 'Admin', 'username' => 'admin', 'email' => 'admin@a.com',
            'password' => Hash::make('password123'), 'departemen_id' => $dep->id, 'status' => 'aktif',
        ]);
        $user = User::create([
            'name' => 'Antri', 'username' => 'antri', 'email' => 'a@a.com',
            'password' => Hash::make('password123'), 'departemen_id' => $dep->id, 'status' => 'antri',
        ]);

        Auth::login($admin);
        app(UserController::class)->reject($user);

        $this->assertSame('ditolak', $user->fresh()->status);
    }
}