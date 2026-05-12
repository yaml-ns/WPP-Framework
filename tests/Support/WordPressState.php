<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Support;

final class WordPressState
{
    /** @var array<string, array<int, array{callback: callable, priority: int}>> */
    public static array $actions = [];

    /** @var array<string, callable> */
    public static array $shortcodes = [];

    /** @var array<string, mixed> */
    public static array $options = [];

    /** @var array<string, mixed> */
    public static array $transients = [];

    /** @var array<int, array<string, mixed>> */
    public static array $postMeta = [];

    /** @var array<int, \WP_Post> */
    public static array $posts = [];

    /** @var array<int, \WP_Post> */
    public static array $deletedPosts = [];

    /** @var array<int, array<string, array<int|string, mixed>>> */
    public static array $objectTerms = [];

    /** @var array<int, array<int, \WP_Post>> */
    public static array $queryPosts = [];

    public static int $queryIndex = 0;

    /** @var array<int, array<string, mixed>> */
    public static array $registeredPostMeta = [];

    /** @var array<int, array<string, mixed>> */
    public static array $registeredRestRoutes = [];

    /** @var array<int, string> */
    public static array $redirects = [];

    /** @var array<string, bool> */
    public static array $capabilities = [];

    /** @var array<string, array<string, bool>> */
    public static array $roles = [];

    /** @var array<int, array{post_type: string, args: array<string, mixed>}> */
    public static array $registeredPostTypes = [];

    /** @var array<int, array{name: string, object_type: string|array, args: array<string, mixed>}> */
    public static array $registeredTaxonomies = [];

    /** @var array<int, array<string, mixed>> */
    public static array $adminPages = [];

    /** @var array<int, array<string, mixed>> */
    public static array $adminSubmenuPages = [];

    /** @var array<int, string> */
    public static array $flushedRewriteRules = [];

    /** @var array<int, array{hook: string, args: array<int, mixed>}> */
    public static array $clearedCron = [];

    public static bool $throwOnRedirect = true;

    public static int $currentUserId = 1;

    public static function reset(): void
    {
        self::$actions = [];
        self::$shortcodes = [];
        self::$options = [];
        self::$transients = [];
        self::$postMeta = [];
        self::$posts = [];
        self::$deletedPosts = [];
        self::$objectTerms = [];
        self::$queryPosts = [];
        self::$queryIndex = 0;
        self::$registeredPostMeta = [];
        self::$registeredRestRoutes = [];
        self::$redirects = [];
        self::$capabilities = ['read' => true, 'manage_options' => true, 'edit_post' => true, 'edit_posts' => true];
        self::$roles = ['administrator' => []];
        self::$registeredPostTypes = [];
        self::$registeredTaxonomies = [];
        self::$adminPages = [];
        self::$adminSubmenuPages = [];
        self::$flushedRewriteRules = [];
        self::$clearedCron = [];
        self::$throwOnRedirect = true;
        self::$currentUserId = 1;
        $_POST = [];
        $_GET = [];
    }
}
