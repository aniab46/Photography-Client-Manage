<?php
namespace photographyclientmanagement_admin;

class Cloudinary_Handler {
    private $cloud_name = "debwo4wzu";
    private $api_key    = "578262542722968";
    private $api_secret = "CNhhZN7nRD7StHhbI61em9OOYZ0";

    public function upload_image($attachment_id) {
        // Get the file path of the attachment
        $file_path = get_attached_file($attachment_id);
        if (!$file_path) {
            error_log("PCM: Attachment file not found for ID: " . $attachment_id);
            return false;
        }

        // Check if file exists and is readable
        if (!file_exists($file_path) || !is_readable($file_path)) {
            error_log("PCM: File does not exist or is not readable: " . $file_path);
            return false;
        }

        $timestamp = time();

        // Cloudinary signature: sha1('timestamp=' . timestamp . api_secret)
        // Only timestamp goes in the signature, NOT api_key
        $string_to_sign = 'timestamp=' . $timestamp . $this->api_secret;
        $signature = sha1($string_to_sign);

        $url = "https://api.cloudinary.com/v1_1/{$this->cloud_name}/image/upload";

        if (!function_exists('curl_init')) {
            error_log("PCM: cURL is not enabled on this server");
            return false;
        }

        if (!function_exists('curl_file_create')) {
            error_log("PCM: curl_file_create is not available, trying alternative method");
            return $this->upload_image_alternative($attachment_id, $file_path, $timestamp, $signature);
        }

        $cfile = curl_file_create($file_path);
        $post_fields = [
            'file' => $cfile,
            'api_key' => $this->api_key,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        error_log("PCM: Starting upload to Cloudinary for attachment: " . $attachment_id);
        error_log("PCM: File path: " . $file_path);
        error_log("PCM: File size: " . filesize($file_path) . " bytes");
        error_log("PCM: Cloudinary URL: " . $url);
        error_log("PCM: Signature: " . $signature);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Try without SSL verification first
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

        $result = curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result === false) {
            error_log("PCM: cURL error: " . $curl_error);
            return false;
        }

        error_log("PCM: Cloudinary HTTP Response Code: " . $http_code);
        error_log("PCM: Cloudinary Raw Response: " . substr($result, 0, 1000));

        $data = json_decode($result, true);

        if (isset($data['secure_url'])) {
            error_log("PCM: Upload successful! Secure URL: " . $data['secure_url']);
            update_post_meta($attachment_id, '_pcm_cloudinary_url', $data['secure_url']);
            update_post_meta($attachment_id, '_pcm_cloudinary_id', $data['public_id']);
            return $data['secure_url'];
        }

        // Log error response from Cloudinary
        if (isset($data['error'])) {
            error_log("PCM: Cloudinary error: " . json_encode($data['error']));
        }

        error_log("PCM: Upload failed. Response: " . $result);
        return false;
    }

    private function upload_image_alternative($attachment_id, $file_path, $timestamp, $signature) {
        $url = "https://api.cloudinary.com/v1_1/{$this->cloud_name}/image/upload";
        
        $post_data = http_build_query([
            'file' => '@' . $file_path,
            'api_key' => $this->api_key,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: multipart/form-data\r\n",
                'content' => $post_data,
                'timeout' => 60,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            error_log("PCM: Alternative upload failed via stream");
            return false;
        }

        $data = json_decode($result, true);

        if (isset($data['secure_url'])) {
            error_log("PCM: Alternative upload successful! Secure URL: " . $data['secure_url']);
            update_post_meta($attachment_id, '_pcm_cloudinary_url', $data['secure_url']);
            update_post_meta($attachment_id, '_pcm_cloudinary_id', $data['public_id']);
            return $data['secure_url'];
        }

        if (isset($data['error'])) {
            error_log("PCM: Cloudinary error (alternative): " . json_encode($data['error']));
        }

        return false;
    }
}