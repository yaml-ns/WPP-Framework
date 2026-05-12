<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Core;

final class PluginContext
{
    public function __construct(
        private readonly string $file,
        private readonly string $dir,
        private readonly string $url,
        private readonly string $slug,
        private readonly string $name,
        private readonly string $version,
        private readonly string $textDomain,
        private readonly string $restNamespace,
    ) {
    }

    /**
     * @param array{
     *     file?: string,
     *     dir?: string,
     *     url?: string,
     *     slug?: string,
     *     name?: string,
     *     version?: string,
     *     text_domain?: string,
     *     rest_namespace?: string
     * } $config
     */
    public static function fromFile(string $file, array $config = []): self
    {
        $slug = $config['slug'] ?? self::slugFromPath($file);

        return new self(
            file: $config['file'] ?? $file,
            dir: $config['dir'] ?? self::dirFromFile($file),
            url: $config['url'] ?? self::urlFromFile($file),
            slug: $slug,
            name: $config['name'] ?? self::nameFromSlug($slug),
            version: $config['version'] ?? '1.0.0',
            textDomain: $config['text_domain'] ?? $slug,
            restNamespace: $config['rest_namespace'] ?? $slug . '/v1',
        );
    }

    /**
     * @param array{
     *     file?: string,
     *     url?: string,
     *     slug?: string,
     *     name?: string,
     *     version?: string,
     *     text_domain?: string,
     *     rest_namespace?: string
     * } $config
     */
    public static function fromDirectory(string $dir, array $config = []): self
    {
        $file = $config['file'] ?? '';
        $slug = $config['slug'] ?? self::slugFromPath($dir);

        return new self(
            file: $file,
            dir: $dir,
            url: $config['url'] ?? '',
            slug: $slug,
            name: $config['name'] ?? self::nameFromSlug($slug),
            version: $config['version'] ?? '1.0.0',
            textDomain: $config['text_domain'] ?? $slug,
            restNamespace: $config['rest_namespace'] ?? $slug . '/v1',
        );
    }

    public function file(): string
    {
        return $this->file;
    }

    public function dir(): string
    {
        return self::withTrailingSeparator($this->dir);
    }

    public function url(): string
    {
        return self::withTrailingSlash($this->url);
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function textDomain(): string
    {
        return $this->textDomain;
    }

    public function restNamespace(): string
    {
        return trim($this->restNamespace, '/');
    }

    public function id(): string
    {
        $id = $this->file !== ''
            ? $this->file
            : $this->dir() . '|' . $this->slug;

        return hash('sha256', strtolower(str_replace('\\', '/', $id)));
    }

    public function fingerprint(): string
    {
        return hash('sha256', strtolower(str_replace('\\', '/', implode('|', [
            $this->file,
            $this->dir(),
            $this->slug,
            $this->restNamespace(),
            $this->textDomain,
        ]))));
    }

    public function path(string $relative = ''): string
    {
        return $this->dir() . ltrim($relative, '/\\');
    }

    public function assetUrl(string $relative = ''): string
    {
        return $this->url() . ltrim($relative, '/\\');
    }

    private static function dirFromFile(string $file): string
    {
        if (function_exists('plugin_dir_path')) {
            return plugin_dir_path($file);
        }

        return dirname($file) . DIRECTORY_SEPARATOR;
    }

    private static function urlFromFile(string $file): string
    {
        if (function_exists('plugin_dir_url')) {
            return plugin_dir_url($file);
        }

        return '';
    }

    private static function slugFromPath(string $path): string
    {
        $base = pathinfo(rtrim($path, '/\\'), PATHINFO_FILENAME);
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $base));

        return trim($slug, '-') ?: 'plugin';
    }

    private static function nameFromSlug(string $slug): string
    {
        return ucwords(str_replace('-', ' ', $slug));
    }

    private static function withTrailingSeparator(string $path): string
    {
        if ($path === '' || str_ends_with($path, '/') || str_ends_with($path, '\\')) {
            return $path;
        }

        return $path . DIRECTORY_SEPARATOR;
    }

    private static function withTrailingSlash(string $url): string
    {
        if ($url === '' || str_ends_with($url, '/')) {
            return $url;
        }

        return $url . '/';
    }
}
