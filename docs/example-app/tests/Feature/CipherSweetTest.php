<?php
declare(strict_types=1);
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CipherSweetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_can_create_and_find_user_by_email()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $found = User::whereBlind('email', 'test@example.com', 'users_email_bi')->first();
        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
        $this->assertEquals('Test User', $found->name);
        $this->assertEquals('test@example.com', $found->email);
    }

    public function test_can_create_and_find_user_by_name()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $found = User::whereBlind('name', 'Test User', 'users_name_bi')->first();
        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
        $this->assertEquals('Test User', $found->name);
        $this->assertEquals('test@example.com', $found->email);
    }
}
