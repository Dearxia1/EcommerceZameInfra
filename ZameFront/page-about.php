<?php
/**
 * Template Name: About Us
 * Description: A premium "About Us" page layout.
 */

get_header(); ?>

<main class="bg-white dark:bg-black text-gray-900 dark:text-gray-100">

    <!-- Hero Section -->
    <section class="relative h-[60vh] flex items-center justify-center overflow-hidden">
        <div class="absolute inset-0 bg-gray-900">
            <!-- Placeholder for Hero Image -->
            <img src="https://images.unsplash.com/photo-1615634260167-c8cdede054de?q=80&w=2070&auto=format&fit=crop" alt="Background" class="w-full h-full object-cover opacity-40">
        </div>
        <div class="relative z-10 text-center px-4 max-w-4xl mx-auto">
            <h1 class="text-5xl md:text-7xl font-display font-medium text-white mb-6">Nuestra Esencia</h1>
            <p class="text-lg md:text-xl text-gray-200 font-light tracking-wide max-w-2xl mx-auto">
                Más que fragancias, creamos memorias olfativas que trascienden el tiempo.
            </p>
        </div>
    </section>

    <!-- Story Section -->
    <section class="py-20 md:py-32 px-6">
        <div class="max-w-3xl mx-auto text-center">
            <span class="text-xs font-bold tracking-[0.2em] uppercase text-gray-400 mb-6 block">La Historia</span>
            <h2 class="text-3xl md:text-4xl font-display font-medium mb-8 leading-relaxed">
                "Creemos en el poder invisible del aroma para contar historias que las palabras no pueden alcanzar."
            </h2>
            <div class="prose prose-lg mx-auto text-gray-600 dark:text-gray-400 font-light leading-loose">
                <p class="mb-6">
                    ZAME nació de una búsqueda incansable por la perfección. En un mundo saturado de lo efímero, decidimos detenernos y volver a lo esencial: la artesanía, la paciencia y la calidad sin compromisos.
                </p>
                <p>
                    Cada botella es el resultado de cientos de horas de experimentación, seleccionando ingredientes de origen ético y combinándolos con la precisión de un alquimista. No seguimos tendencias; buscamos la atemporalidad.
                </p>
            </div>
            <!-- Signature / Visual Element -->
            <div class="mt-12 opacity-50 font-display italic text-2xl">
                El Equipo Zame
            </div>
        </div>
    </section>

    <!-- Visual Interlude (Parallax-ish) -->
    <section class="h-[50vh] bg-fixed bg-center bg-cover relative" style="background-image: url('https://images.unsplash.com/photo-1557170334-a9632e77c6e4?q=80&w=2070&auto=format&fit=crop');">
        <div class="absolute inset-0 bg-black/20"></div>
    </section>

    <!-- Values Grid -->
    <section class="py-24 bg-gray-50 dark:bg-zinc-900 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 text-center">
                <!-- Value 1 -->
                <div class="space-y-4">
                    <div class="w-16 h-16 mx-auto bg-gray-200 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-6">
                        <span class="material-icons-outlined text-2xl">diamond</span>
                    </div>
                    <h3 class="font-display text-xl font-bold">Artesanía Pura</h3>
                    <p class="text-gray-500 font-light leading-relaxed max-w-xs mx-auto">
                        Cada fragancia es mezclada a mano en pequeños lotes para garantizar una calidad inigualable.
                    </p>
                </div>
                <!-- Value 2 -->
                <div class="space-y-4">
                    <div class="w-16 h-16 mx-auto bg-gray-200 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-6">
                        <span class="material-icons-outlined text-2xl">spa</span>
                    </div>
                    <h3 class="font-display text-xl font-bold">Ingredientes Nobles</h3>
                    <p class="text-gray-500 font-light leading-relaxed max-w-xs mx-auto">
                        Seleccionamos materias primas de Grasse y rincones exóticos del mundo.
                    </p>
                </div>
                <!-- Value 3 -->
                <div class="space-y-4">
                    <div class="w-16 h-16 mx-auto bg-gray-200 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-6">
                        <span class="material-icons-outlined text-2xl">all_inclusive</span>
                    </div>
                    <h3 class="font-display text-xl font-bold">Sostenibilidad</h3>
                    <p class="text-gray-500 font-light leading-relaxed max-w-xs mx-auto">
                        Empaques reciclables y procesos respetuosos con el medio ambiente.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-32 text-center px-6">
        <h2 class="text-4xl font-display font-medium mb-8">Descubre tu Sello Personal</h2>
        <a href="<?php echo get_permalink( wc_get_page_id( 'shop' ) ); ?>" class="inline-block border-b border-black dark:border-white pb-1 text-lg uppercase tracking-widest hover:text-gray-600 transition-colors">
            Ir a la Tienda
        </a>
    </section>

</main>

<?php get_footer(); ?>
