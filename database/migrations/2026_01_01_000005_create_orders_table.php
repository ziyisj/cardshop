<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 32)->unique();       // 订单号
            $table->foreignId('product_id')->constrained('products');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('amount', 10, 2);               // 应付总额
            $table->string('contact')->nullable();          // 买家联系方式（邮箱/QQ）
            // 状态：pending=待支付 paid=已支付已发货 failed=失败 canceled=取消
            $table->enum('status', ['pending', 'paid', 'failed', 'canceled'])->default('pending');
            $table->string('pay_method')->nullable();       // 支付方式
            $table->string('trade_no')->nullable();         // 第三方交易号
            $table->timestamp('paid_at')->nullable();
            // 发货内容（卡密明文，多张用换行分隔）
            $table->text('delivered_cards')->nullable();
            $table->string('buyer_ip', 45)->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
