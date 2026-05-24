<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked woocommerce_output_all_notices - 10
 */
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // WPCS: XSS ok.
	return;
}
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>

    <!-- Luxury Breadcrumb (Top) -->
    <div class="mb-12 text-[10px] uppercase tracking-[0.2em] text-gray-400 text-center md:text-left font-medium">
        <?php woocommerce_breadcrumb( array( 'delimiter' => ' <span class="text-primary/40 px-2">/</span> ' ) ); ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 xl:gap-24 items-start">
        
        <!-- Column 1: Gallery (Museum Frame) -->
        <div class="product-gallery-section lg:sticky lg:top-24">
            <div class="gallery-frame product-gallery-wrapper transition-all duration-700 hover:shadow-2xl">
                <?php do_action( 'woocommerce_before_single_product_summary' ); ?>
            </div>
        </div>

        <!-- Column 2: Info & Details -->
        <div class="product-info-section pt-4">
            
            <!-- Eyebrow: Category / Inspiration -->
            <div class="mb-6 flex flex-wrap items-center gap-4">
                <div class="text-primary font-bold uppercase tracking-[0.25em] text-[10px] border-b border-primary/30 inline-block pb-1">
                    <?php echo wc_get_product_category_list( $product->get_id(), ', ' ); ?>
                </div>
                <!-- Optional: Add "Inspirado en..." logic here if custom field exists -->
            </div>

            <!-- Title -->
            <h1 class="font-luxury-title text-4xl md:text-5xl lg:text-6xl text-gray-900 dark:text-white mb-6 leading-none">
                <?php the_title(); ?>
            </h1>

            <!-- Price with Gold Gradient -->
            <div class="mb-10 flex items-center gap-4">
                 <div class="text-3xl md:text-4xl font-light font-luxury-title text-gold-gradient">
                    <?php echo $product->get_price_html(); ?>
                 </div>
            </div>

            <!-- Short Description -->
            <div class="prose prose-sm text-gray-600 dark:text-gray-400 mb-12 leading-loose font-light tracking-wide">
                <?php the_excerpt(); ?>
            </div>

            <!-- Custom Product Configurator (JS Controlled) -->
            <div id="zame-product-configurator" class="mb-12">
                
                <!-- 1. Attributes (Only for Variable Products) -->
                <?php if ( $product->is_type( 'variable' ) ) : ?>
                    <div class="mb-8 space-y-6">
                        <?php foreach ( $product->get_variation_attributes() as $attribute_name => $options ) : ?>
                            <div class="custom-attribute-group">
                                <label class="text-[10px] uppercase tracking-[0.2em] font-bold mb-3 block text-gray-400">
                                    <?php echo wc_attribute_label( $attribute_name ); ?>
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    <?php 
                                    if ( is_array( $options ) ) {
                                        foreach ( $options as $option ) {
                                            $sanitize_name = sanitize_title( $attribute_name );
                                            // Handle cases where attribute name might need 'attribute_' prefix logic or raw name
                                            // WC forms use 'attribute_pa_color' or 'attribute_color' depending on taxonomy.
                                            // Simplest approach: We bind to the selects by iterating selects in JS, or we guess the name.
                                            // To be safe, we let JS find the select by approximate name or just pass raw name.
                                            // Let's pass 'attribute_' + sanitized name which is standard for form fields.
                                            ?>
                                            <button type="button" 
                                                    class="custom-attribute-swatch px-5 py-3 border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-300 transition-all uppercase tracking-widest text-[10px] font-medium hover:border-primary hover:text-primary" 
                                                    data-attribute="attribute_<?php echo esc_attr( $sanitize_name ); ?>" 
                                                    data-value="<?php echo esc_attr( $option ); ?>">
                                                <?php echo esc_html( $option ); ?>
                                            </button>
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php
                /**
                 * ZAME: Dynamic Fragrance Catalog for Visual Kit Builder
                 * Fetches all products to match names with images.
                 */
                /**
                 * ZAME: Dynamic Fragrance Catalog for Visual Kit Builder
                 * Cached query to improve performance.
                 */
                $zame_catalog = get_transient( 'zame_catalog_cache' );

                if ( false === $zame_catalog ) {
                    $catalog_args = array(
                        'post_type'      => 'product',
                        'posts_per_page' => -1,
                        'post_status'    => 'publish',
                        'fields'         => 'ids', // Only get IDs for performance if possible, but we need titles/images.
                        'no_found_rows'  => true,  // Pagination not needed
                    );
                    $catalog_query = new WP_Query( $catalog_args );
                    $zame_catalog = array();
                    
                    if ( $catalog_query->have_posts() ) {
                        while ( $catalog_query->have_posts() ) {
                            $catalog_query->the_post();
                            $valid_image = get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' );
                            if ( $valid_image ) {
                                $zame_catalog[] = array(
                                    'name'  => get_the_title(),
                                    'image' => $valid_image
                                );
                            }
                        }
                        wp_reset_postdata();
                    }

                    // Cache for 12 hours
                    set_transient( 'zame_catalog_cache', $zame_catalog, 12 * HOUR_IN_SECONDS );
                }
                ?>
                <script type="text/javascript">
                    window.ZAME_CATALOG = <?php echo json_encode( $zame_catalog ); ?>;
                </script>
                
                <!-- 1.5. Advanced Product Fields (WAPF) Integration -->
                <div id="zame-wapf-container" class="mb-10 empty:hidden">
                    <?php 
                    // Manually trigger the WAPF display
                    // The plugin usually hooks into 'woocommerce_before_add_to_cart_button'
                    // but we can also call the controller display method if available.
                    // To stay safe and compatible, we'll trigger the hook here.
                    do_action( 'woocommerce_before_add_to_cart_button' ); 
                    ?>
                </div>

                <!-- 2. Price & Actions Container -->
                <div class="grid grid-cols-2 md:flex md:flex-row gap-0 md:gap-6 items-stretch md:items-center p-0 md:p-1 bg-white dark:bg-white/5 border border-gray-100 dark:border-white/10 rounded-lg overflow-hidden md:rounded-none md:overflow-visible">
                    
                    <!-- Dynamic Price Display -->
                    <div id="custom-price-container" class="col-span-1 md:w-1/3 flex items-center justify-center p-4 border-r border-gray-100 dark:border-white/5 md:border-b-0 text-xl font-luxury-title text-gold-gradient" data-original-price="<?php 
                        // User Request: Don't show range by default. Show a symbol/placeholder.
                        $initial_html = $product->get_price_html();
                        if ( $product->is_type('variable') ) {
                            // Placeholder style: Stylized Dollar Sign (Inactive color)
                            $initial_html = '<span class="text-gray-300 dark:text-gray-600 text-4xl">$</span>';
                        }
                        echo esc_attr( '<span class="price">' . $initial_html . '</span>' ); 
                    ?>">
                        <span class="price">
                            <?php 
                            if ( $product->is_type('variable') ) {
                                echo '<span class="text-gray-300 dark:text-gray-600 text-4xl">$</span>';
                            } else {
                                echo $product->get_price_html();
                            }
                            ?>
                        </span>
                    </div>

                    <!-- Quantity Selector -->
                    <div class="col-span-1 flex items-center justify-center p-2 md:p-0 md:border-r border-gray-100 dark:border-white/5">
                        <div class="flex items-center h-full">
                            <button type="button" id="custom-qty-minus" class="w-8 h-10 md:h-12 flex items-center justify-center text-gray-400 hover:text-primary transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                                <span class="material-icons-outlined text-sm">remove</span>
                            </button>
                            <input type="number" id="custom-qty-input" value="1" min="1" max="50" class="w-10 text-center border-none bg-transparent text-sm font-bold appearance-none p-0 focus:ring-0 text-gray-900 dark:text-white">
                            <button type="button" id="custom-qty-plus" class="w-8 h-10 md:h-12 flex items-center justify-center text-gray-400 hover:text-primary transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                                <span class="material-icons-outlined text-sm">add</span>
                            </button>
                        </div>
                    </div>

                    <!-- Add to Cart Action -->
                    <div class="col-span-2 md:flex-1 flex items-center p-2 border-t border-gray-100 dark:border-white/5 md:border-t-0">
                        <button id="custom-add-to-cart-btn" class="w-full h-12 bg-black dark:bg-white text-white dark:text-black uppercase tracking-[0.2em] text-xs font-bold hover:bg-primary hover:text-white dark:hover:bg-primary dark:hover:text-white transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center rounded-md md:rounded-none">
                            <?php echo $product->is_type('variable') ? 'Selecciona Opciones' : 'Añadir al Carrito'; ?>
                        </button>
                    </div>

                </div>

                <!-- Hidden Native Form (Required for Logic) -->
                <div class="hidden-wc-form opacity-0 h-0 overflow-hidden pointer-events-none absolute">
                    <?php woocommerce_template_single_add_to_cart(); ?>
                </div>

            </div>
            
            <!-- Trust Signals (Immediately below CTA) -->
            <div class="grid grid-cols-3 gap-4 mb-12 border-t border-b border-gray-100 dark:border-white/10 py-6">
                <div class="flex flex-col items-center text-center gap-2 group">
                    <span class="material-icons-outlined text-gray-400 group-hover:text-primary transition-colors text-2xl">local_shipping</span>
                    <span class="text-[9px] uppercase tracking-widest font-bold text-gray-500">Envío Gratis +150k</span>
                </div>
                <div class="flex flex-col items-center text-center gap-2 group">
                    <span class="material-icons-outlined text-gray-400 group-hover:text-primary transition-colors text-2xl">verified_user</span>
                    <span class="text-[9px] uppercase tracking-widest font-bold text-gray-500">Garantía 33%</span>
                </div>
                <div class="flex flex-col items-center text-center gap-2 group">
                    <span class="material-icons-outlined text-gray-400 group-hover:text-primary transition-colors text-2xl">lock</span>
                    <span class="text-[9px] uppercase tracking-widest font-bold text-gray-500">Pago Seguro</span>
                </div>
            </div>

            <!-- Premium Accordion (Replaces Tabs) -->
            <div class="border-t border-gray-100 dark:border-white/10 mb-8">
                <!-- Description -->
                <details class="group py-5 border-b border-gray-100 dark:border-white/10 cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-white/5 px-2" open>
                    <summary class="flex items-center justify-between font-bold uppercase tracking-[0.15em] text-xs select-none text-gray-900 dark:text-white list-none">
                        Descripción Detallada
                        <span class="material-icons-outlined transition-transform duration-300 group-open:rotate-180 text-primary">expand_more</span>
                    </summary>
                    <div class="pt-6 pb-2 text-sm text-gray-500 dark:text-gray-400 leading-relaxed font-light animate-fadeIn tracking-wide">
                        <?php the_content(); ?>
                    </div>
                </details>
                
                <!-- Additional Info (Attributes) -->
                <?php if ( $product->has_attributes() ) : ?>
                <details class="group py-5 border-b border-gray-100 dark:border-white/10 cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-white/5 px-2">
                    <summary class="flex items-center justify-between font-bold uppercase tracking-[0.15em] text-xs select-none text-gray-900 dark:text-white list-none">
                        Notas Olfativas
                        <span class="material-icons-outlined transition-transform duration-300 group-open:rotate-180 text-primary">expand_more</span>
                    </summary>
                    <div class="pt-6 pb-2 text-sm text-gray-500 dark:text-gray-400 leading-relaxed font-light animate-fadeIn tracking-wide">
                        <?php wc_display_product_attributes( $product ); ?>
                    </div>
                </details>
                <?php endif; ?>
            </div>

            <!-- Meta & Sharing -->
            <div class="pt-6 space-y-3 text-[9px] tracking-[0.2em] uppercase text-gray-400 font-medium">
                <div class="flex items-center gap-2">
                     <span class="text-primary">SKU:</span> 
                     <?php echo ( $sku = $product->get_sku() ) ? $sku : esc_html__( 'N/D', 'woocommerce' ); ?>
                </div>
                 <div class="flex items-center gap-2">
                     <span class="text-primary">Etiquetas:</span> 
                     <?php echo wc_get_product_tag_list( $product->get_id(), ', ', '', '' ); ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Related Products (Full Width Below) -->
    <?php
    /**
     * Related Products - Direct Template Loading
     * Using wc_get_related_products() + wc_get_template() for reliable rendering.
     * @see ADR: 2026-02-01-fix-related-products-refactor.md
     */
    
    // Remove tabs from standard hook just in case
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
    
    // Get related product IDs
    $related_product_ids = wc_get_related_products( $product->get_id(), 4 );
    
    if ( $related_product_ids ) {
        // Convert IDs to WC_Product objects
        $related_products = array_filter( array_map( 'wc_get_product', $related_product_ids ) );
        
        if ( $related_products ) {
            wc_get_template(
                'single-product/related.php',
                array(
                    'related_products' => $related_products,
                    'columns'          => 4,
                )
            );
        }
    }
    
    // Upsells
    woocommerce_upsell_display();
    ?>

</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
