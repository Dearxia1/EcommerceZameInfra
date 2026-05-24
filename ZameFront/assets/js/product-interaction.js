/**
 * Product Interaction Controller
 * Syncs custom UI (buttons) with hidden WooCommerce native form.
 */
console.log('ZAME: Script Loaded v3 (Global Scan Active)');
document.addEventListener('DOMContentLoaded', function () {
    initProductConfigurator();
    initVisualKitBuilder();
});

function initProductConfigurator() {
    // Attempt to find the specific variable product form first, then fallback to generic cart form
    const form = document.querySelector('.variations_form') || document.querySelector('form.cart');

    if (!form) {
        console.error('ZAME: Critical - Product form (.variations_form or form.cart) not found. Configurator disabled.');
        return;
    }

    console.log('ZAME: Product Configurator Initialized on:', form);

    // Check for Auto-Open Cart Flag (after reload)
    const shouldOpenCart = sessionStorage.getItem('zame_should_open_cart');
    if (shouldOpenCart === 'true') {
        sessionStorage.removeItem('zame_should_open_cart');

        console.log('ZAME: Auto-open flag detected. Forcing fragment refresh for cache bypass...');

        // CRITICAL: Force update fragments to bypass cached HTML state
        // This ensures the mini-cart has the latest items even if the page load was cached
        if (typeof refreshCartFragments === 'function') {
            refreshCartFragments();
        } else {
            // Fallback if function undefined (should not happen if loaded correctly)
            jQuery(document.body).trigger('wc_fragment_refresh');
        }

        // Small delay to ensure UI is ready and drawer opens cleanly
        setTimeout(() => {
            const cartTrigger = document.getElementById('cart-trigger');
            if (cartTrigger) {
                console.log('ZAME: Auto-opening cart UI...');
                cartTrigger.click();
            }
        }, 800);
    }

    // Message Bridge (Convert Native WC Messages to Toasts)
    const nativeMessages = document.querySelectorAll('.woocommerce-message, .woocommerce-error, .woocommerce-info');
    if (nativeMessages.length > 0) {
        nativeMessages.forEach(msg => {
            const clone = msg.cloneNode(true);
            const btn = clone.querySelector('.button');
            if (btn) btn.remove();

            const text = clone.innerText.trim();
            if (text) {
                let type = 'success';
                if (msg.classList.contains('woocommerce-error')) type = 'error';
                showToast(text, type);
            }
        });
    }

    // State
    let currentVariation = null;
    let currentQty = 1;

    // 1. Bind Custom Attribute Buttons
    const customSwatches = document.querySelectorAll('.custom-attribute-swatch');
    customSwatches.forEach(swatch => {
        swatch.addEventListener('click', function (e) {
            e.preventDefault();

            const attributeName = this.dataset.attribute;
            const value = this.dataset.value;

            const siblings = this.closest('.custom-attribute-group').querySelectorAll('.custom-attribute-swatch');
            siblings.forEach(sib => sib.classList.remove('active', 'border-primary', 'text-primary'));

            this.classList.add('active', 'border-primary', 'text-primary');

            const nativeSelect = form.querySelector(`select[name="${attributeName}"]`);
            if (nativeSelect) {
                nativeSelect.value = value;
                const event = new Event('change', { bubbles: true });
                nativeSelect.dispatchEvent(event);
            }
        });
    });

    // 2. Listen for WooCommerce Variation Events
    jQuery(form).on('found_variation', function (event, variation) {
        console.log('ZAME: Variation Found', variation);
        currentVariation = variation;
        updateCustomPrice();
        updateCustomAddToCart(true, variation);
    });

    jQuery(form).on('reset_data', function () {
        console.log('ZAME: Data Reset');
        currentVariation = null;
        updateCustomPrice();
        updateCustomAddToCart(false, null);
    });

    // 3. Custom Price Updater
    function updateCustomPrice() {
        const priceContainer = document.getElementById('custom-price-container');
        if (!priceContainer) return;

        if (currentVariation) {
            const unitPrice = currentVariation.display_price;
            const total = unitPrice * currentQty;

            const formatter = new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });

            if (currentQty > 1) {
                const parts = formatter.formatToParts(total);
                let priceHTML = '<span class="woocommerce-Price-amount amount"><bdi>';
                parts.forEach(part => {
                    if (part.type === 'currency') {
                        priceHTML += `<span class="woocommerce-Price-currencySymbol">${part.value}</span>`;
                    } else {
                        priceHTML += part.value;
                    }
                });
                priceHTML += '</bdi></span>';
                priceContainer.innerHTML = `<span class="price">${priceHTML}</span>`;
            } else {
                priceContainer.innerHTML = currentVariation.price_html;
            }
        } else {
            const originalPrice = priceContainer.getAttribute('data-original-price');
            if (originalPrice) {
                priceContainer.innerHTML = originalPrice;
            }
        }
    }

    // 4. Custom Add to Cart Button Logic
    const customAddBtn = document.getElementById('custom-add-to-cart-btn');
    if (customAddBtn) {
        customAddBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (this.classList.contains('disabled')) {
                // If it's just general validation (not kit)
                if (!document.getElementById('zame-wapf-container')) {
                    showToast('Por favor selecciona las opciones.', 'error');
                    return false;
                }
            }

            // KIT VALIDATION (Elegante)
            const kitContainer = document.getElementById('zame-wapf-container');
            if (kitContainer) {
                const selects = kitContainer.querySelectorAll('select.wapf-input');
                const empty = Array.from(selects).filter(s => !s.value);
                if (empty.length > 0) {
                    const missing = empty.length;
                    showToast(`Faltan ${missing} fragancia${missing > 1 ? 's' : ''} para completar tu kit.`, 'warning');

                    // Visual cue on empty slots
                    const emptySlots = document.querySelectorAll('.kit-slot:not(.filled)');
                    emptySlots.forEach(slot => {
                        slot.classList.add('ring-2', 'ring-red-300', 'dark:ring-red-900', 'scale-105');
                        setTimeout(() => slot.classList.remove('ring-2', 'ring-red-300', 'dark:ring-red-900', 'scale-105'), 600);
                    });

                    return false;
                }
            }

            handleAjaxAddToCart(form, this);
            return false;
        });
    }

    // Handle Native Form Submission (Prevent traditional reload)
    form.addEventListener('submit', function (e) {
        if (customAddBtn) {
            e.preventDefault();
            e.stopPropagation();
            handleAjaxAddToCart(form, customAddBtn);
            return false;
        }
    });

    async function handleAjaxAddToCart(form, btn) {
        if (btn.disabled) return;

        console.log('ZAME: Starting AJAX Add to Cart Process');

        const originalText = btn.textContent;
        btn.classList.add('loading', 'opacity-75', 'cursor-wait');
        btn.innerHTML = '<span class="material-icons-outlined animate-spin text-sm mr-2">refresh</span> Añadiendo...';
        btn.disabled = true;

        try {
            // 1. FORCED NONCE SYNC: Find every security token on the page and inject it into our form
            const securityFields = [
                '_wpnonce',
                'woocommerce-add-to-cart-nonce',
                'woocommerce-process-checkout-nonce',
                'security'
            ];

            securityFields.forEach(name => {
                const existingInForm = form.querySelector(`input[name="${name}"]`);
                const globallyFound = document.querySelector(`input[name="${name}"]`);

                if (globallyFound && !existingInForm) {
                    console.log(`ZAME: Found security token [${name}] globally. Injecting into form.`);
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = name;
                    hiddenInput.value = globallyFound.value;
                    form.appendChild(hiddenInput);
                }
            });

            // 2. Build Clean Payload Object (Surgical Approach)
            let payload = {};

            // 2a. IDs
            const parentId = form.querySelector('input[name="product_id"]')?.value ||
                document.querySelector('[id^="product-"]')?.id?.replace('product-', '');
            const variationId = form.querySelector('input[name="variation_id"]')?.value;

            console.log('ZAME: Detected IDs - Parent:', parentId, 'Variation:', variationId);

            payload['product_id'] = parentId;
            payload['add-to-cart'] = parentId; // WooCommerce needs this to trigger the cart handler

            if (variationId && variationId !== '0' && variationId !== '') {
                payload['variation_id'] = variationId;

                // ROBUST ATTRIBUTE CAPTURE: Use keys from currentVariation.attributes
                // Get actual VALUES from the native WooCommerce selects (they contain slugs, not text)
                if (currentVariation && currentVariation.attributes) {
                    console.log('ZAME: Using variation.attributes for keys:', currentVariation.attributes);
                    for (const attrKey in currentVariation.attributes) {
                        // Use jQuery to find the select in the variations form - it contains the slug as value
                        const $select = jQuery(form).find(`select[name="${attrKey}"]`);
                        if ($select.length && $select.val()) {
                            const realValue = $select.val();
                            payload[attrKey] = realValue;
                            console.log(`ZAME: Attribute ${attrKey} = ${realValue} (from select.val())`);
                        } else {
                            // Fallback: use the value from variation object
                            payload[attrKey] = currentVariation.attributes[attrKey];
                            console.log(`ZAME: Attribute ${attrKey} = ${currentVariation.attributes[attrKey]} (from variation object)`);
                        }
                    }
                } else {
                    // Fallback to old method if currentVariation not available
                    form.querySelectorAll('select[name^="attribute_"]').forEach(sel => {
                        if (sel.value) payload[sel.name] = sel.value;
                    });
                }
            }

            // 2b. Quantity
            payload['quantity'] = document.getElementById('custom-qty-input')?.value || 1;

            // 2c. WAPF Fields
            document.querySelectorAll('input[name^="wapf"], select[name^="wapf"], textarea[name^="wapf"]').forEach(el => {
                if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
                payload[el.name] = el.value;
            });

            // 2d. Security Nonce (from PHP localized script or form injection)
            if (typeof zame_cart_params !== 'undefined' && zame_cart_params.cart_nonce) {
                payload['security'] = zame_cart_params.cart_nonce;
                console.log('ZAME: Nonce added from zame_cart_params');
            } else {
                // Fallback: try to find any nonce in the form
                securityFields.forEach(name => {
                    const input = form.querySelector(`input[name="${name}"]`);
                    if (input && input.value) payload[name] = input.value;
                });
            }

            // 3. TRUE AJAX APPROACH: Use native form's FormData (WooCommerce already set it up correctly)
            // 3. TRUE AJAX APPROACH with FRAGMENTS
            // Sync quantity to native form first
            const nativeQtyInput = form.querySelector('input[name="quantity"]');

            // Get FormData directly from the native WooCommerce form
            const nativeFormData = new FormData(form);

            // IMPORTANT: 'add-to-cart' param is usually button value, missing in FormData(form)
            // We must append it manually for WC to process usage correctly
            if (!nativeFormData.has('add-to-cart')) {
                // Try to find the ID to add:
                // 1. Variation ID (if variable product)
                // 2. Parent ID (if simple product)
                // 3. Form input named 'add-to-cart'
                // 4. Submit button value
                let idToAdd = variationId && variationId !== '0' ? variationId : parentId;

                if (!idToAdd) {
                    const btnInject = form.querySelector('[name="add-to-cart"]');
                    if (btnInject) idToAdd = btnInject.value;
                }

                if (idToAdd) {
                    nativeFormData.append('add-to-cart', idToAdd);
                    console.log(`ZAME: Appended add-to-cart ID: ${idToAdd}`);
                } else {
                    console.warn('ZAME: Could not determine product ID for add-to-cart param');
                }
            }

            // Log what we're sending
            console.log('ZAME: Native Form Payload (to WC AJAX):');
            for (let [key, value] of nativeFormData.entries()) {
                console.log(`  > ${key}: ${value}`);
            }

            // Construct Endpoint URL: Use Form Action for native compatibility
            const formAction = form.getAttribute('action') || window.location.href;
            console.log(`ZAME: Sending AJAX to Form Action: ${formAction}`);

            jQuery.ajax({
                url: formAction,
                data: nativeFormData,
                type: 'POST',
                processData: false,
                contentType: false,
                success: function (response) {
                    console.log('ZAME: AJAX Success. Reloading page...');
                    // Set flag to open cart after reload
                    sessionStorage.setItem('zame_should_open_cart', 'true');

                    // Force Cache Busting Reload
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('added-to-cart', Date.now()); // Unique param
                    window.location.href = currentUrl.toString();
                },
                error: function (xhr, status, error) {
                    console.log('ZAME: AJAX Request finished (error or not). Reloading...');
                    // Even on error, we reload because often the server accepted the request but returned non-JSON
                    // If it was a real error, the user will see it on the reloaded page or the cart won't contain the item.
                    sessionStorage.setItem('zame_should_open_cart', 'true');

                    // Force Cache Busting Reload
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('after-error', Date.now());
                    window.location.href = currentUrl.toString();
                }
            });

            return; // Exit after AJAX call

            jQuery.ajax({
                url: '/?wc-ajax=add_to_cart',
                data: formData,
                type: 'POST',
                processData: false,
                contentType: false,
                dataType: 'text',
                success: function (responseText) {
                    console.log('ZAME: AJAX Response Received');

                    if (!responseText || responseText.trim() === '') {
                        console.error('ZAME: Empty Response');
                        showToast('El servidor no respondió. Intenta de nuevo.', 'error');
                        resetButton(btn, originalText);
                        return;
                    }

                    let data;
                    try {
                        const firstBrace = responseText.indexOf('{');
                        const lastBrace = responseText.lastIndexOf('}');
                        const cleanJSON = (firstBrace !== -1 && lastBrace !== -1) ? responseText.substring(firstBrace, lastBrace + 1) : responseText;
                        data = JSON.parse(cleanJSON);
                    } catch (e) {
                        console.error('ZAME: JSON Parse Error', e, responseText);
                        showToast('Error al procesar la respuesta del servidor.', 'error');
                        resetButton(btn, originalText);
                        return;
                    }

                    if (data && !data.error) {
                        if (data.fragments) {
                            jQuery.each(data.fragments, function (key, value) {
                                jQuery(key).replaceWith(value);
                            });
                            jQuery(document.body).trigger('wc_fragments_refreshed');
                        }

                        jQuery(document.body).trigger('added_to_cart', [data.fragments, data.cart_hash, jQuery(btn)]);

                        btn.classList.remove('loading', 'opacity-75', 'cursor-wait');
                        btn.classList.add('bg-green-600', 'text-white', 'border-green-600');
                        btn.innerHTML = '<span class="material-icons-outlined text-sm mr-2">check</span> Añadido';
                        showToast('Producto añadido al carrito', 'success');

                        // Open side cart automatically
                        setTimeout(() => {
                            jQuery(document.body).trigger('wc_fragment_refresh');
                            document.body.click(); // Hacky close of search if open
                            const cartTrigger = document.querySelector('.cart-trigger') || document.querySelector('.relative.group a[href*="cart"]');
                            if (cartTrigger) cartTrigger.click();
                        }, 500);

                        setTimeout(() => {
                            btn.classList.remove('bg-green-600', 'text-white', 'border-green-600');
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }, 3000);
                    } else {
                        console.warn('ZAME: WC rejected add to cart', data);
                        btn.classList.remove('loading');
                        btn.classList.add('bg-red-600', 'text-white');
                        btn.innerHTML = 'Error';

                        let msg = 'No se pudo añadir. Revisa las opciones.';

                        // EXTRACT REAL ERROR FROM FRAGMENTS
                        if (data && data.fragments) {
                            const notices = data.fragments['.woocommerce-error'] ||
                                data.fragments['.woocommerce-notices-wrapper'] ||
                                data.fragments['notices_html'];

                            if (notices) {
                                const temp = document.createElement('div');
                                temp.innerHTML = notices;
                                const errorText = temp.querySelector('.woocommerce-error li')?.innerText ||
                                    temp.querySelector('.woocommerce-error')?.innerText ||
                                    temp.innerText;
                                if (errorText) msg = errorText.trim();
                            }
                        } else if (data && data.notice) {
                            msg = data.notice;
                        }
                        console.log('ZAME: Extracted error message:', msg); // Added log for extracted error
                        showToast(msg, 'error');
                        setTimeout(() => resetButton(btn, originalText), 4000);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('ZAME: AJAX Request Failed', status, error);
                    showToast('Error de conexión con el servidor.', 'error');
                    resetButton(btn, originalText);
                }
            });

        } catch (err) {
            console.error('ZAME: Fatal UI Error:', err);
            showToast(err.message || 'Error técnico al añadir.', 'error');
            resetButton(btn, originalText);
        }
    }

    function resetButton(btn, originalText) {
        btn.classList.remove('loading', 'opacity-75', 'cursor-wait', 'bg-red-600', 'bg-green-600');
        btn.innerHTML = originalText;
        btn.disabled = false;
    }

    function updateCustomAddToCart(isValid, variation) {
        const btn = document.getElementById('custom-add-to-cart-btn');
        if (!btn) return;

        if (isValid && variation.is_purchasable && variation.is_in_stock) {
            btn.classList.remove('opacity-50', 'cursor-not-allowed', 'disabled');
            btn.textContent = 'Añadir al Carrito';
            btn.removeAttribute('disabled');
        } else {
            btn.classList.add('opacity-50', 'cursor-not-allowed', 'disabled');
            btn.textContent = 'Selecciona Opciones';
            btn.setAttribute('disabled', 'disabled');
        }
    }

    function refreshCartFragments() {
        // Force refresh WooCommerce cart fragments
        console.log('ZAME: Refreshing cart fragments...');

        // First, get fresh fragments from WooCommerce BEFORE triggering events
        if (typeof wc_cart_fragments_params !== 'undefined') {
            jQuery.ajax({
                url: wc_cart_fragments_params.wc_ajax_url.toString().replace('%%endpoint%%', 'get_refreshed_fragments'),
                type: 'POST',
                data: {
                    time: new Date().getTime()
                },
                success: function (data) {
                    if (data && data.fragments) {
                        // Update all fragment elements with fresh data
                        jQuery.each(data.fragments, function (key, value) {
                            jQuery(key).replaceWith(value);
                        });
                        console.log('ZAME: Cart fragments updated successfully');
                    }
                    // Store the cart hash
                    if (data && data.cart_hash) {
                        sessionStorage.setItem(wc_cart_fragments_params.fragment_name, JSON.stringify(data.fragments));
                        localStorage.setItem(wc_cart_fragments_params.cart_hash_key, data.cart_hash);
                    }

                    // NOW trigger the cart open event (after fragments are updated)
                    jQuery(document.body).trigger('added_to_cart', [data.fragments, data.cart_hash]);
                    jQuery(document.body).trigger('wc_fragment_refresh');
                }
            });
        } else {
            // Fallback if wc_cart_fragments_params not available
            jQuery(document.body).trigger('added_to_cart');
            jQuery(document.body).trigger('wc_fragment_refresh');
        }
    }

    function showToast(message, type = 'success') {
        let container = document.getElementById('zame-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'zame-toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `zame-toast ${type}`;
        const iconName = type === 'success' ? 'check' : 'error_outline';
        toast.innerHTML = `<div class="toast-icon"><span class="material-icons-outlined text-sm">${iconName}</span></div><span>${message}</span>`;
        container.appendChild(toast);

        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }

    // Quantity logic
    const qtyInput = document.getElementById('custom-qty-input');
    const minusBtn = document.getElementById('custom-qty-minus');
    const plusBtn = document.getElementById('custom-qty-plus');

    if (qtyInput && minusBtn && plusBtn) {
        const nativeQty = form.querySelector('input.qty');
        const updateQty = (delta) => {
            let newVal = (parseInt(qtyInput.value) || 1) + delta;
            if (newVal < 1) newVal = 1;
            if (nativeQty && nativeQty.max && newVal > parseInt(nativeQty.max)) newVal = parseInt(nativeQty.max);
            qtyInput.value = newVal;
            currentQty = newVal;
            updateCustomPrice();
            if (nativeQty) {
                nativeQty.value = newVal;
                nativeQty.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };
        minusBtn.addEventListener('click', (e) => { e.preventDefault(); updateQty(-1); });
        plusBtn.addEventListener('click', (e) => { e.preventDefault(); updateQty(1); });
        qtyInput.addEventListener('input', function () {
            let val = Math.min(parseInt(this.value) || 1, 50);
            this.value = val;
            currentQty = val;
            updateCustomPrice();
            if (nativeQty) nativeQty.value = val;
        });
    }
}

function initVisualKitBuilder() {
    const container = document.getElementById('zame-wapf-container');
    if (!container) return;

    const nativeSelects = container.querySelectorAll('select.wapf-input');
    if (nativeSelects.length === 0) return;

    console.log('ZAME: Visual Kit Builder Initialized with', nativeSelects.length, 'slots');
    container.style.display = 'none';

    const fragrances = [];
    Array.from(nativeSelects[0].options).forEach(opt => {
        if (opt.value) {
            fragrances.push({
                value: opt.value,
                label: opt.text,
                image: (window.ZAME_CATALOG || []).find(i => i.name.toLowerCase().includes(opt.text.toLowerCase()))?.image || ''
            });
        }
    });

    const builderUI = document.createElement('div');
    builderUI.id = 'zame-visual-kit-builder';
    builderUI.className = 'mt-8 border-t border-gray-100 dark:border-white/10 pt-10 animate-fadeIn';

    builderUI.innerHTML = `
        <div class="mb-10 text-center">
            <h3 class="font-luxury-title text-2xl mb-2 text-gray-900 dark:text-white">Crea tu Kit de Descubrimiento</h3>
            <p class="text-[10px] uppercase tracking-[0.2em] text-primary font-bold">Selecciona ${nativeSelects.length} fragancias para tu set</p>
        </div>
        <div id="kit-selection-slots" class="flex justify-center gap-2 md:gap-4 mb-16">
            ${Array.from({ length: nativeSelects.length }).map((_, i) => `
                <div class="kit-slot group cursor-pointer" data-index="${i}">
                    <div class="slot-circle w-14 h-16 md:w-20 md:h-24 rounded-lg border border-dashed border-gray-200 dark:border-white/10 flex items-center justify-center transition-all duration-500 group-[.filled]:border-solid group-[.filled]:border-primary overflow-hidden relative">
                        <span class="slot-number text-[10px] font-bold text-gray-300 group-[.filled]:opacity-0 transition-opacity">${i + 1}</span>
                        <div class="slot-image absolute inset-0 opacity-0 group-[.filled]:opacity-100 transition-opacity duration-700 p-1"></div>
                    </div>
                </div>
            `).join('')}
        </div>
        <div class="mb-6 flex justify-between items-end">
            <div>
                <span id="kit-counter" class="text-[11px] font-luxury text-primary">0 de ${nativeSelects.length} seleccionados</span>
            </div>
            <div class="relative w-48 md:w-64">
                <input type="text" id="fragrance-search" placeholder="Buscar fragancia..." class="w-full bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10 py-2 pl-2 pr-8 text-xs focus:border-primary outline-none transition-all dark:text-white" />
                <span class="material-icons-outlined absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm">search</span>
            </div>
        </div>
        <div id="kit-fragrance-grid" class="grid grid-cols-2 md:grid-cols-3 gap-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
            ${fragrances.map(f => `
                <button type="button" class="fragrance-item p-3 border border-gray-100 dark:border-white/5 text-left transition-all hover:border-primary group flex items-center gap-3 relative" data-value="${f.value}" data-label="${f.label}" data-image="${f.image}" title="${f.label}">
                    ${f.image ? `<div class="w-12 h-12 flex-shrink-0 bg-white p-1 rounded-md shadow-sm group-hover:scale-105 transition-transform"><img src="${f.image}" class="w-full h-full object-contain" loading="lazy" /></div>` : ''}
                    <div><div class="text-[10px] font-bold text-gray-900 dark:text-white leading-tight group-hover:text-primary transition-colors line-clamp-2">${f.label}</div></div>
                </button>
            `).join('')}
        </div>
    `;

    container.parentNode.insertBefore(builderUI, container.nextSibling);

    let selections = [];
    const updateUI = () => {
        // Update Slots
        builderUI.querySelectorAll('.kit-slot').forEach((slot, i) => {
            const imgDiv = slot.querySelector('.slot-image');
            if (selections[i]) {
                slot.classList.add('filled');
                imgDiv.innerHTML = selections[i].image ? `<img src="${selections[i].image}" class="w-full h-full object-contain" />` : '<span class="material-icons-outlined text-primary">auto_awesome</span>';
            } else {
                slot.classList.remove('filled');
                imgDiv.innerHTML = '';
            }
        });

        // Update Grid Items (Visual Feedback for Selection)
        builderUI.querySelectorAll('.fragrance-item').forEach(item => {
            const val = item.dataset.value;
            const isSelected = selections.some(s => s.value === val);
            if (isSelected) {
                item.classList.add('ring-2', 'ring-primary', 'bg-primary/5', 'shadow-md');
                item.classList.remove('border-gray-100', 'dark:border-white/5');
                // Optional: Add checkmark icon if not present? 
                // Let's keep it simple with border/glow as requested.
            } else {
                item.classList.remove('ring-2', 'ring-primary', 'bg-primary/5', 'shadow-md');
                item.classList.add('border-gray-100', 'dark:border-white/5');
            }
        });

        const counterEl = document.getElementById('kit-counter');
        const isComplete = selections.length === nativeSelects.length;

        counterEl.textContent = `${selections.length} de ${nativeSelects.length} seleccionados`;

        if (isComplete) {
            counterEl.innerHTML += ' <span class="text-green-500 font-bold ml-2 animate-bounce inline-block">¡Kit Completo!</span>';
            counterEl.classList.remove('text-primary');
            counterEl.classList.add('text-green-600', 'dark:text-green-400');
        } else {
            counterEl.classList.add('text-primary');
            counterEl.classList.remove('text-green-600', 'dark:text-green-400');
        }
        nativeSelects.forEach((select, i) => {
            select.value = selections[i]?.value || '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });
    };

    const searchInput = document.getElementById('fragrance-search');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase().trim();
            builderUI.querySelectorAll('.fragrance-item').forEach(item => {
                item.style.display = item.dataset.label.toLowerCase().includes(term) ? 'flex' : 'none';
            });
        });
    }

    builderUI.addEventListener('click', (e) => {
        const item = e.target.closest('.fragrance-item');
        if (item) {
            const val = item.dataset.value;
            const existing = selections.findIndex(s => s.value === val);
            if (existing > -1) selections.splice(existing, 1);
            else if (selections.length < nativeSelects.length) selections.push({ value: val, label: item.dataset.label, image: item.dataset.image });
            updateUI();
        }

        const slot = e.target.closest('.kit-slot.filled');
        if (slot) {
            selections.splice(parseInt(slot.dataset.index), 1);
            updateUI();
        }
    });
}
