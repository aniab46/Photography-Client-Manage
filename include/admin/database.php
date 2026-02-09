<?php 
function pcm_create_activity_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pcm_activities';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        attachment_id bigint(20) NOT NULL,
        client_ip varchar(100) DEFAULT '',
        action_type varchar(20) NOT NULL, -- 'download' or 'print'
        print_size varchar(50) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}