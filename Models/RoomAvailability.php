<?php
namespace Models;

class RoomAvailability {
    private $wpdb;

    public function __construct($wpdb) {
        $this->wpdb = $wpdb;
    }

    public function insertRoomAvailability($post_id, $parent_id) {
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
            
                            $data['check_in'] = $check_in;
                            $data['check_out'] = $check_out;
            
                            $query = $this->wpdb->prepare(
                                "INSERT INTO {$this->wpdb->prefix}st_room_availability (post_id, check_in, check_out, number, post_type, price, status, priority, number_booked, parent_id, allow_full_day, number_end, booking_period, is_base, adult_number, child_number, adult_price, child_price)
                                VALUES (%d, %d, %d, %d, %s, %f, %s, %s, %d, %d, %s, %d, %d, %d, %d, %d, %f, %f)
                                ON DUPLICATE KEY UPDATE price = VALUES(price), status = VALUES(status), number_booked = VALUES(number_booked), priority = VALUES(priority)",
                                $data['post_id'], $data['check_in'], $data['check_out'], $data['number'], $data['post_type'], $data['price'], $data['status'], $data['priority'], $data['number_booked'], $data['parent_id'], $data['allow_full_day'], $data['number_end'], $data['booking_period'], $data['is_base'], $data['adult_number'], $data['child_number'], $data['adult_price'], $data['child_price']
                            );
            
                            $result = $this->wpdb->query($query);
            
                            if ($this->wpdb->last_error) {
                                throw new \Exception($this->wpdb->last_error);
                            }
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