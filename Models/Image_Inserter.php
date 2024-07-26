<?php

class ImageInserter {
    public static function insertImages($wpdb, $prefix, $current_date_time, $post_title, $post_id_name, $post_id, $hotel, $provider) {
        $current_year = date('Y');
        $current_month = date('m');
        $directory = "/home/balkanea/public_html/wp-content/uploads/$current_year/$current_month/" . self::sanitizeFileName($provider) . '_' . self::sanitizeFileName($hotel['name']);
        $image_origin_url = "https://balkanea.com/wp-content/uploads/$current_year/$current_month/" . self::sanitizeFileName($provider) . '_' . self::sanitizeFileName($hotel['name']) . '/';
        $counter = 0;
        $post_image_array_ids = '';

        try {
            foreach ($hotel['images'] as $img) {
                self::createDirectory($directory);
                $img_url = self::getImageUrl($img);
                $image_path = self::downloadImage($directory, $img_url);
                $image_guid = $image_origin_url . basename($img_url);

                $counter++;
                self::insertPost($wpdb, $prefix, $current_date_time, $post_title, $post_id_name, $post_id, $image_guid, $counter);
                $post_image_array_ids .= $wpdb->insert_id . ',';

                $photo_metadata = self::createPhotoMetadata($image_path, $current_year, $current_month);
                self::insertPostMeta($wpdb, $prefix, $image_path, $photo_metadata, $current_year, $current_month);
            }

            echo '<br>Data inserted successfully';
            $post_image_array_ids = rtrim($post_image_array_ids, ',');
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    private static function createDirectory($directory) {
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    private static function getImageUrl($img) {
        return str_replace('{size}', '640x400', $img);
    }

    private static function downloadImage($directory, $img_url) {
        $image_path = $directory . '/' . basename($img_url);
        file_put_contents($image_path, file_get_contents($img_url));
        return $image_path;
    }

    private static function insertPost($wpdb, $prefix, $current_date_time, $post_title, $post_id_name, $post_id, $image_guid, $counter) {
        $wpdb->insert(
            $prefix . 'posts',
            array(
                'post_author' => 14,
                'post_date' => $current_date_time,
                'post_date_gmt' => $current_date_time,
                'post_content' => '',
                'post_title' => $post_title . ' (' . $counter . ')',
                'post_excerpt' => '',
                'post_status' => 'inherit',
                'comment_status' => 'open',
                'ping_status' => 'closed',
                'post_password' => '',
                'post_name' => $post_id_name . '-' . $counter,
                'to_ping' => '',
                'pinged' => '',
                'post_modified' => $current_date_time,
                'post_modified_gmt' => $current_date_time,
                'post_content_filtered' => '',
                'post_parent' => $post_id,
                'guid' => $image_guid,
                'menu_order' => 0,
                'post_type' => 'attachment',
                'post_mime_type' => 'image/jpeg',
                'comment_count' => 0
            )
        );
    }

    private static function createPhotoMetadata($image_path, $current_year, $current_month) {
        return array(
            'width' => 640,
            'height' => 400,
            'file' => "$current_year/$current_month/" . basename($image_path),
            'filesize' => filesize($image_path),
            'sizes' => array(),
            'image_meta' => array(
                'aperture' => '0',
                'credit' => '',
                'camera' => '',
                'caption' => '',
                'created_timestamp' => '0',
                'copyright' => '',
                'focal_length' => '0',
                'iso' => '0',
                'shutter_speed' => '0',
                'title' => '',
                'orientation' => '1',
                'keywords' => array()
            )
        );
    }

    private static function insertPostMeta($wpdb, $prefix, $image_path, $photo_metadata, $current_year, $current_month) {
        $photo_metadata_serialized = serialize($photo_metadata);

        $wpdb->insert(
            $prefix . 'postmeta',
            array(
                'post_id' => $wpdb->insert_id,
                'meta_key' => '_wp_attached_file',
                'meta_value' => "$current_year/$current_month/" . basename($image_path)
            )
        );

        $wpdb->insert(
            $prefix . 'postmeta',
            array(
                'post_id' => $wpdb->insert_id,
                'meta_key' => '_wp_attachment_metadata',
                'meta_value' => $photo_metadata_serialized
            )
        );
    }

    private static function sanitizeFileName($name) {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', $name));
    }
}

?>