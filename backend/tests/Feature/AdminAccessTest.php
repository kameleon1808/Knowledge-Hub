<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_panel(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_member_cannot_access_admin_panel(): void
    {
        $member = User::factory()->create([
            'role' => User::ROLE_MEMBER,
        ]);

        $response = $this->actingAs($member)->get('/admin');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_panel_and_users_list(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertStatus(200);
    }

    public function test_admin_can_update_another_user_role(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $member = User::factory()->create([
            'role' => User::ROLE_MEMBER,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $member), [
            'role' => User::ROLE_MODERATOR,
        ]);

        $response->assertRedirect(route('admin.users.edit', $member));
        $this->assertSame(User::ROLE_MODERATOR, $member->refresh()->role);
    }

    public function test_admin_cannot_demote_self_when_only_admin(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'role' => User::ROLE_MEMBER,
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertSame(User::ROLE_ADMIN, $admin->refresh()->role);
    }
}
