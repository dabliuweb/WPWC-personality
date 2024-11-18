/* admin-script.js */
jQuery(document).ready(function ($) {
    // Alternar entre abas
    $('#wc-custom-prices-tabs .tabs li').on('click', function () {
        const tab = $(this).data('tab');
        $('#wc-custom-prices-tabs .tabs li').removeClass('active');
        $(this).addClass('active');
        $('#wc-custom-prices-tabs .tab-content').removeClass('active');
        $('#' + tab).addClass('active');
    });

    // Salvar configurações via AJAX
    $('#wc-custom-prices-save-button').on('click', function () {
        const formData = $('#wc-custom-prices-settings-form').serialize();

        $('#wc-custom-prices-save-notice').hide().removeClass('updated error');

        $.post(ajaxurl, {
            action: 'wc_custom_prices_save_settings',
            data: formData,
        })
            .done((response) => {
                if (response.success) {
                    $('#wc-custom-prices-save-notice')
                        .addClass('updated')
                        .text(response.data.message)
                        .fadeIn();
                } else {
                    $('#wc-custom-prices-save-notice')
                        .addClass('error')
                        .text(response.data.message)
                        .fadeIn();
                }
            })
            .fail(() => {
                $('#wc-custom-prices-save-notice')
                    .addClass('error')
                    .text('Erro ao salvar configurações.')
                    .fadeIn();
            });
    });
});
