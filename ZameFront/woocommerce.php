<?php get_header(); ?>

<main class="py-20 bg-white dark:bg-background-dark min-h-screen">
    
    <div class="max-w-7xl mx-auto px-4">
        
        <?php 
        // Luxury Hero Logic
        if ( is_product_category() || is_shop() ) {
            $title = woocommerce_page_title(false);
            $bg_image = get_template_directory_uri() . '/assets/images/hero-bg.png';
            $description = '';
            
            if ( is_product_category() ) {
                $term = get_queried_object();
                $thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
                if ( $thumbnail_id ) {
                    $bg_image = wp_get_attachment_url( $thumbnail_id );
                }
                $description = $term->description;
            }
            ?>
            <div class="relative w-full h-[40vh] md:h-[65vh] flex items-center justify-center overflow-hidden mb-16 group bg-background-dark">
                <!-- Premium Background with Zoom effect -->
                <div class="absolute inset-0 transition-transform duration-[15s] ease-out group-hover:scale-110">
                    <img src="<?php echo esc_url($bg_image); ?>" 
                         class="w-full h-full object-cover opacity-80 object-center" 
                         alt="<?php echo esc_attr($title); ?>"
                         loading="eager">
                </div>
                
                <!-- Refined Gradient & Blur Overlays -->
                <div class="absolute inset-0 bg-gradient-to-b from-black/80 via-black/40 to-black/80 z-10"></div>
                
                <div class="relative z-30 text-center px-6 max-w-7xl mx-auto">
                    <div class="mb-4">
                        <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-display font-medium text-white drop-shadow-2xl scroll-reveal animate-revealUp italic leading-tight text-balance">
                            <?php echo esc_html($title); ?>
                        </h1>
                    </div>
                    
                    <?php if ( !empty($description) ) : ?>
                    <p class="text-white/80 text-xs md:text-lg max-w-4xl mx-auto font-light tracking-[0.1em] md:border-l md:border-white/20 md:pl-6 pl-0 border-none scroll-reveal animate-revealRight delay-300 text-center md:text-left text-balance mb-6">
                        <?php echo esc_html($description); ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="flex items-center justify-center gap-2 md:gap-4 scroll-reveal animate-fadeIn-subtle delay-500">
                        <div class="w-8 md:w-12 h-[1px] bg-primary/50"></div>
                        <span class="text-[9px] md:text-[10px] uppercase tracking-[0.2em] md:tracking-[0.5em] text-primary font-bold">Zame Scent</span>
                        <div class="w-8 md:w-12 h-[1px] bg-primary/50"></div>
                    </div>
                </div>
                
                <!-- Decorative Elements -->
                <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-white dark:from-background-dark to-transparent z-20"></div>
            </div>
        <?php } ?>

        <div class="woocommerce-content-wrapper font-sans">
            <?php woocommerce_content(); ?>
        </div>

    </div>

</main>

<?php get_footer(); ?>
