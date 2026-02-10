<?php 

namespace photographyclientmanagement_metabox;

class Meta_box_manage{
    public function __construct(){
        add_action('add_meta_boxes',[$this,'add_meta_box']);
        add_action('save_post',[$this,'save_meta_box_data']);
        
        // Register meta box for client gallery using Meta Box plugin
        add_filter( 'rwmb_meta_boxes', [$this,'your_prefix_register_meta_boxes'] );

        // Include Cloudinary handler for image upload
        include_once plugin_dir_path(__FILE__) .'cloudinary_handler.php';
        
    }


    public function add_meta_box(){
        add_meta_box(
            'client_details',
            'Client Details',
            [$this,'render_meta_box'],
            'client',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post){
        // Add switcher is_paid or not, a text input for client security code.
        $is_paid = get_post_meta($post->ID, '_is_paid', true);
        ?>
        <label for="is_paid">Is Paid:</label>
        <input type="checkbox" name="is_paid" id="is_paid" <?php checked($is_paid, 'yes'); ?>>
        <?php

        // Security code
        $client_security_code = get_post_meta($post->ID, '_client_security_code', true);
        ?>
        <br><br>
        <label for="client_security_code">Security Code:</label>
        <input type="text" name="client_security_code" id="client_security_code" value="<?php echo esc_attr($client_security_code); ?>" size="25" />
        <?php
        
        

    }

    public function save_meta_box_data($post_id){
        if (isset($_POST['is_paid'])) {
            update_post_meta($post_id, '_is_paid', 'yes');
        } else {
            update_post_meta($post_id, '_is_paid', 'no');
        }

        if (isset($_POST['client_security_code'])) {
            update_post_meta($post_id, '_client_security_code', sanitize_text_field($_POST['client_security_code']));
        } else {
            delete_post_meta($post_id, '_client_security_code');
        }

        // Get gallery images using rwmb_meta() for Meta Box plugin
        $gallery_images = rwmb_meta('pcm_client_gallery', array('size' => 'full'), $post_id);

        if (!empty($gallery_images) && is_array($gallery_images)) {
            $cloudinary = new \photographyclientmanagement_admin\Cloudinary_Handler();
            foreach ($gallery_images as $image) {
                // Extract attachment ID from image array
                $attachment_id = isset($image['ID']) ? $image['ID'] : (is_numeric($image) ? $image : null);
                
                if (!$attachment_id) {
                    continue;
                }
                
                // Check if the image has already been uploaded to Cloudinary to avoid duplicate uploads
                $exists = get_post_meta($attachment_id, '_pcm_cloudinary_url', true);
                if (!$exists) {
                    error_log("PCM: Uploading attachment ID: " . $attachment_id);
                    $result = $cloudinary->upload_image($attachment_id);
                    if ($result) {
                        error_log("PCM: Successfully uploaded attachment ID: " . $attachment_id);
                    } else {
                        error_log("PCM: Failed to upload attachment ID: " . $attachment_id);
                    }
                }
            }
        } else {
            error_log("PCM: No gallery images found or gallery_images is not an array. Type: " . gettype($gallery_images));
        }
    }

    public function your_prefix_register_meta_boxes( $meta_boxes ) {
    $prefix = 'pcm_';

    $meta_boxes[] = [
        'title'      => esc_html__( 'Client Gallery', 'online-generator' ),
        'id'         => 'gallery_meta_box',
        'post_types' => ['client'],
        'context'    => 'normal',
        'fields'     => [
            [
                'type' => 'image_advanced',
                'name' => esc_html__( 'Client Gallery', 'online-generator' ),
                'id'   => $prefix . 'client_gallery',
            ],
        ],
    ];

    return $meta_boxes;
    }
    
}