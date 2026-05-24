<?php
/**
 * Related Products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/related.php.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $related_products ) : ?>

<div class="mt-16 pt-8 border-t border-gray-100 dark:border-white/5">
    <style>
        /* NUCLEAR FIX - Related Products Grid */
        /* All breakpoints covered with maximum specificity */
        
        section.related ul.zame-related-grid,
        ul.zame-related-grid.products {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 1rem !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            list-style: none !important;
            float: none !important;
        }
        
        @media (min-width: 768px) {
            section.related ul.zame-related-grid,
            ul.zame-related-grid.products {
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 2rem !important;
            }
        }
        
        /* Force all li.product to be proper grid items */
        section.related.products ul.zame-related-grid li,
        .related.products ul.zame-related-grid.products li,
        ul.zame-related-grid.products li.product {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            float: none !important;
            display: block !important;
            list-style: none !important;
        }
        
        /* Ensure inner card takes full width */
        ul.zame-related-grid.products li.product > div {
            width: 100% !important;
        }
        
        /* Image container aspect ratio */
        ul.zame-related-grid.products li.product .aspect-\[1\/1\.1\],
        ul.zame-related-grid.products li.product [class*="aspect-"] {
            width: 100% !important;
            aspect-ratio: 1 / 1.1 !important;
        }
        
        ul.zame-related-grid.products li.product img {
            width: 100% !important;
            height: 100% !important;
            object-fit: contain !important;
        }
    </style>

	<section class="related w-full">

        <div class="text-left mb-10">
             <span class="text-primary font-bold uppercase tracking-[0.25em] text-[10px] border-b border-primary/30 inline-block pb-1 mb-3">
                Recomendados
             </span>
             <h2 class="text-3xl md:text-4xl font-luxury-title text-gray-900 dark:text-white leading-none">
                 <?php esc_html_e( 'También te podría gustar', 'woocommerce' ); ?>
             </h2>
        </div>

        <!-- Explicit Grid UL (Valid HTML for li children) -->
		<ul class="products zame-related-grid grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-8" style="display: grid !important; opacity: 1 !important; visibility: visible !important;">

			<?php foreach ( $related_products as $related_product ) : ?>

				<?php
				$post_object = get_post( $related_product->get_id() );

				setup_postdata( $GLOBALS['post'] =& $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found

				wc_get_template_part( 'content', 'product' );
				?>

			<?php endforeach; ?>

		</ul>

	</section>
</div>
<?php
endif;
wp_reset_postdata();
