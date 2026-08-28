<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\CardKey;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 管理员
        Admin::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name'     => 'Administrator',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'admin123456')),
            ]
        );

        // 示例商品
        $products = [
            ['name' => '月卡授权', 'price' => 15.00, 'duration_days' => 30],
            ['name' => '季卡授权', 'price' => 40.00, 'duration_days' => 90],
            ['name' => '年卡授权', 'price' => 128.00, 'duration_days' => 365],
        ];

        foreach ($products as $p) {
            $product = Product::updateOrCreate(
                ['slug' => Str::slug($p['name']) . '-' . strtolower(Str::random(4))],
                [
                    'name'          => $p['name'],
                    'description'   => $p['name'] . '，自动发货。',
                    'price'         => $p['price'],
                    'duration_days' => $p['duration_days'],
                    'is_active'     => true,
                ]
            );

            // 每个商品预生成 20 张卡密
            $rows = [];
            for ($i = 0; $i < 20; $i++) {
                $rows[] = [
                    'code'          => CardKey::generateCode(),
                    'product_id'    => $product->id,
                    'duration_days' => $product->duration_days,
                    'status'        => 'unused',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }
            CardKey::insert($rows);
        }
    }
}
