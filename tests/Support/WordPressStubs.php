<?php

declare(strict_types=1);

use YamlNs\WppFramework\Tests\Support\RedirectException;
use YamlNs\WppFramework\Tests\Support\WordPressState;

if (!class_exists('WP_Error')) {
    final class WP_Error
    {
        public function __construct(
            public string $code = '',
            public string $message = '',
            public array $data = [],
        ) {
        }

        public function get_error_message(): string
        {
            return $this->message;
        }
    }
}

if (!class_exists('WP_Post')) {
    final class WP_Post
    {
        public function __construct(
            public int $ID = 0,
            public string $post_type = 'post',
            public string $post_status = 'publish',
            public string $post_title = '',
            public string $post_content = '',
            public string $post_excerpt = '',
        ) {
        }
    }
}

if (!class_exists('WP_REST_Request')) {
    final class WP_REST_Request
    {
        public function __construct(private array $params = [])
        {
        }

        public function get_param(string $key): mixed
        {
            return $this->params[$key] ?? null;
        }

        public function get_params(): array
        {
            return $this->params;
        }

        public function get_header(string $key): mixed
        {
            return $this->params[$key] ?? null;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    final class WP_REST_Response
    {
        public array $headers = [];

        public function __construct(public mixed $data = null, public int $status = 200)
        {
        }

        public function header(string $key, string $value): void
        {
            $this->headers[$key] = $value;
        }
    }
}

if (!class_exists('WP_Query')) {
    final class WP_Query
    {
        /** @var array<int, WP_Post> */
        public array $posts = [];

        public int $found_posts = 0;

        public int $max_num_pages = 0;

        /** @param array<string, mixed> $args */
        public function __construct(public array $args = [])
        {
            $this->posts = WordPressState::$queryPosts[WordPressState::$queryIndex] ?? [];
            WordPressState::$queryIndex++;
            $this->found_posts = count($this->posts);
            $perPage = (int) ($args['posts_per_page'] ?? max(1, $this->found_posts));
            $this->max_num_pages = $perPage > 0 ? (int) ceil($this->found_posts / $perPage) : 0;
        }
    }
}

if (!function_exists('add_action')) {
    function add_action(string $hook, callable $callback, int $priority = 10): void
    {
        WordPressState::$actions[$hook][] = ['callback' => $callback, 'priority' => $priority];
    }
}

if (!function_exists('do_action')) {
    function do_action(string $hook, mixed ...$args): void
    {
        $callbacks = WordPressState::$actions[$hook] ?? [];
        usort($callbacks, static fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        foreach ($callbacks as $entry) {
            $entry['callback'](...$args);
        }
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode(string $tag, callable $callback): void
    {
        WordPressState::$shortcodes[$tag] = $callback;
    }
}

if (!function_exists('register_rest_route')) {
    function register_rest_route(string $namespace, string $route, array $args): void
    {
        WordPressState::$registeredRestRoutes[] = compact('namespace', 'route', 'args');
    }
}

if (!function_exists('register_post_meta')) {
    function register_post_meta(string $postType, string $key, array $args): void
    {
        WordPressState::$registeredPostMeta[] = compact('postType', 'key', 'args');
    }
}

if (!function_exists('register_post_type')) {
    function register_post_type(string $post_type, array $args = []): object
    {
        WordPressState::$registeredPostTypes[] = compact('post_type', 'args');
        return (object) ['name' => $post_type];
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, string $media = 'all'): void
    {
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, bool $in_footer = false): void
    {
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script(string $handle, string $object_name, array $l10n): bool
    {
        return true;
    }
}

if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled(string $hook, array $args = []): int|false
    {
        return false;
    }
}

if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event(int $timestamp, string $recurrence, string $hook, array $args = [], bool $wp_error = false): bool|WP_Error
    {
        return true;
    }
}

if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain(string $domain, bool $deprecated = false, string $plugin_rel_path = ''): bool
    {
        return true;
    }
}

if (!function_exists('add_meta_box')) {
    function add_meta_box(
        string $id,
        string $title,
        callable $callback,
        string|array|null $screen = null,
        string $context = 'advanced',
        string $priority = 'default',
        mixed $callback_args = null,
    ): void {
    }
}

if (!function_exists('register_setting')) {
    function register_setting(string $option_group, string $option_name, array $args = []): bool
    {
        return true;
    }
}

if (!function_exists('add_settings_section')) {
    function add_settings_section(string $id, string $title, callable $callback, string $page, array $args = []): void
    {
    }
}

if (!function_exists('add_settings_field')) {
    function add_settings_field(
        string $id,
        string $title,
        callable $callback,
        string $page,
        string $section = 'default',
        array $args = [],
    ): void {
    }
}

if (!function_exists('register_taxonomy')) {
    function register_taxonomy(string $taxonomy, string|array $object_type, array $args = []): object
    {
        WordPressState::$registeredTaxonomies[] = [
            'name' => $taxonomy,
            'object_type' => $object_type,
            'args' => $args,
        ];

        return (object) ['name' => $taxonomy];
    }
}

if (!function_exists('add_menu_page')) {
    function add_menu_page(
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        ?callable $callback = null,
        string $icon_url = '',
        int|float|null $position = null,
    ): string {
        WordPressState::$adminPages[] = compact('page_title', 'menu_title', 'capability', 'menu_slug', 'callback', 'icon_url', 'position');

        return $menu_slug;
    }
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page(
        string $parent_slug,
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        ?callable $callback = null,
        int|float|null $position = null,
    ): string {
        WordPressState::$adminSubmenuPages[] = compact('parent_slug', 'page_title', 'menu_title', 'capability', 'menu_slug', 'callback', 'position');

        return $menu_slug;
    }
}

if (!function_exists('flush_rewrite_rules')) {
    function flush_rewrite_rules(bool $hard = true): void
    {
        WordPressState::$flushedRewriteRules[] = $hard ? 'hard' : 'soft';
    }
}

if (!function_exists('wp_clear_scheduled_hook')) {
    function wp_clear_scheduled_hook(string $hook, array $args = []): int
    {
        WordPressState::$clearedCron[] = compact('hook', 'args');
        return 1;
    }
}

if (!function_exists('get_role')) {
    function get_role(string $role): ?object
    {
        if (!isset(WordPressState::$roles[$role])) {
            return null;
        }

        return new class ($role) {
            public function __construct(private string $role)
            {
            }

            public function add_cap(string $capability): void
            {
                WordPressState::$roles[$this->role][$capability] = true;
            }

            public function remove_cap(string $capability): void
            {
                unset(WordPressState::$roles[$this->role][$capability]);
            }
        };
    }
}

if (!function_exists('get_option')) {
    function get_option(string $option, mixed $default = false): mixed
    {
        return WordPressState::$options[$option] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option(string $option, mixed $value): bool
    {
        WordPressState::$options[$option] = $value;
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option(string $option): bool
    {
        unset(WordPressState::$options[$option]);
        return true;
    }
}

if (!function_exists('set_transient')) {
    function set_transient(string $transient, mixed $value, int $expiration = 0): bool
    {
        WordPressState::$transients[$transient] = $value;
        return true;
    }
}

if (!function_exists('get_transient')) {
    function get_transient(string $transient): mixed
    {
        return WordPressState::$transients[$transient] ?? false;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient(string $transient): bool
    {
        unset(WordPressState::$transients[$transient]);
        return true;
    }
}

if (!function_exists('delete_site_option')) {
    function delete_site_option(string $option): bool
    {
        unset(WordPressState::$options[$option]);
        return true;
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta(int $postId, string $key, bool $single = false): mixed
    {
        return WordPressState::$postMeta[$postId][$key] ?? ($single ? '' : []);
    }
}

if (!function_exists('get_post')) {
    function get_post(int $postId): ?WP_Post
    {
        return WordPressState::$posts[$postId] ?? null;
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type(int|WP_Post|null $post = null): string|false
    {
        if ($post instanceof WP_Post) {
            return $post->post_type;
        }

        if (is_int($post)) {
            return WordPressState::$posts[$post]->post_type ?? false;
        }

        return false;
    }
}

if (!function_exists('wp_insert_post')) {
    function wp_insert_post(array $postarr, bool $wp_error = false): int|WP_Error
    {
        $id = (int) ($postarr['ID'] ?? (WordPressState::$posts === [] ? 1 : max(array_keys(WordPressState::$posts)) + 1));

        WordPressState::$posts[$id] = new WP_Post(
            $id,
            (string) ($postarr['post_type'] ?? 'post'),
            (string) ($postarr['post_status'] ?? 'draft'),
            (string) ($postarr['post_title'] ?? ''),
            (string) ($postarr['post_content'] ?? ''),
            (string) ($postarr['post_excerpt'] ?? ''),
        );

        return $id;
    }
}

if (!function_exists('wp_update_post')) {
    function wp_update_post(array $postarr, bool $wp_error = false): int|WP_Error
    {
        $id = (int) ($postarr['ID'] ?? 0);
        $post = WordPressState::$posts[$id] ?? null;

        if (!$post instanceof WP_Post) {
            return $wp_error ? new WP_Error('invalid_post', 'Invalid post.') : 0;
        }

        WordPressState::$posts[$id] = new WP_Post(
            $id,
            (string) ($postarr['post_type'] ?? $post->post_type),
            (string) ($postarr['post_status'] ?? $post->post_status),
            (string) ($postarr['post_title'] ?? $post->post_title),
            (string) ($postarr['post_content'] ?? $post->post_content),
            (string) ($postarr['post_excerpt'] ?? $post->post_excerpt),
        );

        return $id;
    }
}

if (!function_exists('wp_delete_post')) {
    function wp_delete_post(int $postid, bool $force_delete = false): WP_Post|false|null
    {
        $post = WordPressState::$posts[$postid] ?? null;

        if (!$post instanceof WP_Post) {
            return false;
        }

        WordPressState::$deletedPosts[$postid] = $post;
        unset(WordPressState::$posts[$postid]);

        return $post;
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta(int $postId, string $key, mixed $value): int|bool
    {
        WordPressState::$postMeta[$postId][$key] = $value;
        return true;
    }
}

if (!function_exists('delete_post_meta')) {
    function delete_post_meta(int $postId, string $key): bool
    {
        unset(WordPressState::$postMeta[$postId][$key]);
        return true;
    }
}

if (!function_exists('wp_set_object_terms')) {
    function wp_set_object_terms(int $object_id, array|int|string $terms, string $taxonomy, bool $append = false): array|WP_Error
    {
        $values = is_array($terms) ? $terms : [$terms];

        if ($append) {
            WordPressState::$objectTerms[$object_id][$taxonomy] = array_merge(
                WordPressState::$objectTerms[$object_id][$taxonomy] ?? [],
                $values,
            );
            return $values;
        }

        WordPressState::$objectTerms[$object_id][$taxonomy] = $values;

        return $values;
    }
}

if (!function_exists('update_post_caches')) {
    function update_post_caches(array &$posts, string $post_type = 'post', bool $update_term_cache = true, bool $update_meta_cache = true): void
    {
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title(WP_Post $post): string
    {
        return $post->post_title !== '' ? $post->post_title : 'Post ' . $post->ID;
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink(WP_Post $post): string
    {
        return 'https://example.test/?p=' . $post->ID;
    }
}

if (!function_exists('get_the_excerpt')) {
    function get_the_excerpt(WP_Post $post): string
    {
        return $post->post_excerpt !== '' ? $post->post_excerpt : 'Excerpt ' . $post->ID;
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map('wp_unslash', $value);
        }

        return is_string($value)
            ? str_replace(['\\"', "\\'"], ['"', "'"], stripslashes($value))
            : $value;
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key(string $key): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9_-]/', '', $key));
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $value): string
    {
        return trim(strip_tags($value));
    }
}

if (!function_exists('absint')) {
    function absint(mixed $value): int
    {
        return abs((int) $value);
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field(string $value): string
    {
        return trim(strip_tags($value));
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email(string $value): string
    {
        return filter_var($value, FILTER_SANITIZE_EMAIL) ?: '';
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw(string $value): string
    {
        return filter_var($value, FILTER_SANITIZE_URL) ?: '';
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea(string $value): string
    {
        return htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8');
    }
}

if (!function_exists('selected')) {
    function selected(mixed $selected, mixed $current = true, bool $display = true): string
    {
        $result = $selected === $current ? ' selected="selected"' : '';

        if ($display) {
            echo $result;
        }

        return $result;
    }
}

if (!function_exists('checked')) {
    function checked(mixed $checked, mixed $current = true, bool $display = true): string
    {
        $result = $checked === $current ? ' checked="checked"' : '';

        if ($display) {
            echo $result;
        }

        return $result;
    }
}

if (!function_exists('submit_button')) {
    function submit_button(string $text = 'Save Changes'): void
    {
        echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_attr($text) . '</button></p>';
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can(string $capability, mixed ...$args): bool
    {
        return WordPressState::$capabilities[$capability] ?? false;
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in(): bool
    {
        return WordPressState::$currentUserId > 0;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id(): int
    {
        return WordPressState::$currentUserId;
    }
}

if (!function_exists('wp_die')) {
    function wp_die(string $message = ''): never
    {
        throw new RuntimeException($message);
    }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field(string $action, string $name): void
    {
        echo '<input type="hidden" name="' . $name . '" value="valid">';
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce(string $action): string
    {
        return 'valid';
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce(string $nonce, string $action): bool|int
    {
        return $nonce === 'valid';
    }
}

if (!function_exists('check_admin_referer')) {
    function check_admin_referer(string $action, string $name): bool|int
    {
        if (($_POST[$name] ?? null) !== 'valid') {
            throw new RuntimeException('Invalid nonce.');
        }

        return 1;
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        return 'https://example.test/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg(array|string $key, ?string $value = null, ?string $url = null): string
    {
        $args = is_array($key) ? $key : [$key => $value];
        $url = is_array($key) ? ($value ?? '') : ($url ?? '');

        foreach ($args as $argKey => $argValue) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . rawurlencode((string) $argKey) . '=' . rawurlencode((string) $argValue);
        }

        return $url;
    }
}

if (!function_exists('wp_validate_redirect')) {
    function wp_validate_redirect(string $location, string $fallback = ''): string
    {
        return $location !== '' ? $location : $fallback;
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect(string $location): bool
    {
        WordPressState::$redirects[] = $location;

        if (WordPressState::$throwOnRedirect) {
            throw new RedirectException($location);
        }

        return true;
    }
}
