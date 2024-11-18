jQuery(document).ready(function($) {
    $('.woocommerce_variation').each(function() {
        const pricingDiv = $(this).find('.variable_pricing');
        
        if (pricingDiv.length && !pricingDiv.find('.wc-custom-price').length) {
            // Adiciona o campo dentro da div.variable_pricing
            pricingDiv.append(`
                <p class="form-field form-row form-row-full wc-custom-price">
                    <label for="vista_price_${$(this).data('variation-id')}">Preço à Vista (R$)</label>
                    <input type="text" class="short wc_input_price" name="vista_price[${$(this).data('variation-id')}]" id="vista_price_${$(this).data('variation-id')}" value="">
                </p>
            `);
        }
    });
});