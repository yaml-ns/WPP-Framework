<?php
if (!defined('ABSPATH')) {
    exit;
}

$slug = sanitize_key((string) $resource['slug']);
$paginationArgs = array_filter(
    array_map('strval', (array) $activeFilters),
    static fn (string $value): bool => $value !== ''
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html((string) ($resource['label'] ?? 'Products')); ?></h1>
    <a href="<?php echo esc_url($createUrl); ?>" class="page-title-action"><?php echo esc_html__('Add New', 'product-catalog-plugin'); ?></a>

    <hr class="wp-header-end">

    <form method="get" style="margin: 12px 0;">
        <input type="hidden" name="page" value="<?php echo esc_attr($slug); ?>">
        <?php foreach ($filters as $name => $filter): ?>
            <?php $filter = is_array($filter) ? $filter : []; ?>
            <?php if (($filter['type'] ?? '') === 'select'): ?>
                <select name="<?php echo esc_attr((string) $name); ?>">
                    <option value=""><?php echo esc_html((string) ($filter['label'] ?? $name)); ?></option>
                    <?php foreach ((array) ($filter['options'] ?? []) as $value => $label): ?>
                        <option value="<?php echo esc_attr((string) $value); ?>" <?php selected((string) ($activeFilters[$name] ?? ''), (string) $value); ?>>
                            <?php echo esc_html((string) $label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input type="search" name="<?php echo esc_attr((string) $name); ?>" value="<?php echo esc_attr((string) ($activeFilters[$name] ?? '')); ?>" placeholder="<?php echo esc_attr((string) ($filter['label'] ?? $name)); ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        <button class="button"><?php echo esc_html__('Filter', 'product-catalog-plugin'); ?></button>
    </form>

    <form method="post" action="<?php echo esc_url($adminForm->actionUrl()); ?>">
        <?php echo $adminForm->actionField($slug . '_bulk'); ?>
        <?php echo $adminForm->nonceFields($slug . '_bulk'); ?>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action">
                    <option value=""><?php echo esc_html__('Bulk actions', 'product-catalog-plugin'); ?></option>
                    <option value="delete"><?php echo esc_html__('Delete', 'product-catalog-plugin'); ?></option>
                </select>
                <button class="button"><?php echo esc_html__('Apply', 'product-catalog-plugin'); ?></button>
            </div>
        </div>

        <table class="widefat striped">
            <thead>
            <tr>
                <td class="check-column"></td>
                <th><?php echo esc_html__('Name', 'product-catalog-plugin'); ?></th>
                <th><?php echo esc_html__('SKU', 'product-catalog-plugin'); ?></th>
                <th><?php echo esc_html__('Price', 'product-catalog-plugin'); ?></th>
                <th><?php echo esc_html__('Stock', 'product-catalog-plugin'); ?></th>
                <th><?php echo esc_html__('Status', 'product-catalog-plugin'); ?></th>
                <th><?php echo esc_html__('Actions', 'product-catalog-plugin'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($query->posts as $post): ?>
            <tr>
                <th class="check-column">
                    <input type="checkbox" name="ids[]" value="<?php echo esc_attr((string) $post->ID); ?>">
                </th>
                <td><?php echo esc_html(get_the_title($post)); ?></td>
                <td><?php echo esc_html((string) get_post_meta($post->ID, 'product_sku', true)); ?></td>
                <td><?php echo esc_html((string) get_post_meta($post->ID, 'product_price', true)); ?></td>
                <td><?php echo esc_html((string) get_post_meta($post->ID, 'product_stock', true)); ?></td>
                <td><?php echo esc_html($post->post_status); ?></td>
                <td>
                    <a href="<?php echo esc_url(add_query_arg('id', (string) $post->ID, add_query_arg('action', 'edit', $baseUrl))); ?>">
                        <?php echo esc_html__('Edit', 'product-catalog-plugin'); ?>
                    </a>
                    <button type="submit" class="button-link-delete" name="delete_id" value="<?php echo esc_attr((string) $post->ID); ?>">
                        <?php echo esc_html__('Delete', 'product-catalog-plugin'); ?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </form>

    <?php if ($totalPages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                    <?php $pageUrl = add_query_arg(array_merge($paginationArgs, ['paged' => (string) $page]), $baseUrl); ?>
                    <a class="button <?php echo $page === $currentPage ? 'disabled' : ''; ?>" href="<?php echo esc_url($pageUrl); ?>">
                        <?php echo esc_html((string) $page); ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
