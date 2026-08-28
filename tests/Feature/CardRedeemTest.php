<?php

namespace Tests\Feature;

use App\Models\AppUser;
use App\Models\CardKey;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class CardRedeemTest extends TestCase
{
    use RefreshDatabase;

    private function service(): LicenseService
    {
        return app(LicenseService::class);
    }

    public function test_redeem_extends_existing_user(): void
    {
        $user = AppUser::factory()->create([
            'username'   => 'alice',
            'expires_at' => now()->addDays(5),
        ]);
        $card = CardKey::factory()->create(['duration_days' => 30]);

        $before = $user->expires_at->copy();
        $updated = $this->service()->redeemCard($card->code, 'alice', null);

        // 在原有效期基础上 +30 天
        $this->assertEqualsWithDelta(
            $before->addDays(30)->timestamp,
            $updated->expires_at->timestamp,
            5
        );
        $this->assertDatabaseHas('card_keys', ['id' => $card->id, 'status' => 'used']);
    }

    public function test_redeem_expired_user_counts_from_now(): void
    {
        AppUser::factory()->expired()->create(['username' => 'bob']);
        $card = CardKey::factory()->create(['duration_days' => 10]);

        $updated = $this->service()->redeemCard($card->code, 'bob', null);

        $this->assertEqualsWithDelta(
            now()->addDays(10)->timestamp,
            $updated->expires_at->timestamp,
            5
        );
    }

    public function test_redeem_auto_registers_when_allowed(): void
    {
        $card = CardKey::factory()->create(['duration_days' => 30]);

        $user = $this->service()->redeemCard($card->code, 'newbie', 'password123', register: true);

        $this->assertDatabaseHas('app_users', ['username' => 'newbie']);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_redeem_nonexistent_user_without_register_fails(): void
    {
        $card = CardKey::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->service()->redeemCard($card->code, 'ghost', null);
    }

    public function test_used_card_cannot_be_redeemed_again(): void
    {
        AppUser::factory()->create(['username' => 'alice']);
        $card = CardKey::factory()->used()->create();

        $this->expectException(RuntimeException::class);
        $this->service()->redeemCard($card->code, 'alice', null);
    }

    public function test_invalid_code_fails(): void
    {
        AppUser::factory()->create(['username' => 'alice']);

        $this->expectException(RuntimeException::class);
        $this->service()->redeemCard('NON-EXIST-CODE-XXXX', 'alice', null);
    }
}
