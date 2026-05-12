<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Admin;

final class FrameworkAdminPage extends BaseAdminPage
{
    public function register(): void
    {
        add_menu_page(
            $this->context->name(),
            $this->context->name(),
            'manage_options',
            $this->context->slug(),
            [$this, 'render'],
            'dashicons-admin-generic',
            58,
        );
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'wpp-framework'));
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($this->context->name()); ?></h1>
            <p><?php echo esc_html__('WPP Framework is active for this plugin.', 'wpp-framework'); ?></p>
            <p>
                <?php echo esc_html__('REST health endpoint:', 'wpp-framework'); ?>
                <code>/wp-json/<?php echo esc_html($this->context->restNamespace()); ?>/health</code>
            </p>
        </div>
        <?php
    }
}
