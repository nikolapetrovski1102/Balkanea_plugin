<?php

namespace Models;
class RoomAvailability {
    private $wpdb;


    public function __construct($wpdb){
        $this->wpdb = $wpdb;
    }

    public function insertRoomAvailability($post_id, $parent_id)
    {

        echo $post_id;
        echo $parent_id;

        $data = [
            'post_id' => $post_id,
            'check_in' => '',
            'check_out' => '',
            'number' => 1,
            'post_type' => 'hotel_room',
            'price' => 0,
            'status' => 'available',
            'priority' => NULL,
            'number_booked' => 0,
            'parent_id' => $parent_id,
            'allow_full_day' => 'on',
            'number_end' => NULL,
            'booking_period' => 0,
            'is_base' => 0,
            'adult_number' => 2,
            'child_number' => 0,
            'adult_price' => 0,
            'child_price' => 0,
        ];

        $format = [
            '%d', '%d', '%d', '%d', '%s', '%f', '%s', '%s', '%d', '%d', '%s', 
            '%d', '%d', '%d', '%d', '%d', '%d', '%f', '%f'
        ];

        // Years to insert data for
        $years = [2024, 2025, 2026];

        try {
        foreach ($years as $year) {
            for ($month = 1; $month <= 12; $month++) {
                for ($day = 1; $day <= 31; $day++) {
                    if (checkdate($month, $day, $year)) {
                        $check_in = mktime(0, 0, 0, $month, $day, $year);

                        $check_out = $check_in;

                        // Check if entry already exists
                        // $existing = $this->wpdb->get_var(
                        //     $this->wpdb->prepare(
                        //         "SELECT COUNT(*) FROM {$this->wpdb->prefix}st_room_availability WHERE post_id = %d AND check_in = %d",
                        //         $post_id, $check_in
                        //     )
                        // );

                        // if ($existing == 0) {
                            $data['check_in'] = $check_in;
                            $data['check_out'] = $check_out;

                            print_r('<pre>');
                            print_r($data);
                            print_r('</pre>');

                            $result = $this->wpdb->insert($this->wpdb->prefix . 'st_room_availability', $data, $format);

                            if ($this->wpdb->last_error) {
                                throw new \Exception($this->wpdb->last_error);
                            }
                        // }
                    }
                }
            }
        }

        return 0;

    } catch (\Exception $ex) {
        return 'Caught exception: ' .  $ex->getMessage() . "\n";
    }

    }
}
?>
