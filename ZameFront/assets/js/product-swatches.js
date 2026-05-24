document.addEventListener('DOMContentLoaded', function () {
    // [LEGACY DISABLED] - Swatch logic is now handled by product-interaction.js
    // Only Cart Logic should remain active in this file.

    /* 
    // Only run on product pages with variations
    const variationForms = document.querySelectorAll('form.variations_form');
    // ... (Legacy code omitted/commented out) ...
    */

    // Dynamic Price Logic (jQuery for WC compatibility)
    if (typeof jQuery !== 'undefined') {
        /*
        jQuery('.variations_form').on('found_variation', function (event, variation) {
             // [LEGACY DISABLED]
        });
        */


        // Listen for Quantity Change
        jQuery(document).on('change input', 'input.qty', function () {
            updateTotalPrice();
        });

        function updateTotalPrice() {
            const $form = jQuery('.variations_form');
            const unitPrice = $form.data('unit_price');
            const $qtyInput = jQuery('input.qty');

            if (!unitPrice || !$qtyInput.length) return;

            const qty = parseFloat($qtyInput.val()) || 1;
            const total = unitPrice * qty;

            // Format Currency (Colombia / Default)
            const formatted = new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(total);

            // Update Price Display
            const $priceContainer = jQuery('.woocommerce-variation-price .price');
            if ($priceContainer.length) {
                $priceContainer.html(`<span class="woocommerce-Price-amount amount"><bdi>${formatted}</bdi></span>`);
            }
        }

        /**
         * CART AUTO-UPDATE LOGIC
         */
        jQuery(document).on('change', '.woocommerce-cart-form input.qty', function () {
            const $button = jQuery('button[name="update_cart"]');
            $button.prop('disabled', false).click();
        });

        // Handle AJAX cart updates to re-apply styles if needed
        jQuery(document.body).on('updated_cart_totals', function () {
            // Any post-update logic here
            console.log('Cart updated via AJAX');
        });
    }

});
