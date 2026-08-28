<?php

namespace Tests\Feature;

use App\Models\AppUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\SignsRequests;
use Tests\TestCase;

class LicenseApiTest extends TestCase
{
    use RefreshDatabase, SignsRequests;

    public function test_login_rejects_unsigned_request(): void
    {
        // 无 timestamp -> 中间件先做时间戳校验 -> 4001
        $res = $this->postJson('/api/v1/login', [
            'username' => 'foo',
            'password' => 'bar',
        ]);

        $res->assertStatus(400)->assertJson(['code' => 4001]);
    }

    public function test_login_rejects_bad_signature(): void
    {
        // 有合法 timestamp 但签名错误 -> 4002
        $res = $this->postJson('/api/v1/login', [
            'username'  => 'foo',
            'password'  => 'bar',
            'timestamp' => time(),
            'nonce'     => 'abc',
            'sign'      => 'deadbeef',
        ]);

        $res->assertStatus(401)->assertJson(['code' => 4002]);
    }

    public function test_login_rejects_expired_timestamp(): void
    {
        $params = $this->signed([
            'username'  => 'foo',
            'password'  => 'bar',
            'timestamp' => time() - 99999,
        ]);

        $this->postJson('/api/v1/login', $params)
            ->assertStatus(400)
            ->assertJson(['code' => 4001]);
    }

    public function test_successful_login_returns_expiry(): void
    {
        $user = AppUser::factory()->create([
            'username'   => 'alice',
            'password'   => Hash::make('secret'),
            'expires_at' => now()->addDays(10),
        ]);

        $params = $this->signed([
            'username' => 'alice',
            'password' => 'secret',
        ]);

        $res = $this->postJson('/api/v1/login', $params);

        $res->assertOk()
            ->assertJson(['code' => 0])
            ->assertJsonPath('data.username', 'alice');

        $this->assertGreaterThan(0, $res->json('data.remaining_seconds'));
    }

    public function test_wrong_password_fails(): void
    {
        AppUser::factory()->create([
            'username' => 'bob',
            'password' => Hash::make('correct'),
        ]);

        $params = $this->signed(['username' => 'bob', 'password' => 'wrong']);

        $this->postJson('/api/v1/login', $params)
            ->assertOk()
            ->assertJson(['code' => 1001]);
    }

    public function test_expired_account_fails(): void
    {
        AppUser::factory()->expired()->create([
            'username' => 'carol',
            'password' => Hash::make('secret'),
        ]);

        $params = $this->signed(['username' => 'carol', 'password' => 'secret']);

        $this->postJson('/api/v1/login', $params)
            ->assertOk()
            ->assertJson(['code' => 1003]);
    }

    public function test_banned_account_fails(): void
    {
        AppUser::factory()->banned()->create([
            'username' => 'dan',
            'password' => Hash::make('secret'),
        ]);

        $params = $this->signed(['username' => 'dan', 'password' => 'secret']);

        $this->postJson('/api/v1/login', $params)
            ->assertOk()
            ->assertJson(['code' => 1002]);
    }

    public function test_machine_code_binding(): void
    {
        AppUser::factory()->create([
            'username'     => 'eve',
            'password'     => Hash::make('secret'),
            'machine_code' => 'MACHINE-A',
        ]);

        // 不同机器码应被拒绝
        $params = $this->signed([
            'username'     => 'eve',
            'password'     => 'secret',
            'machine_code' => 'MACHINE-B',
        ]);

        $this->postJson('/api/v1/login', $params)
            ->assertOk()
            ->assertJson(['code' => 1004]);
    }

    public function test_response_is_signed(): void
    {
        AppUser::factory()->create([
            'username' => 'frank',
            'password' => Hash::make('secret'),
        ]);

        $params = $this->signed(['username' => 'frank', 'password' => 'secret']);
        $res = $this->postJson('/api/v1/login', $params);

        $res->assertOk();
        $this->assertNotEmpty($res->json('sign'));
        $this->assertNotEmpty($res->json('nonce'));
    }
}
