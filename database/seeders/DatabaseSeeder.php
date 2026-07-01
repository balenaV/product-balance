<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $now = now();

        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ]
        );

        DB::table('products')->upsert([
            [
                'business_id' => 1,
                'name' => 'Camiseta Azul - P',
                'sku' => 'CAM-AZUL',
                'variation_id' => 101,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'business_id' => 1,
                'name' => 'Camiseta Azul - M',
                'sku' => 'CAM-AZUL',
                'variation_id' => 102,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'business_id' => 1,
                'name' => 'Tenis Preto - 40',
                'sku' => 'TEN-PRETO',
                'variation_id' => 201,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'business_id' => 2,
                'name' => 'Produto de Outra Empresa',
                'sku' => 'OUTRA-EMPRESA',
                'variation_id' => 301,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['business_id', 'sku', 'variation_id'], ['name', 'updated_at']);

        DB::table('product_balances')->updateOrInsert(
            ['id' => 1],
            [
                'business_id' => 1,
                'status' => 'in_progress',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}

