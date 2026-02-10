jQuery(document).ready(function($) {
    $('.pcm-download').on('click', function() {
        var btn = $(this);
        $.post(pcm_ajax_object.ajax_url, {
            action: 'pcm_handle_download',
            post_id: btn.data('post'),
            attachment_id: btn.data('img'),
            nonce: pcm_ajax_object.nonce
        }, function(res) {
            if (res.success) {
                window.open(res.data.url, '_blank');
            } else {
                alert(res.data && res.data.message ? res.data.message : 'An error occurred');
            }
        });
    });
});