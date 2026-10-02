<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EvaluationPortalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EvaluationPortalSeeder::class);
        $this->admin = User::query()->where('email', 'admin@connectionscounseling.test')->firstOrFail();
        $this->participant = User::query()->where('email', 'participant@connectionscounseling.test')->firstOrFail();
    }

    public function test_admin_can_reset_a_users_password(): void
    {
        $oldRememberToken = $this->participant->remember_token;

        $this->actingAs($this->admin)
            ->put(route('admin.users.password', $this->participant), [
                'password' => 'new-secret-123',
                'password_confirmation' => 'new-secret-123',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $this->participant->refresh();
        $this->assertTrue(Hash::check('new-secret-123', $this->participant->password));
        $this->assertNotSame($oldRememberToken, $this->participant->remember_token);

        auth()->logout();
        $this->post('/login', ['email' => $this->participant->email, 'password' => 'new-secret-123']);
        $this->assertAuthenticatedAs($this->participant);
    }

    public function test_reset_requires_matching_password_of_eight_characters(): void
    {
        $original = $this->participant->password;

        $this->actingAs($this->admin)
            ->put(route('admin.users.password', $this->participant), [
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($this->admin)
            ->put(route('admin.users.password', $this->participant), [
                'password' => 'new-secret-123',
                'password_confirmation' => 'different-123',
            ])
            ->assertSessionHasErrors('password');

        $this->assertSame($original, $this->participant->fresh()->password);
    }

    public function test_non_admin_cannot_reset_passwords(): void
    {
        $original = $this->admin->password;

        $this->actingAs($this->participant)
            ->put(route('admin.users.password', $this->admin), [
                'password' => 'hijacked-123',
                'password_confirmation' => 'hijacked-123',
            ])
            ->assertForbidden();

        $this->assertSame($original, $this->admin->fresh()->password);
    }

    public function test_users_page_shows_reset_password_control(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Reset password')
            ->assertSee(route('admin.users.password', $this->participant), false);
    }
}
