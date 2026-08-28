<?php

namespace App\Services;

use App\Models\CardKey;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderService
{
    /** 创建待支付订单（先校验库存，不锁定卡密）。 */
    public function createOrder(Product $product, int $quantity, ?string $contact, ?string $ip): Order
    {
        if (! $product->is_active) {
            throw new RuntimeException('商品已下架');
        }
        if ($quantity < 1) {
            throw new RuntimeException('购买数量不合法');
        }
        if ($product->stock() < $quantity) {
            throw new RuntimeException('库存不足');
        }

        return Order::create([
            'order_no'   => Order::generateOrderNo(),
            'product_id' => $product->id,
            'quantity'   => $quantity,
            'amount'     => bcmul((string) $product->price, (string) $quantity, 2),
            'contact'    => $contact,
            'status'     => 'pending',
            'buyer_ip'   => $ip,
        ]);
    }

    /**
     * 标记支付成功并自动发货：原子地取出对应数量的未使用卡密。
     * 真实环境中应由支付网关异步回调触发。
     */
    public function markPaidAndDeliver(Order $order, string $payMethod = 'manual', ?string $tradeNo = null): Order
    {
        return DB::transaction(function () use ($order, $payMethod, $tradeNo) {
            /** @var Order $order */
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($order->status === 'paid') {
                return $order; // 幂等
            }
            if ($order->status !== 'pending') {
                throw new RuntimeException('订单状态不可支付');
            }

            $cards = CardKey::where('product_id', $order->product_id)
                ->where('status', 'unused')
                ->lockForUpdate()
                ->limit($order->quantity)
                ->get();

            if ($cards->count() < $order->quantity) {
                throw new RuntimeException('库存不足，无法发货');
            }

            $codes = [];
            foreach ($cards as $card) {
                $card->update(['status' => 'sold']);
                $codes[] = $card->code;
            }

            $order->update([
                'status'          => 'paid',
                'pay_method'      => $payMethod,
                'trade_no'        => $tradeNo,
                'paid_at'         => now(),
                'delivered_cards' => implode("\n", $codes),
            ]);

            return $order->refresh();
        });
    }
}
