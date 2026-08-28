<?php

namespace Tests\Unit;

use App\Services\SignatureService;
use Tests\TestCase;

class SignatureServiceTest extends TestCase
{
    private function service(): SignatureService
    {
        return SignatureService::make();
    }

    public function test_canonical_sorts_keys_and_skips_sign(): void
    {
        $s = $this->service();
        $canonical = $s->canonical(['b' => '2', 'a' => '1', 'sign' => 'x']);
        $this->assertSame('a=1&b=2', $canonical);
    }

    public function test_valid_signature_verifies(): void
    {
        $s = $this->service();
        $params = ['username' => 'alice', 'timestamp' => time()];
        $params['sign'] = $s->signRequest($params);

        $this->assertTrue($s->verifyRequest($params));
    }

    public function test_tampered_params_fail_verification(): void
    {
        $s = $this->service();
        $params = ['username' => 'alice', 'timestamp' => time()];
        $params['sign'] = $s->signRequest($params);

        // 篡改参数
        $params['username'] = 'attacker';

        $this->assertFalse($s->verifyRequest($params));
    }

    public function test_missing_sign_fails(): void
    {
        $this->assertFalse($this->service()->verifyRequest(['username' => 'x']));
    }

    public function test_timestamp_tolerance(): void
    {
        $s = $this->service();
        $this->assertTrue($s->timestampValid(time()));
        $this->assertFalse($s->timestampValid(time() - 10000));
        $this->assertFalse($s->timestampValid('not-a-number'));
    }
}
