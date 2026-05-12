<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html($post ? __('Edit Product', 'product-catalog-plugin') : __('Add Product', 'product-catalog-plugin')); ?></h1>

    <?php if ($errors !== []): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html__('Please correct the highlighted fields.', 'product-catalog-plugin'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url($formAction); ?>">
        <?php echo $adminForm->actionField($action); ?>
        <?php echo $adminForm->nonceFields($nonceId); ?>
        <?php if ($post): ?>
            <input type="hidden" name="id" value="<?php echo esc_attr((string) $post->ID); ?>">
        <?php endif; ?>

        <table class="form-table" role="presentation">
            <tbody>
            <?php foreach ($fields as $name => $field): ?>
                <?php $field = is_array($field) ? $field : []; ?>
                <?php $type = (string) ($field['type'] ?? 'text'); ?>
                <?php $value = $values[$name] ?? ''; ?>
                <?php $fieldErrors = (array) ($errors[$name] ?? []); ?>
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr((string) $name); ?>"><?php echo esc_html((string) ($field['label'] ?? $name)); ?></label>
                    </th>
                    <td>
                        <?php if ($type === 'textarea'): ?>
                            <textarea class="large-text" rows="8" id="<?php echo esc_attr((string) $name); ?>" name="<?php echo esc_attr((string) $name); ?>"><?php echo esc_textarea((string) $value); ?></textarea>
                        <?php elseif ($type === 'select'): ?>
                            <select id="<?php echo esc_attr((string) $name); ?>" name="<?php echo esc_attr((string) $name); ?>">
                                <?php foreach ((array) ($field['options'] ?? []) as $optionValue => $optionLabel): ?>
                                    <option value="<?php echo esc_attr((string) $optionValue); ?>" <?php selected((string) $value, (string) $optionValue); ?>>
                                        <?php echo esc_html((string) $optionLabel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($type === 'checkbox'): ?>
                            <label>
                                <input type="checkbox" id="<?php echo esc_attr((string) $name); ?>" name="<?php echo esc_attr((string) $name); ?>" value="1" <?php checked((string) $value, '1'); ?>>
                                <?php echo esc_html((string) ($field['description'] ?? '')); ?>
                            </label>
                        <?php else: ?>
                            <input class="regular-text" type="<?php echo esc_attr(in_array($type, ['float', 'number', 'integer'], true) ? 'number' : 'text'); ?>" id="<?php echo esc_attr((string) $name); ?>" name="<?php echo esc_attr((string) $name); ?>" value="<?php echo esc_attr((string) $value); ?>" <?php echo in_array($type, ['float', 'number'], true) ? 'step="any"' : ''; ?>>
                        <?php endif; ?>
                        <?php foreach ($fieldErrors as $message): ?>
                            <p class="description error"><?php echo esc_html((string) $message); ?></p>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php submit_button($post ? __('Update', 'product-catalog-plugin') : __('Create', 'product-catalog-plugin')); ?>
    </form>
</div>
