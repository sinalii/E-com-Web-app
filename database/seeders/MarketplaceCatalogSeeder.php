<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarketplaceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            ['smart-home', 'Smart Home', [
                ['smart-home-essentials', 'Smart Home Essentials Bundle', 'Smart lighting and sound for a more connected home.', 18990, 'photo-1558002038-1055907df827', 25],
                ['mini-wifi-speaker', 'Mini Wi-Fi Smart Speaker', 'Room-filling sound with voice control.', 8490, 'photo-1543512214-318c7553f230', 40],
                ['smart-led-light-kit', 'Smart LED Light Kit', 'Set the mood with millions of colors.', 4290, 'photo-1507473885765-e6ed057f782c', 60],
            ]],
            ['audio', 'Audio', [
                ['jbl-wireless-headphones', 'JBL Wireless Headphones', 'Immersive sound with all-day comfort.', 12990, 'photo-1505740420928-5e560c06d30e', 35],
                ['studio-wireless-earbuds', 'Studio Wireless Earbuds', 'Compact, clear sound wherever you go.', 6990, 'photo-1606220945770-b5b6c2c55bf1', 50],
                ['portable-bluetooth-speaker', 'Portable Bluetooth Speaker', 'Big sound, ready for the outdoors.', 9990, 'photo-1608043152269-423dbba4e7e1', 30],
            ]],
            ['computing', 'Computing', [
                ['apple-slim-laptop', 'Apple Slim Laptop', 'Lightweight performance for work and play.', 189900, 'photo-1517336714731-489689fd1ca8', 12],
                ['ultra-wide-monitor', 'Ultra-wide Monitor', 'A vivid, expansive workspace.', 79900, 'photo-1527443224154-c4a3942d3acf', 18],
                ['mechanical-keyboard', 'Mechanical Keyboard', 'A satisfying upgrade for your desk.', 15900, 'photo-1587829741301-dc798b83add3', 45],
            ]],
            ['wearables', 'Wearables', [
                ['apple-smartwatch-series-9', 'Apple Smartwatch Series 9', 'Stay connected and keep your day on track.', 89900, 'photo-1523275335684-37898b6baf30', 20],
                ['fitness-watch-pro', 'Fitness Watch Pro', 'Training insights and a bright AMOLED display.', 24900, 'photo-1575311373937-040b8e1fd5b6', 30],
                ['everyday-smart-band', 'Everyday Smart Band', 'Simple wellness tracking, all day long.', 11900, 'photo-1508685096489-7aacd43bd3b1', 50],
            ]],
        ];

        foreach ($catalog as [$categorySlug, $categoryName, $products]) {
            $category = DB::table('category')->where('slug', $categorySlug)->first();
            $categoryId = $category?->id ?? DB::table('category')->insertGetId([
                'name' => $categoryName,
                'slug' => $categorySlug,
                'is_active' => true,
            ]);

            foreach ($products as [$slug, $name, $description, $price, $imageId, $stock]) {
                $product = DB::table('product')->where('slug', $slug)->first();
                $productId = $product?->id ?? DB::table('product')->insertGetId([
                    'category_id' => $categoryId,
                    'name' => $name,
                    'description' => $description,
                    'slug' => $slug,
                    'sku' => strtoupper(str_replace('-', '', $slug)),
                    'status' => 'active',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $variantSku = 'VAR-'.strtoupper(str_replace('-', '', $slug));
                if (! DB::table('product_variant')->where('sku', $variantSku)->exists()) {
                    DB::table('product_variant')->insert([
                        'product_id' => $productId,
                        'sku' => $variantSku,
                        'price' => $price,
                        'sale_price' => null,
                        'stock_quantity' => $stock,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if (! DB::table('product_image')->where('product_id', $productId)->exists()) {
                    DB::table('product_image')->insert([
                        'product_id' => $productId,
                        'image_url' => 'https://images.unsplash.com/'.$imageId.'?auto=format&fit=crop&w=800&q=85',
                        'is_primary' => true,
                        'sort_order' => 1,
                        'created_at' => now(),
                    ]);
                }
            }
        }
    }
}
