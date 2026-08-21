<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class StorefrontStaticContentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedWeightMaster();
            $categoryIds = $this->seedCategories();
            $this->seedProducts($categoryIds);
        });
    }

    private function now(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    private function seedWeightMaster(): void
    {
        foreach ([
            '100 Grams',
            '250 Grams',
            '500 Grams',
            '1 KG',
            'Each One',
            '5 Pieces',
            '10 Pieces',
            'Each Bunch',
            'Each Packet',
            'Small Bunch',
            'Premium Bunch',
        ] as $unit) {
            $existing = DB::table('weights')->where('name', $unit)->first();

            if ($existing) {
                DB::table('weights')->where('id', $existing->id)->update([
                    'status' => 1,
                    'updated_at' => $this->now(),
                ]);
                continue;
            }

            DB::table('weights')->insert([
                'name' => $unit,
                'status' => 1,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }
    }

    private function seedCategories(): array
    {
        $parents = [
            [
                'title' => 'Puja Flowers',
                'slug' => 'puja-flowers',
                'short_description' => 'Chamanthi, Banthi, Kanakambaram, lotus and daily puja flowers.',
                'image' => 'category-daily-puja.jpg',
                'source' => 'category-daily-puja.jpg',
                'priority' => 1,
                'home_category' => 1,
            ],
            [
                'title' => 'Premium Flowers',
                'slug' => 'premium-flowers',
                'short_description' => 'Roses, lilies, orchids, tulips and imported premium blooms.',
                'image' => 'category-premium.jpg',
                'source' => 'category-premium.jpg',
                'priority' => 2,
                'home_category' => 1,
            ],
            [
                'title' => 'Rare Flowers',
                'slug' => 'rare-flowers',
                'short_description' => 'Limited seasonal flowers for rituals and special occasions.',
                'image' => 'category-rare.jpg',
                'source' => 'category-rare.jpg',
                'priority' => 3,
                'home_category' => 1,
            ],
            [
                'title' => 'Patri & Leaves',
                'slug' => 'patri-leaves',
                'short_description' => 'Tulasi, bilva, mango leaves and fresh patri combinations.',
                'image' => 'category-patri.jpg',
                'source' => 'category-patri.jpg',
                'priority' => 4,
                'home_category' => 1,
            ],
            [
                'title' => 'Bouquets & Gifting',
                'slug' => 'bouquets-gifting',
                'short_description' => 'Fresh flower gifts, bouquets and celebration-ready blooms.',
                'image' => 'category-gifting.jpg',
                'source' => 'category-gifting.jpg',
                'priority' => 5,
                'home_category' => 1,
            ],
            [
                'title' => 'Garlands',
                'slug' => 'garlands',
                'short_description' => 'Fresh garlands and temple flower support.',
                'image' => 'category-temple.jpg',
                'source' => 'category-temple.jpg',
                'priority' => 6,
                'home_category' => 1,
            ],
        ];

        $categoryIds = [];

        foreach ($parents as $category) {
            $category['image'] = $this->copySeedImage($category['source'], $category['image'], 'categories');
            $categoryIds[$category['slug']] = $this->upsertCategory($category);
        }

        $children = [
            ['title' => 'Chamanthi', 'slug' => 'chamanthi', 'parent' => 'puja-flowers', 'priority' => 11],
            ['title' => 'Roses', 'slug' => 'roses', 'parent' => 'puja-flowers', 'priority' => 12],
            ['title' => 'Banthi', 'slug' => 'banthi', 'parent' => 'puja-flowers', 'priority' => 13],
            ['title' => 'Kanakambaram', 'slug' => 'kanakambaram', 'parent' => 'puja-flowers', 'priority' => 14],
            ['title' => 'Lotus', 'slug' => 'lotus', 'parent' => 'puja-flowers', 'priority' => 15],
            ['title' => 'Jasmine / Malli', 'slug' => 'jasmine-malli', 'parent' => 'rare-flowers', 'priority' => 21],
            ['title' => 'Seasonal Flowers', 'slug' => 'seasonal-flowers', 'parent' => 'rare-flowers', 'priority' => 22],
            ['title' => 'Tuberose', 'slug' => 'tuberose', 'parent' => 'rare-flowers', 'priority' => 23],
            ['title' => 'Sampangi', 'slug' => 'sampangi', 'parent' => 'rare-flowers', 'priority' => 24],
            ['title' => 'Tulips', 'slug' => 'tulips', 'parent' => 'premium-flowers', 'priority' => 31],
            ['title' => 'Orchids', 'slug' => 'orchids', 'parent' => 'premium-flowers', 'priority' => 32],
            ['title' => 'Lilies', 'slug' => 'lilies', 'parent' => 'premium-flowers', 'priority' => 33],
            ['title' => 'Imported / Exotic Flowers', 'slug' => 'imported-exotic-flowers', 'parent' => 'premium-flowers', 'priority' => 34],
        ];

        foreach ($children as $category) {
            $categoryIds[$category['slug']] = $this->upsertCategory([
                'title' => $category['title'],
                'slug' => $category['slug'],
                'short_description' => $category['title'].' flower category.',
                'image' => null,
                'priority' => $category['priority'],
                'home_category' => 0,
                'parent_id' => $categoryIds[$category['parent']] ?? null,
            ]);
        }

        return $categoryIds;
    }

    private function upsertCategory(array $category): int
    {
        $existing = DB::table('categories')
            ->where('slug', $category['slug'])
            ->orWhere('name', $category['slug'])
            ->first();

        $payload = [
            'name' => $category['slug'],
            'title' => $category['title'],
            'slug' => $category['slug'],
            'short_description' => $category['short_description'] ?? null,
            'description' => $category['description'] ?? $category['short_description'] ?? null,
            'image' => $category['image'] ?? null,
            'home_category' => $category['home_category'] ?? 0,
            'parent_id' => $category['parent_id'] ?? null,
            'priority' => $category['priority'] ?? null,
            'status' => 1,
            'updated_at' => $this->now(),
        ];

        if ($existing) {
            DB::table('categories')->where('id', $existing->id)->update($payload);
            return (int) $existing->id;
        }

        return (int) DB::table('categories')->insertGetId(array_merge($payload, [
            'created_at' => $this->now(),
        ]));
    }

    private function seedProducts(array $categoryIds): void
    {
        foreach ($this->products() as $index => $product) {
            $imageName = $this->copySeedImage($product['image_source'], $product['image_name'], 'products');
            $productId = $this->upsertProduct($product, $index + 1);

            if ($imageName) {
                $this->ensureProductImage($productId, $imageName);
            }

            foreach ($product['weights'] as $priority => $weight) {
                $this->ensureProductWeight($productId, $weight, $priority + 1);
            }

            foreach ($product['categories'] as $categorySlug) {
                if (!empty($categoryIds[$categorySlug])) {
                    $this->ensureProductCategory($productId, $categoryIds[$categorySlug]);
                }
            }

            $this->ensureFeaturedProduct($productId, $product['featured_priority'] ?? ($index + 1));
        }
    }

    private function upsertProduct(array $product, int $priority): int
    {
        $existing = DB::table('products')
            ->where('slug', $product['slug'])
            ->orWhere('sku', $product['sku'])
            ->first();

        $firstWeight = $product['weights'][0];
        $insertPayload = [
            'brand_id' => null,
            'title' => $product['title'],
            'sku' => $product['sku'],
            'qty' => 100,
            'short_description' => $product['short_description'],
            'description' => $product['description'],
            'sell_price' => $firstWeight['sell_price'],
            'list_price' => $firstWeight['list_price'],
            'cost_price' => $firstWeight['cost_price'] ?? 0,
            'size' => null,
            'weight' => $firstWeight['name'],
            'vat_enable' => 0,
            'vat_price' => 0,
            'stock' => 1,
            'search_keywords' => implode(',', $product['keywords']),
            'slug' => $product['slug'],
            'priority' => $priority,
            'status' => 1,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];

        if (!$existing) {
            return (int) DB::table('products')->insertGetId($insertPayload);
        }

        $safeUpdate = [
            'status' => 1,
            'stock' => 1,
            'updated_at' => $this->now(),
        ];

        foreach (['short_description', 'description', 'search_keywords', 'priority', 'qty'] as $field) {
            if (empty($existing->{$field})) {
                $safeUpdate[$field] = $insertPayload[$field];
            }
        }

        DB::table('products')->where('id', $existing->id)->update($safeUpdate);

        return (int) $existing->id;
    }

    private function ensureProductImage(int $productId, string $imageName): void
    {
        $existingImage = DB::table('product_images')
            ->where('product_id', $productId)
            ->where('name', $imageName)
            ->first();

        if ($existingImage) {
            DB::table('product_images')->where('id', $existingImage->id)->update([
                'status' => 1,
                'updated_at' => $this->now(),
            ]);
            return;
        }

        $activeImageExists = DB::table('product_images')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->exists();

        DB::table('product_images')->insert([
            'product_id' => $productId,
            'name' => $imageName,
            'priority' => $activeImageExists ? 99 : 1,
            'status' => 1,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    private function ensureProductWeight(int $productId, array $weight, int $priority): void
    {
        $existingWeight = DB::table('product_weights')
            ->where('product_id', $productId)
            ->where('name', $weight['name'])
            ->first();

        if ($existingWeight) {
            DB::table('product_weights')->where('id', $existingWeight->id)->update([
                'qty' => $existingWeight->qty ?: 100,
                'stock' => 1,
                'status' => 1,
                'priority' => $existingWeight->priority ?: $priority,
                'updated_at' => $this->now(),
            ]);
            return;
        }

        DB::table('product_weights')->insert([
            'product_id' => $productId,
            'name' => $weight['name'],
            'sell_price' => $weight['sell_price'],
            'list_price' => $weight['list_price'],
            'cost_price' => $weight['cost_price'] ?? 0,
            'qty' => 100,
            'vat_price' => 0,
            'vat_enable' => 0,
            'stock' => 1,
            'priority' => $priority,
            'status' => 1,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    private function ensureProductCategory(int $productId, int $categoryId): void
    {
        $exists = DB::table('category_product')
            ->where('product_id', $productId)
            ->where('category_id', $categoryId)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('category_product')->insert([
            'product_id' => $productId,
            'category_id' => $categoryId,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    private function ensureFeaturedProduct(int $productId, int $priority): void
    {
        $existing = DB::table('featured_products')->where('product_id', $productId)->first();

        if ($existing) {
            DB::table('featured_products')->where('id', $existing->id)->update([
                'priority' => $existing->priority ?: $priority,
                'updated_at' => $this->now(),
            ]);
            return;
        }

        DB::table('featured_products')->insert([
            'product_id' => $productId,
            'priority' => $priority,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    private function copySeedImage(string $sourceRelativePath, string $filename, string $directory): ?string
    {
        $sourcePath = base_path('../public/assets/images/home-v2/'.$sourceRelativePath);

        if (!File::exists($sourcePath)) {
            return null;
        }

        foreach ([
            storage_path('app/public/'.$directory.'/'.$filename),
            public_path('storage/'.$directory.'/'.$filename),
        ] as $destinationPath) {
            File::ensureDirectoryExists(dirname($destinationPath));

            if (!File::exists($destinationPath) || File::size($destinationPath) !== File::size($sourcePath)) {
                File::copy($sourcePath, $destinationPath);
            }
        }

        return $filename;
    }

    private function products(): array
    {
        return [
            [
                'title' => 'Red Roses',
                'slug' => 'red-roses',
                'sku' => 'MF-RED-ROSES',
                'short_description' => 'Fresh red roses for puja, decor and gifting.',
                'description' => 'Fresh red roses selected for daily puja, decorations and special gifting.',
                'keywords' => ['red rose', 'rose', 'roses', 'fresh flowers'],
                'image_source' => 'fresh-arrivals/fresh-red-roses.jpg',
                'image_name' => 'seed-fresh-red-roses.jpg',
                'categories' => ['puja-flowers', 'roses', 'bouquets-gifting'],
                'featured_priority' => 1,
                'weights' => $this->kgWeights(250, 300),
            ],
            [
                'title' => 'Chamanthi Flowers',
                'slug' => 'chamanthi-flowers',
                'sku' => 'MF-CHAMANTHI',
                'short_description' => 'Fresh chamanthi flowers for daily puja and rituals.',
                'description' => 'Fresh chamanthi flowers packed for daily puja, temple use and home rituals.',
                'keywords' => ['chamanthi', 'chrysanthemum', 'puja flowers'],
                'image_source' => 'fresh-arrivals/fresh-chamanthi.jpg',
                'image_name' => 'seed-fresh-chamanthi.jpg',
                'categories' => ['puja-flowers', 'chamanthi'],
                'featured_priority' => 2,
                'weights' => $this->kgWeights(120, 150),
            ],
            [
                'title' => 'Kanakambaram',
                'slug' => 'kanakambaram',
                'sku' => 'MF-KANAKAMBARAM',
                'short_description' => 'Fresh kanakambaram flowers for pooja and traditional use.',
                'description' => 'Fresh kanakambaram flowers suitable for pooja, hair flowers and traditional rituals.',
                'keywords' => ['kanakambaram', 'crossandra', 'rare flowers'],
                'image_source' => 'fresh-arrivals/fresh-kanakambaram.jpg',
                'image_name' => 'seed-fresh-kanakambaram.jpg',
                'categories' => ['puja-flowers', 'kanakambaram', 'rare-flowers', 'seasonal-flowers'],
                'featured_priority' => 3,
                'weights' => $this->kgWeights(250, 300),
            ],
            [
                'title' => 'Lotus Flowers',
                'slug' => 'lotus-flowers',
                'sku' => 'MF-LOTUS',
                'short_description' => 'Fresh lotus flowers for puja and temple offerings.',
                'description' => 'Fresh lotus flowers for temple offerings, puja and special rituals.',
                'keywords' => ['lotus', 'puja flowers', 'rare flowers'],
                'image_source' => 'fresh-arrivals/fresh-lotus.jpg',
                'image_name' => 'seed-fresh-lotus.jpg',
                'categories' => ['puja-flowers', 'lotus', 'rare-flowers'],
                'featured_priority' => 4,
                'weights' => [
                    ['name' => 'Each One', 'sell_price' => 60, 'list_price' => 80],
                    ['name' => '5 Pieces', 'sell_price' => 300, 'list_price' => 400],
                    ['name' => '10 Pieces', 'sell_price' => 600, 'list_price' => 800],
                ],
            ],
            [
                'title' => 'Banthi Flowers',
                'slug' => 'banthi-flowers',
                'sku' => 'MF-BANTHI',
                'short_description' => 'Fresh banthi flowers for puja and decorations.',
                'description' => 'Fresh banthi flowers for puja, temple offering and traditional decoration.',
                'keywords' => ['banthi', 'marigold', 'puja flowers'],
                'image_source' => 'fresh-arrivals/fresh-banthi.jpg',
                'image_name' => 'seed-fresh-banthi.jpg',
                'categories' => ['puja-flowers', 'banthi'],
                'featured_priority' => 5,
                'weights' => $this->kgWeights(120, 150),
            ],
            [
                'title' => 'Yellow Sevanthi',
                'slug' => 'yellow-sevanthi',
                'sku' => 'MF-YELLOW-SEVANTHI',
                'short_description' => 'Fresh yellow sevanthi for daily puja.',
                'description' => 'Fresh yellow sevanthi flowers prepared for daily puja and rituals.',
                'keywords' => ['yellow sevanthi', 'sevanthi', 'chamanthi'],
                'image_source' => 'fresh-arrivals/fresh-yellow-sevanthi.jpg',
                'image_name' => 'seed-fresh-yellow-sevanthi.jpg',
                'categories' => ['puja-flowers', 'chamanthi', 'seasonal-flowers'],
                'featured_priority' => 6,
                'weights' => [
                    ['name' => '100 Grams', 'sell_price' => 60, 'list_price' => 80],
                    ['name' => '250 Grams', 'sell_price' => 150, 'list_price' => 200],
                    ['name' => '500 Grams', 'sell_price' => 300, 'list_price' => 400],
                    ['name' => '1 KG', 'sell_price' => 600, 'list_price' => 800],
                ],
            ],
            [
                'title' => 'Premium Roses',
                'slug' => 'premium-roses',
                'sku' => 'MF-PREMIUM-ROSES',
                'short_description' => 'Premium roses for gifting, decor and special occasions.',
                'description' => 'Handpicked premium roses for gifting, reception decor and special moments.',
                'keywords' => ['premium rose', 'roses', 'premium flowers'],
                'image_source' => 'premium-collection/premium-roses.jpg',
                'image_name' => 'seed-premium-roses.jpg',
                'categories' => ['premium-flowers', 'roses', 'bouquets-gifting'],
                'featured_priority' => 21,
                'weights' => [
                    ['name' => 'Small Bunch', 'sell_price' => 150, 'list_price' => 200],
                    ['name' => 'Premium Bunch', 'sell_price' => 450, 'list_price' => 600],
                ],
            ],
            [
                'title' => 'Tulips',
                'slug' => 'tulips',
                'sku' => 'MF-TULIPS',
                'short_description' => 'Imported tulips for premium gifting and decor.',
                'description' => 'Premium tulips for corporate decor, gifting and special occasions.',
                'keywords' => ['tulip', 'tulips', 'imported flowers'],
                'image_source' => 'premium-collection/premium-tulips.jpg',
                'image_name' => 'seed-premium-tulips.jpg',
                'categories' => ['premium-flowers', 'tulips'],
                'featured_priority' => 22,
                'weights' => [['name' => 'Each Bunch', 'sell_price' => 600, 'list_price' => 750]],
            ],
            [
                'title' => 'Orchids',
                'slug' => 'orchids',
                'sku' => 'MF-ORCHIDS',
                'short_description' => 'Premium orchids for arrangements and gifting.',
                'description' => 'Fresh orchids for premium arrangements, corporate spaces and gifting.',
                'keywords' => ['orchid', 'orchids', 'premium flowers'],
                'image_source' => 'premium-collection/premium-orchids.jpg',
                'image_name' => 'seed-premium-orchids.jpg',
                'categories' => ['premium-flowers', 'orchids'],
                'featured_priority' => 23,
                'weights' => [['name' => 'Each Bunch', 'sell_price' => 450, 'list_price' => 550]],
            ],
            [
                'title' => 'Lilies',
                'slug' => 'lilies',
                'sku' => 'MF-LILIES',
                'short_description' => 'Elegant lilies for premium arrangements.',
                'description' => 'Elegant lilies for premium floral arrangements, decor and gifting.',
                'keywords' => ['lily', 'lilies', 'premium flowers'],
                'image_source' => 'premium-collection/premium-lilies.jpg',
                'image_name' => 'seed-premium-lilies.jpg',
                'categories' => ['premium-flowers', 'lilies'],
                'featured_priority' => 24,
                'weights' => [['name' => 'Each Bunch', 'sell_price' => 300, 'list_price' => 380]],
            ],
            [
                'title' => 'Imported / Exotic Flowers',
                'slug' => 'imported-exotic-flowers',
                'sku' => 'MF-IMPORTED-EXOTIC',
                'short_description' => 'Imported and exotic flowers for premium occasions.',
                'description' => 'Imported and exotic flowers for hotels, offices, gifting and special decor.',
                'keywords' => ['imported', 'exotic', 'premium flowers'],
                'image_source' => 'premium-collection/premium-exotic.jpg',
                'image_name' => 'seed-premium-exotic.jpg',
                'categories' => ['premium-flowers', 'imported-exotic-flowers', 'bouquets-gifting'],
                'featured_priority' => 25,
                'weights' => [['name' => 'Each Bunch', 'sell_price' => 600, 'list_price' => 750]],
            ],
            [
                'title' => 'Rare Jasmine',
                'slug' => 'rare-jasmine',
                'sku' => 'MF-RARE-JASMINE',
                'short_description' => 'Fragrant jasmine flowers based on seasonal availability.',
                'description' => 'Fragrant jasmine flowers for puja, special rituals and premium occasions.',
                'keywords' => ['jasmine', 'malli', 'rare flowers'],
                'image_source' => 'rare-seasonal/rare-jasmine.jpg',
                'image_name' => 'seed-rare-jasmine.jpg',
                'categories' => ['rare-flowers', 'jasmine-malli'],
                'featured_priority' => 31,
                'weights' => [['name' => 'Each Bunch', 'sell_price' => 320, 'list_price' => 420]],
            ],
            [
                'title' => 'Seasonal Marigold',
                'slug' => 'seasonal-marigold',
                'sku' => 'MF-SEASONAL-MARIGOLD',
                'short_description' => 'Seasonal marigold flowers for puja and decoration.',
                'description' => 'Seasonal marigold flowers for rituals, events and decoration needs.',
                'keywords' => ['seasonal', 'marigold', 'banthi'],
                'image_source' => 'rare-seasonal/rare-marigold.jpg',
                'image_name' => 'seed-rare-marigold.jpg',
                'categories' => ['rare-flowers', 'seasonal-flowers', 'banthi'],
                'featured_priority' => 32,
                'weights' => $this->kgWeights(140, 180),
            ],
            [
                'title' => 'White Tuberose',
                'slug' => 'white-tuberose',
                'sku' => 'MF-WHITE-TUBEROSE',
                'short_description' => 'White tuberose flowers with strong fragrance.',
                'description' => 'White tuberose flowers for fragrance, rituals and premium arrangements.',
                'keywords' => ['tuberose', 'rajanigandha', 'rare flowers'],
                'image_source' => 'rare-seasonal/rare-tuberose.jpg',
                'image_name' => 'seed-rare-tuberose.jpg',
                'categories' => ['rare-flowers', 'tuberose'],
                'featured_priority' => 33,
                'weights' => [['name' => 'Each Bunch', 'sell_price' => 280, 'list_price' => 350]],
            ],
            [
                'title' => 'Sampangi Flowers',
                'slug' => 'sampangi-flowers',
                'sku' => 'MF-SAMPANGI',
                'short_description' => 'Rare sampangi flowers for special puja and rituals.',
                'description' => 'Rare sampangi flowers for special puja, temple offerings and traditional use.',
                'keywords' => ['sampangi', 'champaca', 'rare flowers'],
                'image_source' => 'rare-seasonal/rare-sampangi.jpg',
                'image_name' => 'seed-rare-sampangi.jpg',
                'categories' => ['rare-flowers', 'sampangi'],
                'featured_priority' => 34,
                'weights' => [['name' => 'Each Packet', 'sell_price' => 300, 'list_price' => 380]],
            ],
        ];
    }

    private function kgWeights(float $kgSellPrice, float $kgListPrice): array
    {
        return [
            ['name' => '100 Grams', 'sell_price' => round($kgSellPrice * 0.1), 'list_price' => round($kgListPrice * 0.1)],
            ['name' => '250 Grams', 'sell_price' => round($kgSellPrice * 0.25), 'list_price' => round($kgListPrice * 0.25)],
            ['name' => '500 Grams', 'sell_price' => round($kgSellPrice * 0.5), 'list_price' => round($kgListPrice * 0.5)],
            ['name' => '1 KG', 'sell_price' => $kgSellPrice, 'list_price' => $kgListPrice],
        ];
    }
}
