<?php
// Silent is golden? No, show something!
?>
<!DOCTYPE html>
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 */

get_header(); ?>

<main class="container mx-auto px-4 py-12">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) :
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('mb-12 prose dark:prose-invert max-w-none'); ?>>
                <h1 class="text-4xl font-display font-medium mb-6"><?php the_title(); ?></h1>
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>
            <?php
        endwhile;

        the_posts_navigation();

    else :
        ?>
        <div class="text-center py-20">
            <h2 class="text-2xl font-bold mb-4">No se encontró contenido</h2>
            <p>Parece que no hay nada aquí. ¿Tal vez intentar una búsqueda?</p>
            <?php get_search_form(); ?>
        </div>
        <?php
    endif;
    ?>
</main>

<?php
get_footer();
?>
