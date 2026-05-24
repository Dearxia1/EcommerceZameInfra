<?php get_header(); ?>

<main>
    
    <!-- Premium Minimalist Hero Section -->
    <section class="relative h-[85vh] w-full bg-zinc-950 overflow-hidden">
        
        <!-- Background with Ken Burns Effect -->
        <div class="absolute inset-0 z-0">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/hero-bg.png" 
                 alt="Luxury Background" 
                 class="w-full h-full object-cover opacity-80 animate-ken-burns dark:opacity-60" />
            
            <!-- Strategic Multi-layered Overlay for Readability -->
            <!-- 1. Bottom Darkening -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
            <!-- 2. Central Focus (Subtle Vignette) -->
            <div class="absolute inset-0 bg-black/40"></div>
        </div>
        
        <div class="absolute inset-0 z-10 flex flex-col items-center justify-center text-center px-6">
            <div class="max-w-4xl space-y-6">
                <!-- Headline with subtle shadow and letter spacing -->
                <h1 class="text-6xl md:text-9xl font-display font-medium text-white drop-shadow-2xl mb-4 animate-fadeIn-subtle tracking-tighter">
                    ZAME <span class="italic font-light opacity-90">SCENT</span>
                </h1>
                
                <!-- Subheadline with improved contrast -->
                <p class="text-white/80 text-sm md:text-lg tracking-[0.3em] uppercase font-light animate-fadeIn-subtle [animation-delay:0.3s] drop-shadow-md max-w-2xl mx-auto leading-relaxed">
                    Inspiraciones maestras de la alta perfumería mundial
                </p>
                
                <div class="pt-8 animate-fadeIn-subtle [animation-delay:0.6s]">
                    <a href="/tienda" class="glass-morphism px-12 py-5 text-white font-bold uppercase tracking-[0.2em] hover:bg-white hover:text-black transition-all duration-700 shadow-2xl rounded-sm text-[10px] md:text-xs">
                        Explorar Colección
                    </a>
                </div>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 z-20 animate-bounce opacity-40">
            <span class="material-icons-outlined text-white text-3xl font-extralight">expand_more</span>
        </div>
    </section>




    <!-- Features Section (Restored with Icons) -->
    <section class="border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-neutral-900 py-16">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-12">
                <div class="flex flex-col items-center text-center group scroll-reveal animate-revealUp">
                    <span class="material-icons-outlined text-primary text-4xl mb-4 group-hover:scale-110 transition-transform">auto_awesome</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">95% Similitud</h3>
                    <p class="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400">Fragancias de alta gama</p>
                </div>
                <div class="flex flex-col items-center text-center group scroll-reveal animate-revealUp delay-100">
                    <span class="material-icons-outlined text-primary text-4xl mb-4 group-hover:scale-110 transition-transform">schedule</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">10h+ Duración</h3>
                    <p class="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400">Fijación prolongada</p>
                </div>
                <div class="flex flex-col items-center text-center group scroll-reveal animate-revealUp delay-200">
                    <span class="material-icons-outlined text-primary text-4xl mb-4 group-hover:scale-110 transition-transform">groups</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">+500 Clientes</h3>
                    <p class="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400">Satisfechos en todo el país</p>
                </div>
                <div class="flex flex-col items-center text-center group scroll-reveal animate-revealUp delay-300">
                    <span class="material-icons-outlined text-primary text-4xl mb-4 group-hover:scale-110 transition-transform">thumb_up_off_alt</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">9 de 10</h3>
                    <p class="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400">Nos recomiendan</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section (Restored Grid) -->
    <section class="py-24 px-4 bg-white dark:bg-background-dark">
        <div class="max-w-7xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-display text-center mb-16 text-gray-900 dark:text-white scroll-reveal animate-fadeIn-subtle">Explora por Familia Olfativa</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php
                // Get Product Categories
                $tax_terms = get_terms( array(
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => false,
                    'parent'     => 0,
                    'number'     => 4,
                ) );

                if ( ! empty( $tax_terms ) && ! is_wp_error( $tax_terms ) ) {
                    $loop_idx = 0;
                    foreach ( $tax_terms as $term ) {
                        if ( $term->slug === 'uncategorized' ) continue;

                        $thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
                        $image_url    = wp_get_attachment_url( $thumbnail_id );
                        
                        if ( ! $image_url ) {
                            $image_url = 'https://via.placeholder.com/600x800?text=' . urlencode($term->name);
                        }
                        
                        $term_link = get_term_link( $term );
                        $delay_class = 'delay-' . (($loop_idx++) * 100);
                        ?>
                         <a href="<?php echo esc_url( $term_link ); ?>" class="category-card relative h-96 overflow-hidden rounded-xl cursor-pointer group shadow-lg scroll-reveal animate-revealRight <?php echo $delay_class; ?>">
                            <img alt="Categoría <?php echo esc_attr( $term->name ); ?>" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" src="<?php echo esc_url( $image_url ); ?>"/>
                            <div class="category-overlay absolute inset-0 bg-black/40 transition-colors duration-300 flex items-center justify-center group-hover:bg-black/50 p-6 sm:p-8 md:p-10">
                                <span class="text-white font-display text-2xl sm:text-lg md:text-xl lg:text-3xl tracking-wide border-b-2 border-transparent group-hover:border-primary transition-all pb-1 text-center leading-tight max-w-full">
                                    <?php echo str_replace('/', '/<wbr>', esc_html( $term->name )); ?>
                                </span>
                            </div>
                        </a>
                        <?php
                    }
                } else {
                    echo '<p class="col-span-4 text-center text-gray-500">No hay categorías disponibles.</p>';
                }
                ?>
            </div>
        </div>
    </section>

    <?php
    // Detect products in 'Promociones' category OR currently on sale
    $sale_ids = wc_get_product_ids_on_sale();
    
    // Get IDs of products in the 'Promociones' category
    $cat_ids = get_posts(array(
        'post_type' => 'product',
        'numberposts' => -1,
        'fields' => 'ids',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => array('promociones', 'promocion', 'promo'),
            ),
        ),
    ));

    // Combine both sources
    $final_promo_ids = array_unique(array_merge((array)$cat_ids, (array)$sale_ids));
    
    // Always initialize excluded_ids
    $excluded_ids = array();

    if ( ! empty( $final_promo_ids ) ) :
        $promo_args = array(
            'post_type'      => 'product',
            'posts_per_page' => 4,
            'post__in'       => $final_promo_ids,
            'orderby'        => 'post__in', // Keep priorities
        );
        $promo_loop = new WP_Query( $promo_args );

        if ( $promo_loop->have_posts() ) : ?>
            <!-- Promotions Section -->
            <section class="py-24 px-4 bg-primary/5 dark:bg-primary/10 overflow-hidden relative border-t border-gray-100 dark:border-gray-800">
                <!-- Abstract Background Ornament -->
                <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/2 w-96 h-96 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="max-w-7xl mx-auto relative z-10">
                    <div class="flex flex-col md:flex-row items-center justify-between mb-16">
                        <div class="text-center md:text-left">
                            <h2 class="text-3xl md:text-5xl font-display text-gray-900 dark:text-white mb-2">Ofertas Exclusivas</h2>
                            <p class="text-primary font-medium tracking-widest uppercase text-xs">Viviendo el lujo con precios especiales</p>
                        </div>
                        <div class="hidden md:block h-[2px] flex-grow mx-12 bg-primary/20"></div>
                        <a href="/tienda" class="mt-6 md:mt-0 px-8 py-3 bg-primary text-white text-xs font-bold uppercase tracking-widest hover:bg-primary-dark transition-all rounded-full shadow-lg hover:shadow-primary/30">
                            Explorar Ofertas
                        </a>
                    </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <?php 
                    $promo_idx = 0;
                    while ( $promo_loop->have_posts() ) : $promo_loop->the_post();
                        global $product;
                        $excluded_ids[] = $product->get_id();
                        $promo_delay = 'delay-' . ($promo_idx++ * 150);
                        $price_html = $product->get_price_html();
                        ?>
                        <div class="bg-white dark:bg-neutral-800 rounded-xl overflow-hidden shadow-sm transition-all duration-500 group product-card hover-luxury scroll-reveal animate-revealUp <?php echo $promo_delay; ?>">
                            <div class="relative aspect-[1/1.1] overflow-hidden bg-white p-6 flex items-center justify-center">
                                <?php if ( $product->is_on_sale() ) : ?>
                                    <span class="absolute top-4 left-4 z-20 bg-accent-gold text-white text-[10px] font-bold px-3 py-1 rounded-full shadow-lg animate-pulse uppercase tracking-tighter">Oferta</span>
                                <?php endif; ?>
                                
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-300 z-10"></div>
                                
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail('medium', array('class' => 'w-full h-full object-contain transition-transform duration-700 group-hover:scale-110 relative z-0')); ?>
                                <?php endif; ?>

                                <!-- Quick Actions Overlay -->
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-20 gap-3">
                                    <!-- Visibility button removed for cleaner UX -->
                                    <a href="<?php the_permalink(); ?>" 
                                       class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center hover:bg-black transition-all shadow-xl"
                                       title="Configurar Oferta">
                                        <span class="material-icons-outlined text-xl">arrow_forward</span>
                                    </a>
                                </div>
                            </div>

                            <div class="p-6">
                                <div class="mb-2">
                                    <?php 
                                    $terms = get_the_terms( $product->get_id(), 'product_cat' );
                                    if ( ! empty( $terms ) ) :
                                        $cat = array_shift( $terms );
                                        echo '<span class="text-[10px] uppercase tracking-widest text-primary font-bold opacity-70">' . esc_html( $cat->name ) . '</span>';
                                    endif;
                                    ?>
                                </div>
                                <h3 class="font-display text-lg text-gray-900 dark:text-white mb-3 line-clamp-1 group-hover:text-primary transition-colors">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                <div class="flex items-center justify-between">
                                    <div class="luxury-price text-gray-900 dark:text-white font-medium">
                                        <?php echo $price_html; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </div>
        </section>
    <?php endif; // End if have posts 
    endif; // End if final_promo_ids ?>

    <!-- Top Products Section (Restored Cards) -->
    <section class="py-24 px-4 bg-gray-50 dark:bg-neutral-900/50 border-t border-gray-100 dark:border-gray-800">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row items-center justify-between mb-16">
                <h2 class="text-3xl md:text-4xl font-display text-gray-900 dark:text-white">Fragancias del Momento</h2>
                <div class="hidden md:block h-px flex-grow mx-12 bg-gray-200 dark:bg-gray-700"></div>
                <a href="/tienda" class="text-primary font-bold cursor-pointer hover:text-accent-gold transition-colors text-sm uppercase tracking-widest flex items-center gap-2">
                    Ver Catálogo <span class="material-icons-outlined text-base">arrow_forward</span>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php 
                $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => 4,
                    'post__not_in' => $excluded_ids, // Evitar duplicar los que ya salieron arriba
                );
                $loop = new WP_Query( $args );

                if ( $loop->have_posts() ) {
                    $prod_idx = 0;
                    while ( $loop->have_posts() ) : $loop->the_post();
                        global $product;
                        $prod_delay = 'delay-' . ($prod_idx++ * 100);
                        ?>
                        <div class="bg-white dark:bg-neutral-800 rounded-xl overflow-hidden shadow-sm transition-all duration-300 group product-card hover-luxury scroll-reveal animate-revealUp <?php echo $prod_delay; ?>">
                             <div class="relative aspect-[1/1.1] overflow-hidden bg-white p-6 flex items-center justify-center">
                                <?php 
                                if (has_post_thumbnail()) {
                                    the_post_thumbnail('medium', ['class' => 'w-full h-full object-contain group-hover:scale-105 transition-transform duration-500']);
                                } else {
                                    echo '<div class="text-gray-200 text-6xl">🧴</div>';
                                }
                                ?>
                                <?php if ( $product->is_on_sale() ) : ?>
                                    <div class="absolute top-4 right-4 bg-primary text-white text-[10px] px-3 py-1 rounded-full font-bold shadow-md">OFERTA</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="p-6 text-center">
                                <?php 
                                $cats = wc_get_product_category_list($product->get_id(), ', ', '', ''); 
                                if (!empty($cats)) : ?>
                                    <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2 truncate px-2">
                                        <?php echo strip_tags($cats); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <h3 class="font-bold text-gray-800 dark:text-white mb-2 line-clamp-2 min-h-[3rem] text-sm md:text-base group-hover:text-primary transition-colors">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                
                                <p class="text-primary font-semibold mb-5 text-lg">
                                    <?php echo $product->get_price_html(); ?>
                                </p>
                                
                                <a href="<?php the_permalink(); ?>" class="inline-block w-full py-3 border border-primary text-primary hover:bg-primary hover:text-white transition-all rounded-full text-xs font-bold uppercase tracking-widest">
                                    Ver producto
                                </a>
                            </div>
                        </div>
                        <?php
                    endwhile;
                } else {
                    echo '<p class="text-center w-full col-span-4 text-gray-500">No hay productos disponibles por el momento.</p>';
                }
                wp_reset_postdata();
                ?>
            </div>
        </div>
    <!-- Testimonials Section (New) -->
    <section class="py-24 px-4 bg-white dark:bg-background-dark overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16 scroll-reveal animate-fadeIn-subtle">
                <h2 class="text-3xl md:text-5xl font-display text-gray-900 dark:text-white mb-4">Lo que dicen de nosotros</h2>
                <div class="w-24 h-1 bg-primary mx-auto opacity-50"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php
                $args = array(
                    'post_type' => 'testimonial',
                    'posts_per_page' => 3,
                    'orderby' => 'date',
                    'order' => 'DESC'
                );
                $testimonial_query = new WP_Query($args);

                if ($testimonial_query->have_posts()) :
                    $t_idx = 0;
                    while ($testimonial_query->have_posts()) : $testimonial_query->the_post();
                        $t_delay = 'delay-' . ($t_idx++ * 200);
                        ?>
                        <div class="bg-gray-50 dark:bg-neutral-800 p-8 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-500 border border-gray-100 dark:border-gray-700/50 group scroll-reveal animate-revealUp <?php echo $t_delay; ?>">
                            <div class="flex text-accent-gold mb-6 group-hover:scale-105 transition-transform" style="color: #D4AF37;">
                                <span class="material-icons-outlined text-lg">star</span>
                                <span class="material-icons-outlined text-lg">star</span>
                                <span class="material-icons-outlined text-lg">star</span>
                                <span class="material-icons-outlined text-lg">star</span>
                                <span class="material-icons-outlined text-lg">star</span>
                            </div>
                            <blockquote class="text-gray-600 dark:text-gray-300 italic mb-8 relative">
                                <span class="absolute -top-4 -left-2 text-6xl text-primary/10 font-serif leading-none">“</span>
                                <?php echo wp_trim_words(get_the_content(), 40); ?>
                            </blockquote>
                            <div class="flex items-center gap-4">
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="w-12 h-12 rounded-full overflow-hidden">
                                        <?php the_post_thumbnail('thumbnail', array('class' => 'w-full h-full object-cover')); ?>
                                    </div>
                                <?php else : ?>
                                    <div class="w-12 h-12 bg-primary/20 rounded-full flex items-center justify-center text-primary font-bold">
                                        <?php 
                                        $initials = '';
                                        $words = explode(' ', get_the_title());
                                        foreach ($words as $w) {
                                            $initials .= strtoupper($w[0]);
                                        }
                                        echo substr($initials, 0, 2);
                                        ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white uppercase tracking-widest text-xs"><?php the_title(); ?></p>
                                    <p class="text-[10px] text-gray-400">Cliente Verificado</p>
                                </div>
                            </div>
                        </div>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else : ?>
                    <!-- Fallback: Static Mock Testimonials (Show if no CPT posts yet) -->
                    <!-- Testimonial 1 -->
                    <div class="bg-gray-50 dark:bg-neutral-800 p-8 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-500 border border-gray-100 dark:border-gray-700/50 group scroll-reveal animate-revealUp">
                        <div class="flex text-accent-gold mb-6 group-hover:scale-105 transition-transform" style="color: #D4AF37;">
                            <span class="material-icons-outlined text-lg">star</span><span class="material-icons-outlined text-lg">star</span><span class="material-icons-outlined text-lg">star</span><span class="material-icons-outlined text-lg">star</span><span class="material-icons-outlined text-lg">star</span>
                        </div>
                        <blockquote class="text-gray-600 dark:text-gray-300 italic mb-8 relative">
                            <span class="absolute -top-4 -left-2 text-6xl text-primary/10 font-serif leading-none">“</span>
                            La fijación es increíble. Compré la inspiración de Baccarat Rouge y me dura todo el día. Definitivamente el mejor lujo asequible que he encontrado.
                        </blockquote>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-primary/20 rounded-full flex items-center justify-center text-primary font-bold">VP</div>
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white uppercase tracking-widest text-xs">Valentina P.</p>
                                <p class="text-[10px] text-gray-400">Cliente Verificado</p>
                            </div>
                        </div>
                    </div>
                    <!-- Testimonial 2 -->
                    <div class="bg-gray-50 dark:bg-neutral-800 p-8 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-500 border border-gray-100 dark:border-gray-700/50 group scroll-reveal animate-revealUp delay-200">
                        <div class="flex text-accent-gold mb-6 group-hover:scale-105 transition-transform" style="color: #D4AF37;">
                            <span class="material-icons-outlined text-lg">star</span><span class="material-icons-outlined text-lg">star</span><span class="material-icons-outlined text-lg">star</span><span class="material-icons-outlined text-lg">star</span><span class="material-icons-outlined text-lg">star</span>
                        </div>
                        <blockquote class="text-gray-600 dark:text-gray-300 italic mb-8 relative">
                            <span class="absolute -top-4 -left-2 text-6xl text-primary/10 font-serif leading-none">“</span>
                            Como amante de los perfumes, era escéptico. Pero ZAME me sorprendió. El empaque es hermoso y el aroma es idéntico al original. Repetiré seguro.
                        </blockquote>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-primary/20 rounded-full flex items-center justify-center text-primary font-bold">CR</div>
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white uppercase tracking-widest text-xs">Carlos R.</p>
                                <p class="text-[10px] text-gray-400">Cliente Verificado</p>
                            </div>
                        </div>
                    </div>
                    <!-- Testimonial 3 -->
                    <div class="bg-gray-50 dark:bg-neutral-800 p-8 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-500 border border-gray-100 dark:border-gray-700/50 group scroll-reveal animate-revealUp delay-400">
                        <div class="flex text-accent-gold mb-6 group-hover:scale-105 transition-transform" style="color: #D4AF37;">
                            <span class="material-icons-outlined text-lg">star</span><span class="material-icons-outlined text-lg">star</span><span class="material-icons-outlined text-lg">star</span><span class="material-icons-outlined text-lg">star</span><span class="material-icons-outlined text-lg">star</span>
                        </div>
                        <blockquote class="text-gray-600 dark:text-gray-300 italic mb-8 relative">
                            <span class="absolute -top-4 -left-2 text-6xl text-primary/10 font-serif leading-none">“</span>
                            Excelente servicio al cliente y el envío fue súper rápido. Mi perfume llegó en perfectas condiciones. ¡Huele delicioso y proyecta muchísimo!
                        </blockquote>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-primary/20 rounded-full flex items-center justify-center text-primary font-bold">MA</div>
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white uppercase tracking-widest text-xs">Mariana A.</p>
                                <p class="text-[10px] text-gray-400">Cliente Verificado</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
