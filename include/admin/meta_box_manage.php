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
        // images input field for client gallery
        

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

        $gallery_ids = get_post_meta($post_id, 'pcm_client_gallery', true); // Meta Box field ID

    if (!empty($gallery_ids)) {
        $cloudinary = new \photographyclientmanagement_admin\Cloudinary_Handler();
        foreach ($gallery_ids as $attachment_id) {
            // Check if the image has already been uploaded to Cloudinary to avoid duplicate uploads
            $exists = get_post_meta($attachment_id, '_pcm_cloudinary_url', true);
            if (!$exists) {
                $cloudinary->upload_image($attachment_id);
            }
        }
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