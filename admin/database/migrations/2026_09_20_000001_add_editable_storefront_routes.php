<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seo_url_redirects')) {
            Schema::create('seo_url_redirects', function (Blueprint $table) {
                $table->id();
                $table->string('from_url', 190)->unique();
                $table->string('to_url', 190);
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('pages') && ! Schema::hasColumn('pages', 'slug')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->string('slug', 190)->nullable()->after('name');
            });
        }

        if (! Schema::hasTable('seo_urls')) {
            return;
        }

        $normalize = static function (?string $value, string $fallback = '/'): string {
            $value = trim((string) $value);
            $path = parse_url($value !== '' ? $value : $fallback, PHP_URL_PATH);
            $path = is_string($path) && $path !== '' ? $path : $fallback;

            if ($path === '/') {
                return '/';
            }

            $segments = collect(explode('/', rawurldecode($path)))
                ->filter(fn ($segment) => trim((string) $segment) !== '')
                ->map(fn ($segment) => Str::slug((string) $segment))
                ->filter()
                ->values();

            return $segments->isEmpty() ? '/' : '/'.$segments->implode('/');
        };

        $redirect = static function (string $from, string $to) use ($normalize): void {
            $from = $normalize($from);
            $to = $normalize($to);

            if ($from === $to) {
                return;
            }

            DB::table('seo_url_redirects')->updateOrInsert(
                ['from_url' => $from],
                ['to_url' => $to, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]
            );
        };

        $register = static function (string $alias, string $title, array $legacyUrls = []) use ($normalize, $redirect): void {
            $alias = $normalize($alias);
            $candidates = collect(array_merge([$alias], $legacyUrls))
                ->map(fn ($url) => $normalize($url))
                ->unique()
                ->all();
            $row = DB::table('seo_urls')
                ->where('alias', $alias)
                ->orWhereIn('url', $candidates)
                ->orderByRaw('CASE WHEN url = ? THEN 0 ELSE 1 END', [$alias])
                ->first();

            if ($row) {
                $oldUrl = $normalize($row->url);
                DB::table('seo_urls')->where('id', $row->id)->update([
                    'url' => $alias,
                    'alias' => $alias,
                    'page_title' => $row->page_title ?: $title,
                    'updated_at' => now(),
                ]);
                $redirect($oldUrl, $alias);
                return;
            }

            DB::table('seo_urls')->insert([
                'url' => $alias,
                'alias' => $alias,
                'page_title' => $title,
                'robots' => 'index,follow',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        };

        $staticRoutes = [
            '/' => 'Home',
            '/flowers' => 'Flowers',
            '/puja-flowers' => 'Puja Flowers',
            '/subscriptions' => 'Subscriptions',
            '/premium-flowers' => 'Premium Flowers',
            '/rare-flowers' => 'Rare Flowers',
            '/garlands' => 'Garlands',
            '/decorations' => 'Decorations',
            '/gifts' => 'Gifts',
            '/offers' => 'Offers',
            '/about' => 'About Us',
            '/contact-us' => 'Contact Us',
            '/testimonials' => 'Testimonials',
            '/privacy-policy' => 'Privacy Policy',
            '/terms-conditions' => 'Terms and Conditions',
            '/refund-cancellation' => 'Refund and Cancellation Policy',
        ];

        foreach ($staticRoutes as $path => $title) {
            $register($path, $title);
        }

        if (Schema::hasTable('products')) {
            foreach (DB::table('products')->select('title', 'slug')->whereNotNull('slug')->get() as $product) {
                $alias = '/flowers/'.$product->slug;
                $register($alias, $product->title, [
                    '/product-details/'.$product->slug,
                    '/productDetails/'.$product->slug,
                    '/'.$product->slug,
                ]);
            }
        }

        if (Schema::hasTable('categories')) {
            $categories = DB::table('categories')->select('id', 'title', 'slug', 'parent_id')->get()->keyBy('id');
            foreach ($categories as $category) {
                if (! $category->slug) {
                    continue;
                }

                $parent = $category->parent_id ? $categories->get($category->parent_id) : null;
                $alias = $parent?->slug
                    ? '/'.$parent->slug.'/'.$category->slug
                    : '/'.$category->slug;
                $register($alias, $category->title, [
                    '/products/'.$category->slug,
                    '/categories/'.$category->slug,
                ]);
            }
        }

        if (Schema::hasTable('pages')) {
            $pageAliases = [
                'about-us' => '/about',
                'contact-us' => '/contact-us',
                'privacy-policy' => '/privacy-policy',
                'terms-conditions' => '/terms-conditions',
                'refund-policy' => '/refund-cancellation',
                'refund-return-policy' => '/refund-cancellation',
            ];

            foreach (DB::table('pages')->select('id', 'name', 'slug')->get() as $page) {
                $slug = $page->slug ?: Str::slug($page->name);
                if (! $page->slug) {
                    DB::table('pages')->where('id', $page->id)->update(['slug' => $slug]);
                }
                $register($pageAliases[$slug] ?? '/content/'.$slug, $page->name);
            }
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('seo_url_redirects');
    }
};
