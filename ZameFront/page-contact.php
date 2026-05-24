<?php
/**
 * Template Name: Contact Us
 * Description: A premium "Contact Us" page layout with split visual design.
 */

get_header(); ?>

<main class="bg-white dark:bg-black text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">

    <!-- Hero Section -->
    <section class="relative h-[50vh] flex items-center justify-center overflow-hidden mb-24">
        <div class="absolute inset-0 bg-black">
            <!-- Hero Image -->
            <img src="https://images.unsplash.com/photo-1616401784845-180882ba9ba8?q=80&w=2070&auto=format&fit=crop" alt="Contact Background" class="w-full h-full object-cover opacity-60">
        </div>
        <div class="relative z-10 text-center px-4 max-w-4xl mx-auto text-white">
            <h1 class="text-5xl md:text-7xl font-display font-medium mb-6">Contáctanos</h1>
            <p class="text-lg md:text-xl font-light tracking-wide opacity-90 max-w-2xl mx-auto">
                Estamos aquí para guiarte en tu viaje olfativo. Escríbenos o visítanos.
            </p>
        </div>
    </section>

    <!-- Content Split -->
    <section class="flex-grow px-6 pb-32">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-20 lg:gap-32 items-center">
            
            <!-- Left Column: Info & Map -->
            <div class="space-y-16 mt-4">
                <!-- Info Block -->
                <div class="space-y-8">
                    <h3 class="font-display text-3xl font-bold">Atelier ZAME</h3>
                    <div class="space-y-4 text-gray-600 dark:text-gray-400 font-light text-lg">
                        <p class="flex items-start gap-4">
                            <span class="material-icons-outlined mt-1">location_on</span>
                            <span>Calle 85 # 12-34<br>Bogotá, Colombia</span>
                        </p>
                        <p class="flex items-center gap-4">
                            <span class="material-icons-outlined">email</span>
                            <span>contacto@zamescents.com</span>
                        </p>
                        <p class="flex items-center gap-4">
                            <span class="material-icons-outlined">phone</span>
                            <span>+57 300 123 4567</span>
                        </p>
                    </div>
                </div>

                <!-- Hours -->
                <div class="space-y-6">
                    <h3 class="font-display text-2xl font-bold">Horario de Atención</h3>
                    <ul class="text-gray-600 dark:text-gray-400 font-light space-y-3 text-lg">
                        <li class="flex justify-between max-w-sm border-b border-gray-100 dark:border-white/5 pb-2"><span>Lunes - Viernes</span> <span>10:00 - 19:00</span></li>
                        <li class="flex justify-between max-w-sm border-b border-gray-100 dark:border-white/5 pb-2"><span>Sábados</span> <span>11:00 - 17:00</span></li>
                        <li class="flex justify-between max-w-sm pb-2"><span>Domingos</span> <span class="text-gray-400">Cerrado</span></li>
                    </ul>
                </div>

                <!-- Abstract Map Placeholder (Grayscale image of map) -->
                <div class="w-full h-80 bg-gray-200 dark:bg-zinc-800 rounded-xl overflow-hidden relative grayscale opacity-90 hover:grayscale-0 transition-all duration-500 shadow-xl">
                    <img src="https://images.unsplash.com/photo-1524661135-423995f22d0b?q=80&w=1748&auto=format&fit=crop" alt="Map Placeholder" class="w-full h-full object-cover">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <a href="https://maps.google.com" target="_blank" class="bg-white dark:bg-black px-8 py-3 rounded-full text-xs font-bold uppercase tracking-widest shadow-lg hover:scale-105 transition-transform">
                            Ver Mapa
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Column: Contact Form (Mockup) -->
            <div class="bg-gray-50 dark:bg-zinc-900/50 p-10 md:p-16 rounded-3xl shadow-sm">
                <form action="#" method="POST" class="space-y-10">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <div class="group">
                            <label class="block text-xs uppercase tracking-widest text-gray-400 mb-3 transition-colors group-focus-within:text-black dark:group-focus-within:text-white">Nombre</label>
                            <input type="text" class="w-full bg-transparent border-b border-gray-200 dark:border-white/10 py-3 focus:outline-none focus:border-black dark:focus:border-white transition-colors placeholder-transparent text-lg" placeholder="Tu Nombre">
                        </div>
                        <div class="group">
                            <label class="block text-xs uppercase tracking-widest text-gray-400 mb-3 transition-colors group-focus-within:text-black dark:group-focus-within:text-white">Email</label>
                            <input type="email" class="w-full bg-transparent border-b border-gray-200 dark:border-white/10 py-3 focus:outline-none focus:border-black dark:focus:border-white transition-colors placeholder-transparent text-lg" placeholder="tucorreo@ejemplo.com">
                        </div>
                    </div>
                    
                    <div class="group">
                        <label class="block text-xs uppercase tracking-widest text-gray-400 mb-3 transition-colors group-focus-within:text-black dark:group-focus-within:text-white">Asunto</label>
                        <select class="w-full bg-transparent border-b border-gray-200 dark:border-white/10 py-3 focus:outline-none focus:border-black dark:focus:border-white transition-colors text-gray-700 dark:text-gray-300 text-lg">
                            <option>Consulta General</option>
                            <option>Pedido / Envíos</option>
                            <option>Prensa</option>
                        </select>
                    </div>

                    <div class="group">
                        <label class="block text-xs uppercase tracking-widest text-gray-400 mb-3 transition-colors group-focus-within:text-black dark:group-focus-within:text-white">Mensaje</label>
                        <textarea rows="5" class="w-full bg-transparent border-b border-gray-200 dark:border-white/10 py-3 focus:outline-none focus:border-black dark:focus:border-white transition-colors resize-none text-lg"></textarea>
                    </div>

                    <div class="pt-6">
                        <button type="submit" class="w-full bg-black dark:bg-white text-white dark:text-black py-5 px-8 rounded-xl uppercase tracking-widest text-sm font-bold hover:opacity-90 hover:shadow-lg transition-all transform hover:-translate-y-1">
                            Enviar Mensaje
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </section>

</main>

<?php get_footer(); ?>
