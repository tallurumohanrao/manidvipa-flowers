<?php

namespace App\Support;

use App\Models\Admin\SeoUrl;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SeoRouteManager
{
    private const CACHE_VERSION_KEY = 'api_seo_meta_version';

    public static function normalizePath(?string $value, string $fallback = '/'): string
    {
        $value = trim((string) $value);
        $fallback = trim($fallback) ?: '/';
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
    }

    public static function slugFromPath(?string $value, ?string $fallback = null): string
    {
        $path = self::normalizePath($value, '/'.Str::slug($fallback ?: 'page'));
        $slug = basename($path);

        return Str::slug($slug) ?: Str::slug($fallback ?: 'page');
    }

    public static function findByAlias(string $alias, array $legacyUrls = []): ?SeoUrl
    {
        $alias = self::normalizePath($alias);
        $legacyUrls = collect($legacyUrls)
            ->map(fn ($url) => self::normalizePath($url))
            ->push($alias)
            ->unique()
            ->values()
            ->all();

        return SeoUrl::query()
            ->where('alias', $alias)
            ->orWhereIn('url', $legacyUrls)
            ->orderByRaw('CASE WHEN alias = ? THEN 0 ELSE 1 END', [$alias])
            ->first();
    }

    public static function save(
        array $input,
        string $alias,
        ?string $oldPublicUrl = null,
        array $legacyUrls = []
    ): SeoUrl {
        $alias = self::normalizePath($alias);
        $publicUrl = self::normalizePath($input['url'] ?? $alias, $alias);
        $record = self::findByAlias($alias, array_filter(array_merge($legacyUrls, [$oldPublicUrl])));

        self::ensurePathIsAvailable($publicUrl, $alias, $record?->id);

        $payload = [
            'url' => $publicUrl,
            'alias' => $alias,
            'status' => array_key_exists('status', $input) ? (int) $input['status'] : ($record?->status ?? 1),
        ];

        foreach (['page_title', 'meta_keywords', 'meta_description', 'schema_markup', 'robots'] as $field) {
            if (array_key_exists($field, $input)) {
                $payload[$field] = $input[$field];
            }
        }

        if (! $record) {
            $payload['page_title'] = $payload['page_title'] ?? ($input['title'] ?? Str::headline(basename($publicUrl)));
            $record = SeoUrl::create($payload);
        } else {
            $previousUrl = $record->url;
            $record->update($payload);
            $oldPublicUrl = $oldPublicUrl ?: $previousUrl;
        }

        if ($oldPublicUrl) {
            self::recordRedirect($oldPublicUrl, $publicUrl);
        }

        self::clearCaches();

        return $record->fresh();
    }

    public static function recordRedirect(?string $fromUrl, ?string $toUrl): void
    {
        if (! DB::getSchemaBuilder()->hasTable('seo_url_redirects')) {
            return;
        }

        $fromUrl = self::normalizePath($fromUrl);
        $toUrl = self::normalizePath($toUrl);

        if ($fromUrl === $toUrl) {
            return;
        }

        DB::table('seo_url_redirects')->where('from_url', $toUrl)->delete();
        DB::table('seo_url_redirects')->where('to_url', $fromUrl)->update([
            'to_url' => $toUrl,
            'updated_at' => now(),
        ]);
        DB::table('seo_url_redirects')->updateOrInsert(
            ['from_url' => $fromUrl],
            ['to_url' => $toUrl, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public static function ensurePathIsAvailable(string $publicUrl, string $alias, ?int $ignoreId = null): void
    {
        $query = SeoUrl::query()
            ->where(function ($query) use ($publicUrl, $alias) {
                $query->where('url', $publicUrl)
                    ->orWhere('alias', $publicUrl)
                    ->orWhere(function ($query) use ($alias) {
                        $query->where('url', $alias)->where('alias', '!=', $alias);
                    });
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'url' => 'This URL is already used by another storefront page.',
                'seo.url' => 'This URL is already used by another storefront page.',
            ]);
        }
    }

    public static function clearCaches(): void
    {
        Cache::forget('api_seo_routes');
        Cache::forget('sitemap');
        Cache::forever(self::CACHE_VERSION_KEY, self::cacheVersion() + 1);
    }

    public static function cacheVersion(): int
    {
        return (int) Cache::rememberForever(self::CACHE_VERSION_KEY, fn () => 1);
    }
}
