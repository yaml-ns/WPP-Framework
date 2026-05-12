<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Admin;

final class AdminForm
{
    public function actionUrl(): string
    {
        return admin_url('admin-post.php');
    }

    public function actionField(string $action): string
    {
        return '<input type="hidden" name="action" value="' . esc_attr($action) . '">';
    }

    public function nonceFields(string $id): string
    {
        ob_start();
        wp_nonce_field($this->nonceAction($id), $this->nonceName($id));

        return (string) ob_get_clean();
    }

    public function nonceAction(string $id): string
    {
        return self::nonceActionFor($id);
    }

    public function nonceName(string $id): string
    {
        return self::nonceNameFor($id);
    }

    public static function nonceActionFor(string $id): string
    {
        return 'wpp_admin_form_' . sanitize_key($id);
    }

    public static function nonceNameFor(string $id): string
    {
        return '_wpp_admin_form_' . sanitize_key($id);
    }
}
