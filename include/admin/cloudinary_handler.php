<?php
namespace photographyclientmanagement_admin;

class Cloudinary_Handler {
    private $cloud_name = "debwo4wzu";
    private $api_key    = "578262542722968";
    private $api_secret = "CNhhZN7nRD7StHhbI61em9OOYZ0";

    public function upload_image($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        if (!$file_path) return false;

        $timestamp = time();
        // generate signature
        $params = ['timestamp' => $timestamp];
        ksort($params);
        $sign_str = http_build_query($params) . $this->api_secret;
        $signature = sha1($sign_str);

        $url = "https://api.cloudinary.com/v1_1/{$this->cloud_name}/image/upload";

        $response = wp_remote_post($url, [
            'body' => [
                'file'      => fopen($file_path, 'r'),
                'api_key'   => $this->api_key,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ],
            'timeout' => 60
        ]);

        if (is_wp_error($response)) return false;

        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['secure_url'])) {
            // Save the Cloudinary URL and public ID as post meta for the attachment
            update_post_meta($attachment_id, '_pcm_cloudinary_url', $data['secure_url']);
            update_post_meta($attachment_id, '_pcm_cloudinary_id', $data['public_id']);
            return $data['secure_url'];
        }

        return false;
    }
}