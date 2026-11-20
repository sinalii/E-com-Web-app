<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySpecificationSeeder extends Seeder
{
    public function run(): void
    {
        $spec = json_decode(
            file_get_contents(database_path('seeders/data/category_filter_spec.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        $leafIds = [];
        $filterRows = [];

        foreach ($spec['categories'] as $categorySpec) {
            $rootSlug = Str::slug($categorySpec['name']);
            $rootId = $this->category($rootSlug, $categorySpec['name'], null);

            foreach ($categorySpec['subcategories'] as $subCategorySpec) {
                $subCategoryName = $subCategorySpec['name'];
                $leafSlug = Str::slug($categorySpec['name'].' '.$subCategoryName);
                $leafId = $this->category($leafSlug, $subCategoryName, $rootId);
                $leafIds[$categorySpec['name']][$subCategoryName] = $leafId;

                $common = [
                    ['Price', 'range'],
                    ['Customer feedback', 'rating'],
                    ['Shipping', 'shipping'],
                    ['Promotions', 'promotion'],
                    ['Availability', 'availability'],
                ];
                $seen = [];
                foreach ($common as $position => [$name, $type]) {
                    $slug = Str::slug($name);
                    $filterRows[] = $this->filterRow($leafId, $name, $slug, $type, $position, true);
                    $seen[$slug] = true;
                }

                foreach ($this->splitFilters($subCategorySpec['filters']) as $name) {
                    $key = Str::slug($name);
                    if (! $key || isset($seen[$key]) || in_array(mb_strtolower($name), ['sort', 'product attributes'], true)) {
                        continue;
                    }
                    $seen[$key] = true;
                    $filterRows[] = $this->filterRow($leafId, $name, $key, 'attribute', count($seen) + 4, false);
                }
            }
        }

        foreach (array_chunk($filterRows, 350) as $rows) {
            DB::table('category_filter')->upsert(
                $rows,
                ['category_id', 'slug'],
                ['name', 'input_type', 'sort_order', 'is_common']
            );
        }

        $this->moveSampleProducts($leafIds);
        DB::table('category')->whereIn('slug', ['smart-home', 'audio', 'computing', 'wearables'])->update(['is_active' => false]);
        $this->seedSampleAttributes();
    }

    private function category(string $slug, string $name, ?int $parentId): int
    {
        $existing = DB::table('category')->where('slug', $slug)->first();
        if ($existing) {
            DB::table('category')->where('id', $existing->id)->update([
                'name' => $name,
                'parent_id' => $parentId,
                'is_active' => true,
            ]);

            return (int) $existing->id;
        }

        return (int) DB::table('category')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $parentId,
            'is_active' => true,
        ]);
    }

    private function filterRow(int $categoryId, string $name, string $slug, string $type, int $sort, bool $common): array
    {
        return [
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'input_type' => $type,
            'sort_order' => $sort,
            'is_common' => $common,
        ];
    }

    private function splitFilters(string $filters): array
    {
        $parts = [];
        $buffer = '';
        $depth = 0;
        foreach (mb_str_split($filters) as $character) {
            if ($character === '(') $depth++;
            if ($character === ')') $depth = max(0, $depth - 1);
            if ($character === ',' && $depth === 0) {
                if (trim($buffer) !== '') $parts[] = trim($buffer);
                $buffer = '';
                continue;
            }
            $buffer .= $character;
        }
        if (trim($buffer) !== '') $parts[] = trim($buffer);

        return $parts;
    }

    private function moveSampleProducts(array $leafIds): void
    {
        $targets = [
            'smart-home-essentials' => ['Consumer Electronics', 'Smart devices & wearables'],
            'mini-wifi-speaker' => ['Consumer Electronics', 'Speakers & audio'],
            'smart-led-light-kit' => ['Lights & Lighting', 'Smart lighting'],
            'jbl-wireless-headphones' => ['Consumer Electronics', 'Headphones & earphones'],
            'studio-wireless-earbuds' => ['Consumer Electronics', 'Headphones & earphones'],
            'portable-bluetooth-speaker' => ['Consumer Electronics', 'Speakers & audio'],
            'apple-slim-laptop' => ['Computer & Office', 'Laptops'],
            'ultra-wide-monitor' => ['Computer & Office', 'Monitors & displays'],
            'mechanical-keyboard' => ['Computer & Office', 'Keyboards & mice'],
            'apple-smartwatch-series-9' => ['Watches', 'Smart watches'],
            'fitness-watch-pro' => ['Watches', 'Smart watches'],
            'everyday-smart-band' => ['Watches', 'Smart watches'],
        ];

        foreach ($targets as $productSlug => [$parent, $leaf]) {
            $product = DB::table('product')->where('slug', $productSlug)->first();
            $categoryId = $leafIds[$parent][$leaf] ?? null;
            if ($product && $categoryId) {
                DB::table('product')->where('id', $product->id)->update(['category_id' => $categoryId]);
            }
        }
    }

    private function seedSampleAttributes(): void
    {
        $attributes = [
            'smart-home-essentials' => ['Brand' => 'Marketly Home', 'Device type' => 'Smart home bundle', 'Compatibility' => 'Wi-Fi', 'Connectivity' => 'Wi-Fi', 'Battery life' => 'Mains powered', 'Sensors' => 'Voice control', 'Colour' => 'Black'],
            'mini-wifi-speaker' => ['Brand' => 'Marketly Audio', 'Speaker type' => 'Smart speaker', 'Output power' => '15 W', 'Channels' => '2.0', 'Connectivity' => 'Wi-Fi', 'Bluetooth' => 'Yes', 'Battery' => 'Mains powered', 'Size' => 'Compact'],
            'smart-led-light-kit' => ['Brand' => 'Marketly Home', 'Smart-home compatibility' => 'Wi-Fi', 'Connectivity' => 'Wi-Fi', 'Voice assistant' => 'Voice control', 'Colour options' => 'RGB', 'Brightness' => 'Adjustable', 'Wattage' => '12 W'],
            'jbl-wireless-headphones' => ['Brand' => 'JBL', 'Type' => 'Over-ear', 'Connectivity' => 'Bluetooth', 'Bluetooth version' => '5.3', 'Noise cancellation' => 'Yes', 'Microphone' => 'Yes', 'Battery life' => '40 hours', 'Waterproof rating' => 'Not rated', 'Colour' => 'Black'],
            'studio-wireless-earbuds' => ['Brand' => 'Marketly Audio', 'Type' => 'In-ear', 'Connectivity' => 'Bluetooth', 'Bluetooth version' => '5.3', 'Noise cancellation' => 'No', 'Microphone' => 'Yes', 'Battery life' => '8 hours', 'Waterproof rating' => 'IPX4', 'Colour' => 'White'],
            'portable-bluetooth-speaker' => ['Brand' => 'Marketly Audio', 'Speaker type' => 'Portable Bluetooth', 'Output power' => '20 W', 'Channels' => '2.0', 'Connectivity' => 'Bluetooth', 'Bluetooth' => 'Yes', 'Battery' => '12 hours', 'Waterproof rating' => 'IPX7', 'Size' => 'Portable'],
            'apple-slim-laptop' => ['Brand' => 'Apple', 'Model' => 'Slim 13', 'Screen size' => '13 inch', 'Processor/CPU' => 'Apple Silicon', 'RAM' => '16 GB', 'Storage' => '512 GB SSD', 'Graphics/GPU' => 'Integrated', 'Operating system' => 'macOS', 'Resolution' => '2560 × 1664', 'Refresh rate' => '60 Hz', 'Weight' => '1.3 kg', 'Condition' => 'New'],
            'ultra-wide-monitor' => ['Brand' => 'Marketly Display', 'Screen size' => '34 inch', 'Resolution' => '3440 × 1440', 'Panel type' => 'IPS', 'Refresh rate' => '144 Hz', 'Response time' => '1 ms', 'Aspect ratio' => '21:9', 'Ports' => 'HDMI, DisplayPort', 'Curved/flat' => 'Curved'],
            'mechanical-keyboard' => ['Brand' => 'Marketly Tech', 'Connection' => 'USB-C', 'Layout/language' => 'US English', 'Switch type' => 'Mechanical', 'Keyboard size' => 'Full size', 'Colour' => 'Black'],
            'apple-smartwatch-series-9' => ['Brand' => 'Apple', 'Compatibility' => 'iOS', 'Operating system' => 'watchOS', 'Display size' => '45 mm', 'Connectivity' => 'Bluetooth, Wi-Fi', 'Battery life' => '18 hours', 'Health sensors' => 'Heart rate, ECG', 'GPS' => 'Yes', 'Water resistance' => '50 m', 'Strap size' => 'M/L'],
            'fitness-watch-pro' => ['Brand' => 'Marketly Wear', 'Compatibility' => 'Android, iOS', 'Operating system' => 'Proprietary', 'Display size' => '1.4 inch', 'Connectivity' => 'Bluetooth', 'Battery life' => '7 days', 'Health sensors' => 'Heart rate, SpO2', 'GPS' => 'Yes', 'Water resistance' => '5 ATM', 'Strap size' => 'Universal'],
            'everyday-smart-band' => ['Brand' => 'Marketly Wear', 'Compatibility' => 'Android, iOS', 'Operating system' => 'Proprietary', 'Display size' => '1.1 inch', 'Connectivity' => 'Bluetooth', 'Battery life' => '10 days', 'Health sensors' => 'Heart rate', 'GPS' => 'No', 'Water resistance' => '5 ATM', 'Strap size' => 'Universal'],
        ];

        foreach ($attributes as $productSlug => $values) {
            $product = DB::table('product')->where('slug', $productSlug)->first();
            if (! $product) continue;
            foreach ($values as $name => $value) {
                $exists = DB::table('product_attribute')->where('product_id', $product->id)
                    ->where('name', $name)->where('value', $value)->exists();
                if (! $exists) {
                    DB::table('product_attribute')->insert([
                        'product_id' => $product->id,
                        'name' => $name,
                        'value' => $value,
                        'is_variant' => false,
                        'created_at' => now(),
                    ]);
                }
            }
        }
    }
}
