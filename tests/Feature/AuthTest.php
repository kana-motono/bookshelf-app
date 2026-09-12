<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_register_page(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_user_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);

        $this->assertAuthenticated();
    }

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this
            ->from(route('login'))
            ->post('/login', [
                'email' => 'login@example.com',
                'password' => 'wrong-password',
            ]);

        $this->assertGuest();

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_cannot_view_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('login'));

        $response->assertRedirect();
    }

    public function test_guest_can_access_books_page(): void
    {
        $response = $this->get(route('books.index'));

        $response->assertOk();
    }
}