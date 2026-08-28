<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // 商品名称，如「月卡授权」
            $table->string('slug')->unique();             // URL 友好标识
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);  // 售价
            // 该商品对应的授权时长（天）。下单成功后延长账号有效期。
            $table->unsignedInteger('duration_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
