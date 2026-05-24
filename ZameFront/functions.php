<?php
function zame_setup() {
    add_theme_support('woocommerce');
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    register_nav_menus(array(
        'primary' => 'Menú Principal',
    ));
}
add_action('after_setup_theme', 'zame_setup');

function zame_assets() {
    // Carga el CSS generado por Tailwind
    wp_enqueue_style('zame-tailwind', get_template_directory_uri() . '/build.css', array(), time());
    // Fuentes de Google y Iconos
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&family=Material+Icons+Outlined&display=swap', array(), null);

    // Gallery Fix (Hide sticky trigger)
    wp_enqueue_style('zame-gallery-fix', get_template_directory_uri() . '/assets/css/temp_gallery_fix.css', array(), time());

    // Product Swatches & Cart logic (Legacy)
    if ( is_singular('product') || is_cart() ) {
        wp_enqueue_script('zame-scripts', get_template_directory_uri() . '/assets/js/product-swatches.js', array('jquery'), '3.5.0-FORCE-RELOAD', true);
    }

    // New Custom Product Configurator (JS Puppet)
    if ( is_singular('product') ) {
        wp_enqueue_script('zame-product-configurator', get_template_directory_uri() . '/assets/js/product-interaction.js', array('jquery'), '3.5.0-FORCE-RELOAD', true);
        
        // Pass WooCommerce cart params to JS (nonce for AJAX security)
        wp_localize_script('zame-product-configurator', 'zame_cart_params', array(
            'ajax_url'   => admin_url('admin-ajax.php'),
            'wc_ajax_url' => WC_AJAX::get_endpoint('%%endpoint%%'),
            'cart_nonce' => wp_create_nonce('woocommerce-add-to-cart')
        ));
    }
}

/**
 * 🚀 PERFORMANCE & CACHE CONTROL
 * Force disable server cache (LiteSpeed/Cloudflare) when user has items in cart.
 * This programmatic approach complements server-side configuration.
 */
add_action( 'template_redirect', 'zame_prevent_cache_on_active_session' );
function zame_prevent_cache_on_active_session() {
    if ( ! function_exists('WC') || is_admin() ) return;

    // If cart has items, tell the server NOT to serve cached HTML
    if ( WC()->cart && ! WC()->cart->is_empty() ) {
        // WordPress native
        nocache_headers();
        
        // LiteSpeed specific
        header( 'X-LiteSpeed-Cache-Control: no-cache' );
        
        // Cloudflare / General CDNs (tell them this page is private for this user)
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'Pragma: no-cache' );
    }
}

/**
 * ZAME: Force Load Cart Fragments (Fix for WC 7.8+)
 * Ensures AJAX cart updates work even on cached pages where WC might verify the widget presence incorrectly.
 * Ref: Section 6.3 of Technical Report
 */
add_action( 'wp_enqueue_scripts', 'zame_force_queue_cart_fragments' );
function zame_force_queue_cart_fragments() {
    if ( function_exists( 'is_woocommerce' ) ) {
        wp_enqueue_script( 'wc-cart-fragments' );
    }
}

add_action('wp_enqueue_scripts', 'zame_assets');
// Registro de Custom Post Type para Testimonios
function zame_register_testimonials() {
    $labels = array(
        'name'               => 'Testimonios',
        'singular_name'      => 'Testimonio',
        'menu_name'          => 'Testimonios',
        'add_new'            => 'Añadir Nuevo',
        'add_new_item'       => 'Añadir Nuevo Testimonio',
        'edit_item'          => 'Editar Testimonio',
        'new_item'           => 'Nuevo Testimonio',
        'view_item'          => 'Ver Testimonio',
        'search_items'       => 'Buscar Testimonios',
        'not_found'          => 'No se encontraron testimonios',
        'not_found_in_trash' => 'No hay testimonios en la papelera',
        'featured_image'     => 'Foto del Cliente',
        'set_featured_image' => 'Elegir Foto del Cliente',
        'remove_featured_image' => 'Quitar Foto',
        'use_featured_image'    => 'Usar como Foto del Cliente',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => false,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true, // Habilita el editor de bloques (Gutenberg)
        'query_var'          => true,
        'rewrite'            => array('slug' => 'testimonio'),
        'capability_type'    => 'post',
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-format-quote',
        'supports'           => array('title', 'editor', 'thumbnail'),
    );

    register_post_type('testimonial', $args);
}
add_action('init', 'zame_register_testimonials');

// Cambiar el placeholder del título para Testimonios
function zame_change_testimonial_title_placeholder($title) {
    $screen = get_current_screen();
    if (isset($screen->post_type) && 'testimonial' == $screen->post_type) {
        $title = 'Nombre del Cliente (ej. Valentina P.)';
    }
    return $title;
}
add_filter('enter_title_here', 'zame_change_testimonial_title_placeholder');

