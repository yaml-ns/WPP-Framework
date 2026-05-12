<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings = $options->array('product_catalog_settings');
$defaultLimit = (int) ($settings['default_limit'] ?? 12);
$featuredFirst = ($settings['display_featured_first'] ?? '0') === '1';
$inStockOnly = ($settings['in_stock_only'] ?? '0') === '1';
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if (isset($_GET['settings-updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved.', 'product-catalog-plugin'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url($adminForm->actionUrl()); ?>">
        <?php echo $adminForm->actionField('product_catalog_save_settings'); ?>
        <?php echo $adminForm->nonceFields('product_catalog_settings'); ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="default_limit"><?php esc_html_e('Default product limit', 'product-catalog-plugin'); ?></label>
                    </th>
                    <td>
                        <input
                            id="default_limit"
                            class="small-text"
                            name="default_limit"
                            type="number"
                            min="1"
                            max="100"
                            value="<?php echo esc_attr((string) max(1, $defaultLimit)); ?>"
                        >
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Display', 'product-catalog-plugin'); ?></th>
                    <td>
                        <label for="display_featured_first">
                            <input
                                id="display_featured_first"
                                name="display_featured_first"
                                type="checkbox"
                                value="1"
                                <?php checked($featuredFirst); ?>
                            >
                            <?php esc_html_e('Show featured products first', 'product-catalog-plugin'); ?>
                        </label>
                        <br>
                        <label for="in_stock_only">
                            <input
                                id="in_stock_only"
                                name="in_stock_only"
                                type="checkbox"
                                value="1"
                                <?php checked($inStockOnly); ?>
                            >
                            <?php esc_html_e('Show only products in stock', 'product-catalog-plugin'); ?>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button(__('Save', 'product-catalog-plugin')); ?>
    </form>
</div>
