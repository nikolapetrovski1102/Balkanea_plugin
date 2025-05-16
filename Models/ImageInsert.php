<?php
namespace Models;

use Log;

class ImageInserter {
    private $wpdb;
    public $current_date_time;
    public $post_title;
    public $post_id_name;
    public $post_id;
    public $hotel;
    public $directory_url;
    public $provider;
    public $default_image;
    private $image_path;
    private $image_guid;
    private $img_url;
    private Log $log;

    public function __construct($wpdb, Log $log) {
        $this->wpdb = $wpdb;
        $this->log = $log;
        $this->img_url = '';
    }

    public function insertImages() {
        try {
            $post_image_array_ids = '';
            $this->log->info("Insert images. Total: " . count($this->default_image));
            if (empty($this->hotel['images'])) {
                $this->hotel['images'] = $this->default_image;
            }
            foreach ($this->default_image as $img) {
                $img_url = self::getImageUrl($img);
                if($imgId = $this->imageUrlExists($img_url)){
                    $post_image_array_ids .= $imgId . ',';
                }
                else{
                    $this->image_guid = $img_url;
                    $imgId = $this->insertPost();
                    $post_image_array_ids .= $imgId . ',';

                    $photo_metadata = self::createPhotoMetadata($img_url);
                    $this->insertPostMeta($photo_metadata, $img_url);
                }
            }
            $post_image_array_ids = rtrim($post_image_array_ids, ',');
            $this->log->info("Finish Insert images.");

            return $post_image_array_ids;
        } catch (\Exception $e) {
            $this->log->error('Caught exception: ' . $e->getMessage());
        }
    }

    private function createDirectory($directory) {
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    private function getImageUrl($img) {
        return str_replace('{size}', '640x400', $img);
    }

    private function downloadImage($img_url, $new_name) {

        $ch = curl_init($img_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $image_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($image_data && $http_code == 200) {
            if (file_put_contents($this->image_path, $image_data) === false) {
                echo "Failed to save the image: $img_url";
                return false;
            }
        } else {
            echo "Failed to download image: $img_url. HTTP Code: $http_code";
            return false;
        }

        return $this->image_path;
    }

    private function insertPost() {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'posts',
            [
                'post_author' => 6961,
                'post_date' => date('Y-m-d H:i:s'),
                'post_date_gmt' => date('Y-m-d H:i:s'),
                'post_content' => '',
                'post_title' => $this->post_title,
                'post_excerpt' => '',
                'post_status' => 'inherit',
                'comment_status' => 'open',
                'ping_status' => 'closed',
                'post_password' => '',
                'post_name' => self::sanitizeFileName( $this->post_title ) . '-' . uniqid(),
                'to_ping' => '',
                'pinged' => '',
                'post_modified' => date('Y-m-d H:i:s'),
                'post_modified_gmt' => date('Y-m-d H:i:s'),
                'post_content_filtered' => '',
                'post_parent' => $this->post_id,
                'guid' => $this->image_guid,
                'menu_order' => 0,
                'post_type' => 'attachment',
                'post_mime_type' => 'image/jpeg',
                'comment_count' => 0
            ]
        );
        return $this->wpdb->insert_id;
    }

    private function createPhotoMetadata(string $img_url) {
        return array(
            'width' => 640,
            'height' => 400,
            //'file' => self::sanitizeFileName($this->provider) . '/' . $this->directory_url . '/' . basename($this->image_path),
            'file' => $img_url,
           // 'filesize' => filesize($this->image_path),
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

    private function insertPostMeta(array $photo_metadata, string $img_url) {
        $photo_metadata_serialized = serialize($photo_metadata);
        $this->wpdb->insert(
            $this->wpdb->prefix . 'postmeta',
            array(
                'post_id' => $this->wpdb->insert_id,
                'meta_key' => '_wp_attached_file',
           //     'meta_value' => self::sanitizeFileName($this->provider) . '/' . $this->directory_url . '/' . basename($this->image_path)
                'meta_value' => $img_url,
            )
        );

        $this->wpdb->insert(
            $this->wpdb->prefix . 'postmeta',
            array(
                'post_id' => $this->wpdb->insert_id,
                'meta_key' => '_wp_attachment_metadata',
                'meta_value' => $photo_metadata_serialized
            )
        );
    }

    private function sanitizeFileName($name) {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', $name));
    }

    private function imageUrlExists($image_origin_url){
        $query = $this->wpdb->prepare("SELECT ID FROM " . $this->wpdb->prefix . 'posts' . " WHERE guid = %s", $image_origin_url);
        $result = $this->wpdb->get_row($query);

        if ($result)
            return strval($result->ID);
        else
            return false;
    }
}

?>
