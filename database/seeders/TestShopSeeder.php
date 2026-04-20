<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestShopSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | 1) Categories (5)
            |--------------------------------------------------------------------------
            */
            $categories = [
                ['name' => 'Clothing', 'slug' => 'clothing'],
                ['name' => 'Shoes', 'slug' => 'shoes'],
                ['name' => 'Bags', 'slug' => 'bags'],
                ['name' => 'Accessories', 'slug' => 'accessories'],
                ['name' => 'Electronics', 'slug' => 'electronics'],
            ];

            foreach ($categories as &$cat) {
                $cat['is_active'] = 1;
                $cat['created_at'] = now();
                $cat['updated_at'] = now();
            }

            DB::table('categories')->insert($categories);

            // Clothing ID = 1 → use it for all products
            $clothingId = 1;


            /*
            |--------------------------------------------------------------------------
            | 2) Attributes (Color / Size)
            |--------------------------------------------------------------------------
            */
            $colorAttr = DB::table('attributes')->insertGetId([
                'name' => 'Color',
                'slug' => 'color',
                'type' => 'select',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $sizeAttr = DB::table('attributes')->insertGetId([
                'name' => 'Size',
                'slug' => 'size',
                'type' => 'select',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);


            /*
            |--------------------------------------------------------------------------
            | 3) Attribute Values (Red, Blue, Green / S, M, L)
            |--------------------------------------------------------------------------
            */
            $colors = ['Red', 'Blue', 'Green'];
            $sizes = ['S', 'M', 'L'];

            $colorValueIds = [];
            foreach ($colors as $c) {
                $colorValueIds[$c] = DB::table('attribute_values')->insertGetId([
                    'attribute_id' => $colorAttr,
                    'value' => $c,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            $sizeValueIds = [];
            foreach ($sizes as $s) {
                $sizeValueIds[$s] = DB::table('attribute_values')->insertGetId([
                    'attribute_id' => $sizeAttr,
                    'value' => $s,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 4) 20 Products + Attributes + Variants
            |--------------------------------------------------------------------------
            */
            $productIds = [];

            for ($i = 1; $i <= 20; $i++) {

                $productId = DB::table('products')->insertGetId([
                    'name' => "T-Shirt Model $i",
                    'slug' => "tshirt-model-$i",
                    'category_id' => $clothingId,
                    'price' => 100000,
                    'stock' => 0,
                    'sku' => "TSHIRT-$i",
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $productIds[] = $productId;

                // Connect attributes to product
                DB::table('product_attributes')->insert([
                    ['product_id' => $productId, 'attribute_id' => $colorAttr],
                    ['product_id' => $productId, 'attribute_id' => $sizeAttr],
                ]);

                // Create 3 Colors × 3 Sizes = 9 Variants
                foreach ($colors as $c) {
                    foreach ($sizes as $s) {

                        $variantId = DB::table('product_variants')->insertGetId([
                            'product_id' => $productId,
                            'name' => "T-Shirt $c $s",
                            'sku' => "TSHIRT-$i-$c-$s",
                            'price' => 110000,
                            'stock' => rand(5, 20),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Variant Value (Color + Size)
                        DB::table('product_variant_values')->insert([
                            [
                                'product_variant_id' => $variantId,
                                'attribute_value_id' => $colorValueIds[$c],
                            ],
                            [
                                'product_variant_id' => $variantId,
                                'attribute_value_id' => $sizeValueIds[$s],
                            ]
                        ]);
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 5) User + Address
            |--------------------------------------------------------------------------
            */
            $userId = DB::table('users')->insertGetId([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '09120000000',
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $addressId = DB::table('user_addresses')->insertGetId([
                'user_id' => $userId,
                'receiver_name' => 'Test User',
                'receiver_phone' => '09123456789',
                'province' => 'Tehran',
                'city' => 'Tehran',
                'address' => 'Test Address - Valiasr Street',
                'postal_code' => '1234567890',
                'is_default' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);


            /*
            |--------------------------------------------------------------------------
            | 6) Order + Order Items
            |--------------------------------------------------------------------------
            */
            $orderId = DB::table('orders')->insertGetId([
                'user_id' => $userId,
                'address_id' => $addressId,
                'status' => 'pending',
                'total_amount' => 350000,
                'final_amount' => 350000,
                'shipping_amount' => 0,
                'discount_amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Add 3 items from 3 random products
            foreach (array_slice($productIds, 0, 3) as $pId) {
                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'product_id' => $pId,
                    'quantity' => 1,
                    'price' => 110000,
                    'total_price' => 110000,
                    'product_name' => "Product #$pId",
                    'sku' => "SKU-$pId",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }


            DB::commit();
        } catch (\Exception $e) {

            DB::rollBack();
            throw $e;
        }
    }
}
