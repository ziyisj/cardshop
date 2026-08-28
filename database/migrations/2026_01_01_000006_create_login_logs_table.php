<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->string('username')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('machine_code')->nullable();
            $table->boolean('success')->default(false);
            $table->string('reason')->nullable();           // 失败原因
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
