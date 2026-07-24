<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_second_admin(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin', 'is_active' => true]);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Admin Kedua',
            'email' => 'admin2@example.com',
            'phone' => '08123456789',
            'role' => 'Admin',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'admin2@example.com', 'role' => 'Admin', 'is_active' => true]);

        $this->post(route('login'), ['email' => 'admin2@example.com', 'password' => 'rahasia123'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_reset_another_users_password(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'Admin', 'is_active' => true]);

        $this->actingAs($admin)->put(route('users.update', $other), [
            'name' => $other->name,
            'email' => $other->email,
            'phone' => '',
            'role' => 'Admin',
            'is_active' => 1,
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('password-baru', $other->fresh()->password));
    }

    public function test_sales_cannot_access_user_management(): void
    {
        $sales = User::factory()->create(['role' => 'Sales', 'is_active' => true]);

        $this->actingAs($sales)->get(route('users.index'))->assertForbidden();
    }

    public function test_admin_cannot_access_user_management(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'is_active' => true]);

        $this->actingAs($admin)->get(route('users.index'))->assertForbidden();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create(['password' => 'password', 'is_active' => false]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_delete_another_admin_but_not_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'Admin', 'is_active' => true]);
        $super = User::factory()->create(['role' => 'Super Admin', 'is_active' => true]);

        $this->actingAs($admin)->delete(route('users.destroy', $other))->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $other->id]);

        $this->actingAs($admin)->delete(route('users.destroy', $super))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $super->id]);
    }
}
