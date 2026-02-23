<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_is_not_used_in_this_app(): void
    {
        $this->markTestSkipped('このアプリは email 検証を採用していないためテスト対象外です。');
    }
}
