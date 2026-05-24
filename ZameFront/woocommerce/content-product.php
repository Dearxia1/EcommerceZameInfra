<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Ensure visibility.
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'group relative list-none mb-12', $product ); ?>>
    
    <div class="bg-white dark:bg-neutral-800 rounded-sm overflow-hidden transition-all duration-300 group hover:shadow-xl">
        
        <!-- Image Container with Padding -->
        <div class="relative aspect-[1/1.1] overflow-hidden bg-gray-50 dark:bg-neutral-900 p-8 flex items-center justify-center">
            <?php 
            // Custom Image Output using standard WC function but wrapped/styled
            // or manually manually outputting the image
            if ( has_post_thumbnail() ) {
                the_post_thumbnail('medium', ['class' => 'w-full h-full object-contain mix-blend-multiply dark:mix-blend-normal group-hover:scale-105 transition-transform duration-700']);
            } else {
                 echo '<div class="text-gray-200 text-6xl">🧴</div>';
            }
            ?>

            <?php if ( $product->is_on_sale() ) : ?>
                <div class="absolute top-4 right-4 bg-primary text-white text-[10px] px-3 py-1 font-bold tracking-widest uppercase shadow-sm">Oferta</div>
            <?php endif; ?>
            
            <!-- Quick View / Action Overlay -->
            <div class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                 <a href="<?php the_permalink(); ?>" class="bg-white dark:bg-background-dark text-black dark:text-white px-6 py-3 text-xs uppercase tracking-widest font-bold shadow-lg hover:bg-primary hover:text-white transition-colors transform translate-y-4 group-hover:translate-y-0 duration-300">
                    Ver Detalles
                </a>
            </div>
        </div>
        
        <!-- Content -->
        <div class="p-4 md:p-6 text-center flex flex-col min-h-[160px] md:min-h-[180px]">
            <div class="text-[9px] md:text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-3 truncate">
                 <?php echo wc_get_product_category_list( $product->get_id(), ', ' ); ?>
            </div>

            <h2 class="woocommerce-loop-product__title text-base md:text-lg font-display text-gray-900 dark:text-white mb-3 group-hover:text-primary transition-colors leading-tight">
                 <a href="<?php the_permalink(); ?>">
                    <?php 
                        $title = get_the_title();
                        // Remove pattern like "D-03A: Inspirado en " or "Inspirado en "
                        $clean_title = preg_replace('/^.*:\s*Inspirado en\s*/i', '', $title);
                        // Fallback in case the pattern is slightly different (just "Inspirado en ")
                        if ($clean_title === $title) {
                            $clean_title = preg_replace('/^Inspirado en\s*/i', '', $title);
                        }
                        echo esc_html($clean_title);
                    ?>
                 </a>
            </h2>
            
            <div class="text-primary font-bold text-sm md:text-base mt-auto">
                <?php echo $product->get_price_html(); ?>
            </div>
        </div>
    </div>
</li>
