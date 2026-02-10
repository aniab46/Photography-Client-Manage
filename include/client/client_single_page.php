<?php 

namespace photographyclientmanagement;

class Client_single_page{
    public function __construct(){
        add_filter('the_content',[$this,'display_client_details'],20,1);
        add_action('wp_enqueue_scripts', [$this,'enqueue_styles_and_scripts']);

        // Ajax handler include and initialization
        include_once plugin_dir_path(__FILE__) .'ajax_handler.php';
        new \ajaxhandler\ajax_handler();
        
    }

    public function display_client_details($content){
        if(is_single() && 'client' == get_post_type()){
            // Show a input form for the security code, if the code matches the one in the database, show the client details and gallery.
            $client_security_code = get_post_meta(get_the_ID(), '_client_security_code', true);
            if(isset($_POST['client_security_code'])){
                if(sanitize_text_field($_POST['client_security_code']) == $client_security_code){
                    
                // display_client_details 
                $gallery = rwmb_meta( 'pcm_client_gallery', ['size' => 'full'], get_the_ID() );
                foreach ($gallery as $image) {
                    $attachment_id = $image['ID'];
                    $cloudinary_url = get_post_meta($attachment_id, '_pcm_cloudinary_url', true);
                    $is_paid = get_post_meta(get_the_ID(), '_is_paid', true);

                    // If not paid, show low quality blurred image from Cloudinary only
                    if ($is_paid !== 'yes') {
                        // Free users: only show image if it exists on Cloudinary
                        if (!$cloudinary_url) {
                            $content .= '<div class="photo-card" style="display:inline-block; margin:10px; border:1px solid #ddd; padding:5px;">';
                            $content .= '<div style="max-width: 200px; height: 200px; background-color: #f0f0f0; display:flex; align-items:center; justify-content:center;">Image processing...</div>';
                            $content .= '</div>';
                            continue;
                        }
                        $display_url = str_replace('/upload/', '/upload/w_400,q_auto:low,e_blur:200/', $cloudinary_url);
                        $btn_text = "Download (Free)";
                    } else {
                        // Paid users: fallback to original if Cloudinary not available
                        $display_url = $cloudinary_url ? $cloudinary_url : $image['full_url'];
                        $btn_text = "Download High Res";
                    }

                    $content .= '<div class="photo-card" style="display:inline-block; margin:10px; border:1px solid #ddd; padding:5px;">';
                    $content .= '<img src="' . esc_url($display_url) . '" style="max-width: 200px; display:block;">';

                    // Download button with data attributes for JavaScript handling
                    $content .= '<button class="pcm-download" data-img="' . esc_attr($attachment_id) . '" data-post="' . get_the_ID() . '">' . esc_html($btn_text) . '</button>';
                    $content .= '<button class="pcm-print" data-img="' . esc_attr($attachment_id) . '">Request Print</button>';
                    $content .= '</div>';
                }
                } else {
                    $content .= '<p style="color:red;">Incorrect security code. Please try again.</p>';
                }
            } else {
                // Show input form for security code
                $content .= '<form method="post">';
                $content .= '<label for="client_security_code">Enter Security Code:</label><br>';
                $content .= '<input type="text" id="client_security_code" name="client_security_code" required><br><br>';
                $content .= '<input type="submit" value="Submit">';
                $content .= '</form>';
            }

        }
        return $content;
    }

    public function enqueue_styles_and_scripts(){
        if(is_single() && 'client' == get_post_type()){
            wp_enqueue_style('pcm-client-single-page', PCM_PLUGIN_URL . 'assets/css/tailwind.css', [], PCM_PLUGIN_VERSION);
            wp_enqueue_script('pcm-client-single-page', PCM_PLUGIN_URL . 'assets/js/main.js', ['jquery'], PCM_PLUGIN_VERSION, true);
            wp_localize_script('pcm-client-single-page', 'pcm_ajax_object', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pcm_ajax_nonce')
            ]);
        }
    }
    
}