<?php
namespace Models;
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
    public function __construct($wpdb) {
        $this->wpdb = $wpdb;
        $this->img_url = '';
    }

    public function insertImages() {

        print_r('Directory URL: <br>');
        print_r($this->directory_url . '<br>');

        $directory = "/home/balkanea/public_html/wp-content/uploads/" . self::sanitizeFileName($this->provider) . '/' . $this->directory_url;
        $image_origin_url = "https://balkanea.com/wp-content/uploads/" . self::sanitizeFileName($this->provider) . '/' . $this->directory_url . '/';
        $counter = 0;
        $post_image_array_ids = '';

        $counter = 0;

        try {

            if (empty($this->hotel['images'])) {
                $this->hotel['images'][] = $this->default_image;
            }

            foreach ($this->hotel['images'] as $img) {
                $counter++;

                if (explode('/',$this->directory_url))
                        $new_image = explode('/',$this->directory_url)[1];

                $img_url = self::getImageUrl($img);

                $image_url_exsists = self::imageUrlExsist($image_origin_url . $new_image . '.jpg');

                if (!file_exists($directory . '/' . basename($new_image))) {
                    $this->createDirectory($directory);

                    $this->image_path = self::downloadImage($directory, $img_url, $new_image . '-' . $counter);
                    
                }
                if (self::imageUrlExsist($image_origin_url . basename($new_image . '.jpg'))) {
                    $post_image_array_ids .= $image_url_exsists . ',';
                }
                else{
                    if ($this->image_path) {
                    $this->image_guid = $image_origin_url . $new_image . '.jpg';
                        $counter++;
                        $this->insertPost($counter);
                        $post_image_array_ids .= $this->wpdb->insert_id . ',';

                        $photo_metadata = self::createPhotoMetadata();
                        $this->insertPostMeta( $photo_metadata );
                    }
                }
            }

            $post_image_array_ids = rtrim($post_image_array_ids, ',');

            return $post_image_array_ids;

        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    private function createDirectory($directory) {
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    private  function getImageUrl($img) {
        return str_replace('{size}', '640x400', $img);
    }

    private function downloadImage($directory, $img_url, $new_name = null) {
        $image_name = $new_name ? $new_name : basename($img_url);
        $this->image_path = $directory . '/' . $image_name . '.jpg';
        $this->img_url = $new_name . '.jpg';
        
        if (!file_exists($this->image_path)) {
            $ch = curl_init($img_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
            $image_data = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
    
            if ($image_data && $http_code == 200) {
                file_put_contents($this->image_path, $image_data);
                if (file_exists($this->image_path)) {
                    return $this->image_path;
                } else {
                    echo "Failed to save the image: $img_url";
                    return false;
                }
            } else {
                echo "Failed to download image: $img_url. HTTP Code: $http_code";
                return false;
            }
        }
        
        return $this->image_path;
    }


    private function insertPost($counter) {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'posts',
            array(
                'post_author' => 14,
                'post_date' => date('Y-m-d H:i:s'),
                'post_date_gmt' => date('Y-m-d H:i:s'),
                'post_content' => '',
                'post_title' => $this->post_title . ' (' . $counter . ')',
                'post_excerpt' => '',
                'post_status' => 'inherit',
                'comment_status' => 'open',
                'ping_status' => 'closed',
                'post_password' => '',
                'post_name' => $this->post_id_name . '-' . $counter,
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
            )
        );
    }

    private  function createPhotoMetadata() {
        return array(
            'width' => 640,
            'height' => 400,
            'file' =>   self::sanitizeFileName($this->provider) . '/' . $this->directory_url . '/'. basename($this->image_path),
            'filesize' => filesize($this->image_path),
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

    private function insertPostMeta( $photo_metadata ) {
        $photo_metadata_serialized = serialize($photo_metadata);

        $this->wpdb->insert(
            $this->wpdb->prefix . 'postmeta',
            array(
                'post_id' => $this->wpdb->insert_id,
                'meta_key' => '_wp_attached_file',
                'meta_value' =>  self::sanitizeFileName($this->provider) . '/' . $this->directory_url . '/'. basename($this->image_path)
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

    private function imageUrlExsist($image_origin_url){
        
        $query = $this->wpdb->prepare("SELECT ID FROM " . $this->wpdb->prefix . 'posts' . " WHERE guid = %s", $image_origin_url);
        $result = $this->wpdb->get_row($query);

        if ($result)
            return strval($result->ID);
        else
            return false;

    }

}
?>