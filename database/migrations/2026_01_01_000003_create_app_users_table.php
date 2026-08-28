<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * app_users = 被你的程序校验的“最终用户账号”。
     * 与后台管理员(admins)区分开。
     */
    public function up(): void
    {
        Schema::create('app_users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');                 // bcrypt 哈希
            $table->string('email')->nullable();
            // 账号有效期：为空表示尚未激活；过期即校验失败
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_banned')->default(false);
            // 机器码绑定（可选）：首次登录写入，之后校验一致性，防共享账号
            $table->string('machine_code')->nullable();
            // 允许绑定的机器数量（0 = 不限制）
            $table->unsignedSmallInteger('max_devices')->default(1);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_users');
    }
};
