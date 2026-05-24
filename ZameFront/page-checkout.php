<?php
/**
 * Template Name: Checkout Page
 *
 * This template is used specifically for the checkout page to ensure proper
 * layout width (max-w-7xl) which is required for the two-column grid design.
 * It overrides the default page.php which uses a narrower container.
 */

get_header(); ?>

<main class="max-w-7xl mx-auto py-20 px-4 flex-grow w-full">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        
        <h1 class="text-4xl font-display font-bold text-center mb-12 text-primary tracking-wide">
            Finalizar Compra
        </h1>

        <div class="w-full">
            <?php the_content(); ?>
        </div>

    <?php endwhile; endif; ?>
</main>

<?php get_footer(); ?>
