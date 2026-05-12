<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!$query->have_posts()) {
    echo '<p class="product-catalog-empty">No products available.</p>';
    return;
}
?>

<div class="product-catalog-grid">
    <?php while ($query->have_posts()) : $query->the_post(); ?>
        <article class="product-card">
            <?php if (has_post_thumbnail()) : ?>
                <a class="product-card__image" href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail('medium'); ?>
                </a>
            <?php endif; ?>

            <h3 class="product-card__title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>

            <?php if (has_excerpt()) : ?>
                <p class="product-card__excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
            <?php endif; ?>

            <dl class="product-card__meta">
                <?php if (get_post_meta(get_the_ID(), 'product_price', true) !== '') : ?>
                    <div>
                        <dt><?php esc_html_e('Price', 'product-catalog-plugin'); ?></dt>
                        <dd><?php echo esc_html((string) get_post_meta(get_the_ID(), 'product_price', true)); ?></dd>
                    </div>
                <?php endif; ?>

                <?php if (get_post_meta(get_the_ID(), 'product_stock', true) !== '') : ?>
                    <div>
                        <dt><?php esc_html_e('Stock', 'product-catalog-plugin'); ?></dt>
                        <dd><?php echo esc_html((string) get_post_meta(get_the_ID(), 'product_stock', true)); ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </article>
    <?php endwhile; ?>
</div>

<?php wp_reset_postdata(); ?>
