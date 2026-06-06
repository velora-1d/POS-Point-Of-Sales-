<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFCMTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_fcm_token(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = User::factory()->create();
        $token = 'test-fcm-token-12345';

        $response = $this
            ->actingAs($user)
            ->post('/settings/notifications/fcm-token', [
                'token' => $token,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Token FCM berhasil diperbarui.',
        ]);

        /** @var \App\Models\User $freshUser */
        $freshUser = User::find($user->id);
        $this->assertEquals($token, $freshUser->fcm_token);
    }

    public function test_guest_cannot_update_fcm_token(): void
    {
        $response = $this->postJson('/settings/notifications/fcm-token', [
            'token' => 'some-token',
        ]);

        $response->assertStatus(401);
    }
}
