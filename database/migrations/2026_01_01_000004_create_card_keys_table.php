<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_keys', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();          // 卡密明文（发货给用户）
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            // 该卡对应授权天数，独立于商品可覆盖
            $table->unsignedInteger('duration_days')->default(30);
            // 状态：unused=未使用 sold=已售出待激活 used=已激活 disabled=作废
            $table->enum('status', ['unused', 'sold', 'used', 'disabled'])->default('unused');
            // 使用者账号（激活后写入）
            $table->foreignId('used_by')->nullable()->constrained('app_users')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            // 卡自身的有效期（不是激活后账号的有效期），用于限时销售
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_keys');
    }
};
