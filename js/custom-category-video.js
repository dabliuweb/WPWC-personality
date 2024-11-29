jQuery(document).ready(function ($) {
    // Abre o media uploader
    $('#upload-video-button').on('click', function (e) {
        e.preventDefault();

        var mediaUploader = wp.media({
            title: 'Selecionar Vídeo',
            button: { text: 'Usar Vídeo' },
            library: { type: 'video' },
            multiple: false
        });

        mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#product-cat-video').val(attachment.url);
            $('#product-cat-video-url').val(''); // Limpa a URL manual
            $('#product-cat-video-preview').html('<video src="' + attachment.url + '" controls style="max-width: 100%;"></video>');
            $('#remove-video-button').show();
        });

        mediaUploader.open();
    });

    // Remove o vídeo
    $('#remove-video-button').on('click', function (e) {
        e.preventDefault();
        $('#product-cat-video').val('');
        $('#product-cat-video-url').val('');
        $('#product-cat-video-preview').html('');
        $(this).hide();
    });

    // Sincroniza o campo de URL manual com o preview
    $('#product-cat-video-url').on('input', function () {
        var url = $(this).val();
        if (url) {
            $('#product-cat-video').val(url); // Define o valor no campo hidden
            $('#product-cat-video-preview').html('<video src="' + url + '" controls style="max-width: 100%;"></video>');
            $('#remove-video-button').show();
        } else {
            $('#product-cat-video-preview').html('');
            $('#remove-video-button').hide();
        }
    });
});
