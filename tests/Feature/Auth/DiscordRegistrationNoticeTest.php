<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscordRegistrationNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_displays_note_for_multi_account_users(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('初心者G1馬券バトルチャンネルにログインしているアカウントでログインしてください。');
        $response->assertSee('別アカウントが表示された場合は、ブラウザ版Discordから一度ログアウトし、正しいアカウントでログインし直してから再度お試しください。');
    }

    public function test_registration_notice_redirects_to_login_without_pending_session_data(): void
    {
        $response = $this->get('/login/discord/register-notice');

        $response->assertRedirect('/login');
    }

    public function test_registration_notice_can_be_rendered_with_pending_discord_data(): void
    {
        $response = $this->withSession([
            'pending_discord_registration' => [
                'discord_id' => '1234567890',
                'display_name' => 'テストユーザー',
            ],
        ])->get('/login/discord/register-notice');

        $response->assertOk();
        $response->assertSee('初回ログインの確認');
        $response->assertSee('表示名: テストユーザー');
        $response->assertSee('別アカウントが表示された場合は、ブラウザ版Discordから一度ログアウトし、正しいアカウントでログインし直してから再度お試しください。');
    }

    public function test_complete_registration_creates_user_and_logs_in(): void
    {
        $response = $this->withSession([
            'pending_discord_registration' => [
                'discord_id' => '5555555555',
                'display_name' => '新規ユーザー',
            ],
        ])->post('/login/discord/register-notice');

        $createdUser = User::query()->where('discord_id', '5555555555')->first();

        $this->assertNotNull($createdUser);
        $this->assertAuthenticatedAs($createdUser);
        $response->assertRedirect();
    }

    public function test_cancel_registration_clears_pending_session_data(): void
    {
        $response = $this->withSession([
            'pending_discord_registration' => [
                'discord_id' => '9999999999',
            ],
        ])->post('/login/discord/register-notice/cancel');

        $response->assertRedirect('/login');
        $response->assertSessionHas('status', 'Discordログインをキャンセルしました。');
        $response->assertSessionMissing('pending_discord_registration');
    }
}
