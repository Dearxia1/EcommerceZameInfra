<?php get_header(); ?>

<main class="<?php echo is_checkout() || is_cart() ? 'max-w-7xl' : 'max-w-4xl'; ?> mx-auto py-20 px-4 flex-grow">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        
        <h1 class="text-4xl font-display font-bold text-center mb-12 text-primary tracking-wide">
            <?php the_title(); ?>
        </h1>

        <div class="prose max-w-none prose-lg prose-headings:font-display prose-headings:text-background-dark prose-p:text-gray-600 prose-a:text-primary hover:prose-a:text-yellow-600">
            <?php the_content(); ?>
        </div>

    <?php endwhile; endif; ?>
</main>

<?php get_footer(); ?>
