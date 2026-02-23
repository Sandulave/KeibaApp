<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_is_not_used_in_this_app(): void
    {
        $this->markTestSkipped('このアプリは email ベースのパスワードリセットを採用していないためテスト対象外です。');
    }
}
