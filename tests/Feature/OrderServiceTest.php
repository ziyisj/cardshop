<?php

namespace Tests\Feature;

use App\Models\CardKey;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): OrderService
    {
        return app(OrderService::class);
    }

    public function test_create_order_fails_without_stock(): void
    {
        $product = Product::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->service()->createOrder($product, 1, null, '127.0.0.1');
    }

    public function test_create_and_deliver_order(): void
    {
        $product = Product::factory()->create(['price' => 20]);
        CardKey::factory()->count(3)->create([
            'product_id' => $product->id,
            'status'     => 'unused',
        ]);

        $order = $this->service()->createOrder($product, 2, 'buyer@example.com', '127.0.0.1');
        $this->assertSame('pending', $order->status);
        $this->assertEquals('40.00', $order->amount);

        $delivered = $this->service()->markPaidAndDeliver($order, 'test');

        $this->assertSame('paid', $delivered->status);
        $codes = explode("\n", trim($delivered->delivered_cards));
        $this->assertCount(2, $codes);

        // 已售出的卡数量正确
        $this->assertSame(2, CardKey::where('status', 'sold')->count());
        $this->assertSame(1, CardKey::where('status', 'unused')->count());
    }

    public function test_deliver_is_idempotent(): void
    {
        $product = Product::factory()->create();
        CardKey::factory()->create(['product_id' => $product->id, 'status' => 'unused']);

        $order = $this->service()->createOrder($product, 1, null, '127.0.0.1');
        $first  = $this->service()->markPaidAndDeliver($order, 'test');
        $second = $this->service()->markPaidAndDeliver($order->refresh(), 'test');

        // 幂等：第二次不再多发卡
        $this->assertSame($first->delivered_cards, $second->delivered_cards);
        $this->assertSame(1, CardKey::where('status', 'sold')->count());
    }
}
