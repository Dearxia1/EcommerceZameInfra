<?php
/**
 * Template Name: Shopping Cart
 * Description: A clean, premium template for the WooCommerce Cart page.
 */

get_header(); ?>

<main class="max-w-7xl mx-auto py-16 md:py-24 px-4 flex-grow w-full bg-white dark:bg-black transition-colors duration-300">
    <div class="mb-12">
        <h1 class="text-4xl md:text-5xl font-display font-medium text-gray-900 dark:text-white"><?php the_title(); ?></h1>
    </div>

    <div class="woocommerce-cart-container">
        <?php
        while ( have_posts() ) :
            the_post();
            the_content();
        endwhile;
        ?>
    </div>
</main>

<?php get_footer(); ?>
