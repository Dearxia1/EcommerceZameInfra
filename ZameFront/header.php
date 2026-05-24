<!DOCTYPE html>
<html <?php language_attributes(); ?> class="dark scroll-smooth">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <script>
        // Default to dark mode for a Hyper-Luxury aesthetic
        if (localStorage.theme === 'light') {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
        } else {
            document.documentElement.classList.add('dark');
        }
        
        function toggleDarkMode() {
             if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        }
    </script>
    <style>
        /* Sticky Header Fix for WP Admin Bar */
        body.admin-bar nav.sticky {
            top: 32px !important;
        }
        @media screen and (max-width: 782px) {
            body.admin-bar nav.sticky {
                top: 0px !important;
            }
        }
    </style>
</head>
<body <?php body_class('bg-background-light dark:bg-background-dark font-sans text-gray-800 dark:text-gray-200 transition-colors duration-300 flex flex-col min-h-screen'); ?>>
    
    <!-- Announcement Bar -->
    <div class="bg-black text-white py-2.5 overflow-hidden relative z-[60]">
        <div class="max-w-7xl mx-auto px-4 relative h-5 flex items-center justify-center">
             
             <!-- Centered Slider Container -->
             <div class="w-full h-full relative" id="announcement-slider">
                <span class="absolute inset-0 w-full h-full flex items-center justify-center transition-opacity duration-700 opacity-100 text-[10px] md:text-xs uppercase tracking-[0.2em] font-medium" data-index="0">Envíos Gratis a todo Colombia</span>
                <span class="absolute inset-0 w-full h-full flex items-center justify-center transition-opacity duration-700 opacity-0 text-[10px] md:text-xs uppercase tracking-[0.2em] font-medium" data-index="1">10% OFF en tu primera compra: WELCOME10</span>
                <span class="absolute inset-0 w-full h-full flex items-center justify-center transition-opacity duration-700 opacity-0 text-[10px] md:text-xs uppercase tracking-[0.2em] font-medium" data-index="2">Nuevas Fragancias Disponibles</span>
            </div>

            <!-- Absolute Right Links (Does not affect center text) -->
            <div class="hidden md:flex gap-4 absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-[10px] uppercase tracking-widest font-medium z-10">
                <a href="/contact" class="hover:text-white transition-colors">Ayuda</a>
                <a href="/my-account/orders" class="hover:text-white transition-colors">Rastrear</a>
            </div>
        </div>
        <script>
            (function() {
                const slides = document.querySelectorAll('#announcement-slider span');
                let currentIndex = 0;
                if (slides.length > 0) {
                    setInterval(() => {
                        slides[currentIndex].classList.remove('opacity-100');
                        slides[currentIndex].classList.add('opacity-0');
                        currentIndex = (currentIndex + 1) % slides.length;
                        slides[currentIndex].classList.remove('opacity-0');
                        slides[currentIndex].classList.add('opacity-100');
                    }, 4000); // Rotate every 4 seconds
                }
            })();
        </script>
    </div>

    <nav class="py-4 px-8 flex justify-between items-center border-b border-gray-100 dark:border-gray-800 sticky top-0 bg-white/90 dark:bg-background-dark/95 backdrop-blur z-50 transition-colors duration-300">
        <div class="logo">
            <a href="<?php echo home_url(); ?>" class="text-primary hover:opacity-90 transition-opacity">
                <?php 
                $logo_path = get_template_directory() . '/assets/images/logo-zame.svg';
                if ( !file_exists( $logo_path ) ) {
                    $logo_path = get_template_directory() . '/assets/logo-zame.svg';
                }
                
                if ( file_exists( $logo_path ) ) {
                    $svg_content = file_get_contents( $logo_path );
                    // Ensure currentColor for theme control and fixed height
                    // Remove any existing fill attributes to allow Tailwind to control it, or set to currentColor
                    $svg_content = preg_replace('/fill="[^"]+"/', 'fill="currentColor"', $svg_content);
                    echo str_replace('<svg', '<svg class="h-10 w-auto text-black dark:text-white transition-colors duration-300"', $svg_content);
                } else {
                    echo '<span class="text-2xl font-display font-bold tracking-widest">ZAME SCENT</span>';
                }
                ?>
            </a>
        </div>

        <?php if ( ! is_checkout() ) : ?>
        <div class="hidden md:flex space-x-8 text-sm uppercase tracking-widest font-medium list-none">
             <?php 
                if ( has_nav_menu( 'primary' ) ) {
                    wp_nav_menu( array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'menu_class'     => 'flex space-x-8',
                        'items_wrap'     => '%3$s', // Remove ul wrapper
                        'fallback_cb'    => false,
                        'link_before'    => '',
                        'link_after'     => '',
                        // Custom walker could be needed for strict class control, but css classes in menu admin are easier
                    ) );
                } else {
                    ?>
                    <a href="<?php echo home_url(); ?>" class="hover:text-primary transition">Inicio</a>
                    <a href="/tienda" class="hover:text-primary transition">Fragancias</a>
                    <a href="/about" class="hover:text-primary transition">Nosotros</a>
                    <a href="/contact" class="hover:text-primary transition">Contacto</a>
                    <?php
                }
            ?>
        </div>
        <?php endif; ?>

        <div class="flex items-center space-x-4">
            <?php if ( ! is_checkout() ) : ?>
            <div class="relative">
                <span id="search-trigger" class="material-icons-outlined cursor-pointer hover:text-primary transition">search</span>
                
                <!-- Search Dropdown (Hybrid: Fixed Mobile / Absolute Desktop Side) -->
                <!-- Mobile: Fixed to viewport, top-90px (adjusted for slimmer py-4 header), w-full -->
                <!-- Desktop: Absolute (relative to icon), right-full (left of icon), w-64 -->
                <div id="search-dropdown" class="fixed left-0 right-0 top-[80px] w-full bg-white dark:bg-zinc-900 border-b border-gray-100 dark:border-zinc-800 shadow-xl transform origin-top md:origin-right scale-y-0 md:scale-x-0 md:scale-y-100 transition-transform duration-300 z-40 ease-out overflow-hidden md:absolute md:top-1/2 md:-translate-y-1/2 md:right-full md:mr-3 md:left-auto md:w-64 md:rounded-md md:border md:shadow-lg md:h-auto md:bg-white md:dark:bg-zinc-900">
                    <div class="max-w-7xl mx-auto px-4 py-6 md:p-1 md:w-full">
                        <div class="flex justify-between items-start mb-4 md:hidden">
                             <span class="text-xs uppercase tracking-widest text-gray-400 font-bold">Buscar</span>
                             <button id="search-close-btn" class="text-gray-400 hover:text-primary transition focus:outline-none">
                                <span class="material-icons-outlined text-xl">close</span>
                            </button>
                        </div>
                        
                        <!-- FiboSearch / Fallback Form -->
                        <div class="zame-search-dropdown-wrapper w-full relative">
                             <?php 
                             if ( shortcode_exists( 'wcas-search-form' ) ) {
                                 echo do_shortcode('[wcas-search-form]'); 
                             } else {
                                 get_search_form();
                             }
                             ?>
                        </div>
                    </div>
                </div>
            </div>
            <a href="javascript:void(0)" id="cart-trigger" class="relative group">
                <span class="material-icons-outlined cursor-pointer hover:text-primary transition">shopping_bag</span>
                <span class="cart-count absolute -top-1 -right-1 bg-primary text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center <?php echo (WC()->cart->get_cart_contents_count() > 0) ? '' : 'hidden'; ?>">
                    <?php echo WC()->cart->get_cart_contents_count(); ?>
                </span>
            </a>
            <?php endif; ?>

            <button class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition focus:outline-none" onclick="toggleDarkMode()">
                <span class="material-icons-outlined text-sm">dark_mode</span>
            </button>
            
            <?php if ( ! is_checkout() ) : ?>
            <button id="mobile-menu-trigger" class="md:hidden text-gray-800 dark:text-white focus:outline-none ml-2">
                <span class="material-icons-outlined text-2xl">menu</span>
            </button>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Side Cart Drawer -->
    <div id="side-cart-drawer" class="fixed inset-0 z-[100] transition-all duration-300" style="display: none;">
        <!-- Backdrop -->
        <div id="cart-overlay" class="absolute inset-0 bg-black/50 backdrop-blur-sm opacity-0 transition-opacity duration-300 pointer-events-none"></div>
        
        <!-- Drawer Panel -->
        <div id="cart-panel" class="absolute top-0 right-0 w-full max-w-md h-full bg-white dark:bg-background-dark shadow-2xl flex flex-col transform translate-x-full transition-transform duration-300 ease-in-out">
            <div class="p-6 flex justify-between items-center border-b border-gray-100 dark:border-gray-800">
                <span class="font-display font-bold tracking-widest text-primary dark:text-white">CARRITO DE COMPRAS</span>
                <button id="cart-close" class="text-gray-400 hover:text-primary transition">
                    <span class="material-icons-outlined text-2xl">close</span>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto py-8 px-6 zame-mini-cart-container">
                <?php woocommerce_mini_cart(); ?>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Drawer (Slide-out) -->
    <div id="mobile-menu-drawer" class="fixed inset-0 z-[100] md:hidden transition-all duration-300" style="display: none;">
        <!-- Backdrop -->
        <div id="mobile-menu-overlay" class="absolute inset-0 bg-black/50 backdrop-blur-sm opacity-0 transition-opacity duration-300 pointer-events-none"></div>
        
        <!-- Drawer Panel -->
        <div id="mobile-menu-panel" class="absolute top-0 right-0 w-4/5 max-w-sm h-full bg-white dark:bg-background-dark shadow-2xl flex flex-col transform translate-x-full transition-transform duration-300 ease-in-out">
            <div class="p-6 flex justify-between items-center border-b border-gray-100 dark:border-gray-800">
                <span class="font-display font-bold tracking-widest text-primary dark:text-white">MENÚ</span>
                <button id="mobile-menu-close" class="text-gray-400 hover:text-primary transition">
                    <span class="material-icons-outlined text-2xl">close</span>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto py-8 px-6">
                <div class="flex flex-col space-y-6 text-lg uppercase tracking-widest font-medium list-none">
                     <?php 
                        if ( has_nav_menu( 'primary' ) ) {
                            wp_nav_menu( array(
                                'theme_location' => 'primary',
                                'container'      => false,
                                'menu_class'     => 'flex flex-col space-y-6',
                                'items_wrap'     => '%3$s',
                                'fallback_cb'    => false,
                            ) );
                        } else {
                            ?>
                            <a href="<?php echo home_url(); ?>" class="hover:text-primary transition">Inicio</a>
                            <a href="/tienda" class="hover:text-primary transition">Fragancias</a>
                            <a href="/about" class="hover:text-primary transition">Nosotros</a>
                            <a href="/contact" class="hover:text-primary transition">Contacto</a>
                            <?php
                        }
                    ?>
                </div>
            </div>
            
            <div class="p-8 border-t border-gray-100 dark:border-gray-800 space-y-6">
                 <div class="flex justify-center space-x-6">
                    <a href="#" class="text-gray-400 hover:text-primary transition"><span class="material-icons-outlined">instagram</span></a>
                    <a href="#" class="text-gray-400 hover:text-primary transition"><span class="material-icons-outlined">facebook</span></a>
                 </div>
                 <p class="text-center text-[10px] text-gray-400 uppercase tracking-widest">&copy; <?php echo date('Y'); ?> ZAME SCENT</p>
            </div>
        </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile Menu Logic
        const menuTrigger = document.getElementById('mobile-menu-trigger');
        const menuClose = document.getElementById('mobile-menu-close');
        const menuDrawer = document.getElementById('mobile-menu-drawer');
        const menuPanel = document.getElementById('mobile-menu-panel');
        const menuOverlay = document.getElementById('mobile-menu-overlay');
        const body = document.body;

        function openMenu() {
            menuDrawer.style.display = 'block';
            // Use a small timeout to ensure display: block is processed before opacity/translate transitions starts
            setTimeout(() => {
                menuOverlay.classList.remove('opacity-0');
                menuOverlay.classList.add('opacity-100');
                menuOverlay.classList.add('pointer-events-all');
                menuPanel.classList.remove('translate-x-full');
            }, 10);
            body.style.overflow = 'hidden';
        }

        function closeMenu() {
            menuPanel.classList.add('translate-x-full');
            menuOverlay.classList.remove('opacity-100');
            menuOverlay.classList.add('opacity-0');
            menuOverlay.classList.remove('pointer-events-all');
            
            setTimeout(() => {
                menuDrawer.style.display = 'none';
            }, 300);
            body.style.overflow = '';
        }

        if(menuTrigger) menuTrigger.addEventListener('click', openMenu);
        if(menuClose) menuClose.addEventListener('click', closeMenu);
        if(menuOverlay) menuOverlay.addEventListener('click', closeMenu);

        // Side Cart Logic
        const cartTrigger = document.getElementById('cart-trigger');
        const cartClose = document.getElementById('cart-close');
        const cartDrawer = document.getElementById('side-cart-drawer');
        const cartPanel = document.getElementById('cart-panel');
        const cartOverlay = document.getElementById('cart-overlay');

        function openCart() {
            cartDrawer.style.display = 'block';
            setTimeout(() => {
                cartOverlay.classList.remove('opacity-0');
                cartOverlay.classList.add('opacity-100');
                cartOverlay.classList.add('pointer-events-all');
                cartPanel.classList.remove('translate-x-full');
            }, 10);
            body.style.overflow = 'hidden';
        }

        function closeCart() {
            cartPanel.classList.add('translate-x-full');
            cartOverlay.classList.remove('opacity-100');
            cartOverlay.classList.add('opacity-0');
            cartOverlay.classList.remove('pointer-events-all');
            
            setTimeout(() => {
                cartDrawer.style.display = 'none';
            }, 300);
            body.style.overflow = '';
        }

        if(cartTrigger) cartTrigger.addEventListener('click', openCart);
        if(cartClose) cartClose.addEventListener('click', closeCart);
        if(cartOverlay) cartOverlay.addEventListener('click', closeCart);

        // Auto-open on added to cart event (jQuery needed for WC events)
        if (typeof jQuery !== 'undefined') {
            jQuery(document.body).on('added_to_cart', function() {
                openCart();
            });
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchTrigger = document.getElementById('search-trigger');
        const searchDropdown = document.getElementById('search-dropdown');
        const searchCloseBtn = document.getElementById('search-close-btn');
        let isOpen = false;

        function toggleSearch() {
            if (isOpen) {
                closeSearch();
            } else {
                openSearch();
            }
        }

        function openSearch() {
            // Mobile
            searchDropdown.classList.remove('scale-y-0');
            // Desktop
            searchDropdown.classList.remove('md:scale-x-0');
            
            searchDropdown.classList.add('scale-y-100');
            // searchDropdown.classList.add('md:scale-x-100'); // Implicit if 0 removed, but let's be safe if default is 100
            
            isOpen = true;
            
            // Focus input
            const searchInput = searchDropdown.querySelector('input[type="search"]');
            if(searchInput) setTimeout(() => searchInput.focus(), 100);
        }

        function closeSearch() {
            searchDropdown.classList.remove('scale-y-100');
            searchDropdown.classList.add('scale-y-0');
            
            // Desktop
            searchDropdown.classList.add('md:scale-x-0');
            
            isOpen = false;
        }

        if(searchTrigger) {
            searchTrigger.addEventListener('click', function(e) {
                e.preventDefault();
                toggleSearch();
            });
        }

        if(searchCloseBtn) {
            searchCloseBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeSearch();
            });
        }

        // Close on ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isOpen) {
                closeSearch();
            }
        });

        // Close on click outside (if needed, though slide down usually implies part of header)
        document.addEventListener('click', function(e) {
            if (isOpen && !searchDropdown.contains(e.target) && !searchTrigger.contains(e.target)) {
                closeSearch();
            }
        });
    });
</script>
