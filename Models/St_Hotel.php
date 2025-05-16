<?php

namespace Models;

use Log;

class St_Hotel
{
    private $wpdb;
    private $table;

    public $post_id;
    public $multi_location;
    public $id_location;
    public $address;
    public $allow_full_day;
    public $rate_review;
    public $hotel_star;
    public $price_avg;
    public $min_price;
    public $hotel_booking_period;
    public $map_lat;
    public $map_lng;
    public $is_sale_schedule;
    public $post_origin;
    public $is_featured;
    private $log;

    public function __construct($wpdb, Log $log)
    {
        $this->wpdb = $wpdb;
        $this->log = $log;
        $this->is_featured = 'off';
        $this->table = 'st_hotel';
        $this->multi_location= '_14848_,_15095_';
        $this->post_origin = null;
        $this->is_sale_schedule = null;
        $this->hotel_booking_period = 0;
        $this->allow_full_day = 'on';
        $this->id_location = '';
    }

    // Create Hotel
    public function create()
    {
        $data = [
            'post_id' => (int)$this->post_id,
            'multi_location' => $this->multi_location,
            'id_location' => $this->id_location,
            'address' => $this->address,
            'allow_full_day' => $this->allow_full_day,
            'rate_review' => $this->rate_review,
            'hotel_star' => $this->hotel_star,
            'price_avg' => $this->price_avg,
            'min_price' => $this->min_price,
            'hotel_booking_period' => $this->hotel_booking_period,
            'map_lat' => $this->map_lat,
            'map_lng' => $this->map_lng,
            'is_sale_schedule' => $this->is_sale_schedule,
            'post_origin' => $this->post_origin,
            'is_featured' => $this->is_featured
        ];

        $format = [
            '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%d',
            '%f', '%f', '%s', '%s', '%s'
        ];

        try {
            $result = $this->wpdb->insert($this->wpdb->prefix . $this->table, $data, $format);
            $hotelId = $this->wpdb->insert_id;
            if ($this->wpdb->last_error) {
                $this->log->error($this->wpdb->last_error);
                throw new \Exception($this->wpdb->last_error);
            } else {
                $this->log->info('Crate a row in ST hotel table: '.$hotelId);
            }
        } catch (\Exception $e) {
            $this->log->error('Caught exception: ' . $e->getMessage() .$e->getTraceAsString());
        }
    }

    public function update()
    {
        $data = [
            'multi_location' => $this->multi_location,
            'id_location' => $this->id_location,
            'address' => $this->address,
            'allow_full_day' => $this->allow_full_day,
            'rate_review' => $this->rate_review,
            'hotel_star' => $this->hotel_star,
            'price_avg' => $this->price_avg,
            'min_price' => $this->min_price,
            'hotel_booking_period' => $this->hotel_booking_period,
            'map_lat' => $this->map_lat,
            'map_lng' => $this->map_lng,
            'is_sale_schedule' => $this->is_sale_schedule,
            'post_origin' => $this->post_origin,
            'is_featured' => $this->is_featured
        ];

        $where = ['post_id' => (int)$this->post_id];
        $format = [
            '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%d',
            '%f', '%f', '%s', '%s', '%s'
        ];
        $where_format = ['%d'];

        try {
            $result = $this->wpdb->update($this->wpdb->prefix . $this->table, $data, $where, $format, $where_format);

            if ($this->wpdb->last_error) {
                throw new \Exception($this->wpdb->last_error);
            } else {
                $this->log->info("Data for st_hotel updated successfull");
            }
        } catch (\Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    // Get Hotel
    public function get()
    {
        $query = $this->wpdb->prepare("SELECT * FROM " . $this->wpdb->prefix . $this->table . " WHERE post_id = %d", $this->post_id);

        try {
            $result = $this->wpdb->get_row($query);

            if ($this->wpdb->last_error) {
                throw new \Exception($this->wpdb->last_error);
            } else {
                return $result;
            }
        } catch (\Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

}
?>