// Clean up "Notes & Details" Accordion (Remove logistic info)
add_filter( 'woocommerce_display_product_attributes', 'zame_clean_attributes', 10, 2 );
function zame_clean_attributes( $product_attributes, $product ) {
    // 1. Remove Logistics
    $unwanted_keys = array('weight', 'dimensions');

    // 2. Remove Variation Attributes (Concentration, Size, etc.)
    if ( $product->is_type( 'variable' ) ) {
        $variation_attributes = $product->get_variation_attributes();
        foreach ( $variation_attributes as $attribute_name => $options ) {
             // WC prefixes attributes with 'attribute_' in this specific display array
             $unwanted_keys[] = 'attribute_' . $attribute_name;
        }
    }

    foreach ( $unwanted_keys as $key ) {
        if ( isset( $product_attributes[ $key ] ) ) {
            unset( $product_attributes[ $key ] );
        }
    }
    
    return $product_attributes;
}
// Ajax count update
add_filter( 'woocommerce_add_to_cart_fragments', 'zame_cart_count_fragments' );
function zame_cart_count_fragments( $fragments ) {
    ob_start();
    ?>
    <span class="cart-count absolute -top-1 -right-1 bg-primary text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
        <?php echo WC()->cart->get_cart_contents_count(); ?>
    </span>
    <?php
    $fragments['span.cart-count'] = ob_get_clean();
    
    // Mini cart content fragment
    ob_start();
    ?>
    <div class="flex-1 overflow-y-auto py-8 px-6 zame-mini-cart-container">
        <?php woocommerce_mini_cart(); ?>
    </div>
    <?php
    $fragments['div.zame-mini-cart-container'] = ob_get_clean();

    return $fragments;
}

// Custom WooCommerce Breadcrumbs
add_filter( 'woocommerce_breadcrumb_defaults', 'zame_woocommerce_breadcrumbs' );
function zame_woocommerce_breadcrumbs() {
    return array(
        'delimiter'   => ' <span class="mx-2 opacity-30">/</span> ',
        'wrap_before' => '<nav class="woocommerce-breadcrumb py-6 text-[10px] uppercase tracking-[0.2em] font-medium text-gray-500 mb-8 overflow-x-auto whitespace-nowrap scrollbar-hide">',
        'wrap_after'  => '</nav>',
        'before'      => '',
        'after'       => '',
        'home'        => _x( 'Inicio', 'breadcrumb', 'woocommerce' ),
    );
}

// Simplify Checkout Fields for Colombia
add_filter( 'woocommerce_checkout_fields' , 'zame_simplify_checkout_fields' );
function zame_simplify_checkout_fields( $fields ) {
    // Remove Postal Code - Barely used in CO and causes friction
    unset($fields['billing']['billing_postcode']);
    unset($fields['shipping']['shipping_postcode']);
    
    // Remove Company Name
    unset($fields['billing']['billing_company']);
    unset($fields['shipping']['shipping_company']);

    // Remove Second Address Line (Optional for cleaner UI)
    unset($fields['billing']['billing_address_2']);
    unset($fields['shipping']['shipping_address_2']);

    return $fields;
}

// Ensure Breadcrumbs appear in Shop and Single Product
add_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);

// Hide redundant Page Title in Shop/Category pages (Hero handles it)
add_filter('woocommerce_show_page_title', '__return_false');
remove_action('woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10);
remove_action('woocommerce_archive_description', 'woocommerce_product_archive_description', 10);

// Checkout Trust Badges
add_action( 'woocommerce_review_order_after_submit', 'zame_checkout_trust_badges' );
function zame_checkout_trust_badges() {
    ?>
    <div class="grid grid-cols-3 gap-4 mt-8 pt-6 border-t border-gray-100 dark:border-white/10 text-center">
        <div class="flex flex-col items-center gap-2 group">
            <span class="material-icons-outlined text-gray-400 dark:text-gray-500 text-2xl group-hover:text-primary transition-colors">lock</span>
            <span class="text-[10px] uppercase tracking-wider font-bold text-gray-500 dark:text-gray-400">Pago Seguro</span>
        </div>
        <div class="flex flex-col items-center gap-2 group">
            <span class="material-icons-outlined text-gray-400 dark:text-gray-500 text-2xl group-hover:text-primary transition-colors">local_shipping</span>
            <span class="text-[10px] uppercase tracking-wider font-bold text-gray-500 dark:text-gray-400">Envío Rápido</span>
        </div>
        <div class="flex flex-col items-center gap-2 group">
            <span class="material-icons-outlined text-gray-400 dark:text-gray-500 text-2xl group-hover:text-primary transition-colors">verified</span>
            <span class="text-[10px] uppercase tracking-wider font-bold text-gray-500 dark:text-gray-400">Garantía</span>
        </div>
    </div>
    <?php
}

// Wrapper for Shop Meta (Result count & Ordering) to ensure perfect flex alignment
add_action('woocommerce_before_shop_loop', 'zame_shop_meta_wrapper_start', 15);
function zame_shop_meta_wrapper_start() {
    echo '<div class="shop-meta-controls mt-12 mb-12 flex flex-col md:flex-row md:items-center justify-between gap-6 border-b border-white/5 pb-10">';
}

add_action('woocommerce_before_shop_loop', 'zame_shop_meta_wrapper_end', 35);
function zame_shop_meta_wrapper_end() {
    echo '</div>';
}
// Deploy trigger

/**
 * Ensure Related Products Display
 * Force logic to show 4 related products in 4 columns
 */
add_filter( 'woocommerce_output_related_products_args', 'zame_related_products_args' );
function zame_related_products_args( $args ) {
    $args['posts_per_page'] = 4;
    $args['columns'] = 4;
    return $args;
}

// Update WooCommerce Cart Fragments (AJAX)
// This ensures the '.cart-count' in header updates dynamically without reload
function zame_cart_fragments( $fragments ) {
    ob_start();
    ?>
    <span class="cart-count absolute -top-1 -right-1 bg-primary text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center <?php echo (WC()->cart->get_cart_contents_count() > 0) ? '' : 'hidden'; ?>">
        <?php echo WC()->cart->get_cart_contents_count(); ?>
    </span>
    <?php
    $fragments['span.cart-count'] = ob_get_clean();
    
    return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'zame_cart_fragments' );
