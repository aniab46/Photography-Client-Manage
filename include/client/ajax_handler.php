<?php 

namespace ajaxhandler;

class ajax_handler{
    public function __construct(){
        add_action('wp_ajax_pcm_handle_download', [$this, 'pcm_handle_download']);
        add_action('wp_ajax_nopriv_pcm_handle_download', [$this, 'pcm_handle_download']);

        add_action('wp_ajax_pcm_handle_print', [$this, 'pcm_handle_print']);
        add_action('wp_ajax_nopriv_pcm_handle_print', [$this, 'pcm_handle_print']);
    }

    

    function pcm_handle_download() {
        global $wpdb;
        // Verify nonce
        if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'pcm_ajax_nonce' ) ) {
            wp_send_json_error(['message' => 'Invalid request.']);
        }

        $post_id = intval($_POST['post_id']);
        $attachment_id = intval($_POST['attachment_id']);
        $client_ip = $_SERVER['REMOTE_ADDR'];
        $table_name = $wpdb->prefix . 'pcm_activities';

        $is_paid = get_post_meta($post_id, '_is_paid', true);

        $cloudinary_url = get_post_meta($attachment_id, '_pcm_cloudinary_url', true);
        
        // Free users can only access images through Cloudinary with transformations
        if ($is_paid !== 'yes' && empty($cloudinary_url)) {
            wp_send_json_error(['message' => 'Image is still being processed. Please check back soon.']);
        }
        
        // Paid users can fallback to original attachment URL
        if (empty($cloudinary_url)) {
            $cloudinary_url = wp_get_attachment_url($attachment_id);
        }

        // Paid users get original high res
        if ($is_paid === 'yes') {
            wp_send_json_success(['url' => $cloudinary_url]);
        }

        // Already downloaded this image from this IP?
        $already_done = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id =   %d AND client_ip = %s AND attachment_id = %d",
            $post_id, $client_ip, $attachment_id
        ));

        if ($already_done > 0) {
            // If already downloaded, return low-res URL
            $low_url = str_replace('/upload/', '/upload/w_800,q_auto:low,e_blur:200/', $cloudinary_url);
            wp_send_json_success(['url' => $low_url]);
        }

        // Total downloads from this IP for this post?
        $total_downloads = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT attachment_id) FROM $table_name WHERE post_id = %d AND client_ip = %s AND action_type = 'download'",
            $post_id, $client_ip
        ));

        if ($total_downloads < 5) {
            $wpdb->insert($table_name, [
                'post_id' => $post_id,
                'attachment_id' => $attachment_id,
                'client_ip' => $client_ip,
                'action_type' => 'download'
            ]);
            $low_url = str_replace('/upload/', '/upload/w_800,q_auto:low,e_blur:200/', $cloudinary_url);
            wp_send_json_success(['url' => $low_url, 'remaining' => 4 - $total_downloads]);
        } else {
            wp_send_json_error(['message' => 'Your 5 free downloads for this post are over. Please contact the photographer for more.']);
        }
    }

    function pcm_handle_print() {
    global $wpdb;
    $post_id = intval($_POST['post_id']);
    $attachment_id = intval($_POST['attachment_id']);
    $size = sanitize_text_field($_POST['size']);

    $wpdb->insert($wpdb->prefix . 'pcm_activities', [
        'post_id' => $post_id,
        'attachment_id' => $attachment_id,
        'action_type' => 'print',
        'print_size' => $size,
        'client_ip' => $_SERVER['REMOTE_ADDR']
    ]);

    wp_send_json_success(['message' => 'Print request sent. The photographer will contact you soon.']);
    }
}