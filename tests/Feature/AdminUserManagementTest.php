<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_a_non_admin_user_from_the_member_directory(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'approved',
        ]);

        $member = User::factory()->create([
            'is_admin' => false,
            'status' => 'approved',
            'tool_access' => ['procreate_studio', 'pdf_lab'],
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('admin.users.delete', $member));

        $response
            ->assertRedirect(route('admin.index'))
            ->assertSessionHas('success', 'User deleted successfully.');

        $this->assertDatabaseMissing('users', [
            'id' => $member->id,
        ]);
    }

    public function test_admin_delete_route_returns_json_for_ajax_requests(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'approved',
        ]);

        $member = User::factory()->create([
            'is_admin' => false,
            'status' => 'approved',
        ]);

        $response = $this
            ->actingAs($admin)
            ->deleteJson(route('admin.users.delete', $member));

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'User deleted successfully.',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $member->id,
        ]);
    }
}
