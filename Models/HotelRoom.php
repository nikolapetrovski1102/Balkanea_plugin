<?php

namespace Models;

class HotelRoom
{
    private $wpdb;
    private $table = 'hotel_room';

    /** @var int */
    public int $post_id;
    /** @var int */
    public int $room_parent;
    /** @var string */
    public string $multi_location;
    /** @var string */
    public string $id_location;
    /** @var string */
    public string $address;
    /** @var string */
    public string $allow_full_day;
    /** @var float */
    public float $price;
    /** @var int */
    public int $number_room;
    /** @var string */
    public string $discount_rate;
    /** @var int */
    public int $adult_number;
    /** @var int */
    public int $child_number;
    /** @var string */
    public string $status;
    /** @var float */
    public float $adult_price;
    /** @var float */
    public float $child_price;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    // Create Room
    public function create(): bool
    {
        $data = [
            'post_id' => $this->post_id,
            'room_parent' => $this->room_parent,
            'multi_location' => $this->multi_location,
            'id_location' => $this->id_location,
            'address' => $this->address,
            'allow_full_day' => $this->allow_full_day,
            'price' => $this->price,
            'number_room' => $this->number_room,
            'discount_rate' => $this->discount_rate,
            'adult_number' => $this->adult_number,
            'child_number' => $this->child_number,
            'status' => $this->status,
            'adult_price' => $this->adult_price,
            'child_price' => $this->child_price
        ];

        $format = [
            '%d', '%d', '%s', '%s', '%s', '%s', '%f', '%d', '%s', '%d', '%d', '%s', '%f', '%f'
        ];

        try {
            $result = $this->wpdb->insert($this->wpdb->prefix . $this->table, $data, $format);
            if ($this->wpdb->last_error) {
                throw new \Exception($this->wpdb->last_error);
            }
            return $result !== false;
        } catch (\Exception $ex) {
            echo 'Caught exception: ', $ex->getMessage(), "\n";
            return false;
        }
    }

    // Read Room
    public function read($id): ?\stdClass
    {
        $query = $this->wpdb->prepare("SELECT * FROM " . $this->wpdb->prefix . $this->table . " WHERE post_id = %d", $id);
        return $this->wpdb->get_row($query);
    }

    // Update Room
    public function update(): bool
    {
        $data = [
            'post_id' => $this->post_id,
            'room_parent' => $this->room_parent,
            'multi_location' => $this->multi_location,
            'id_location' => $this->id_location,
            'address' => $this->address,
            'allow_full_day' => $this->allow_full_day,
            'price' => $this->price,
            'number_room' => $this->number_room,
            'discount_rate' => $this->discount_rate,
            'adult_number' => $this->adult_number,
            'child_number' => $this->child_number,
            'status' => $this->status,
            'adult_price' => $this->adult_price,
            'child_price' => $this->child_price
        ];

        $where = ['post_id' => $this->post_id];
        $format = [
            '%d', '%d', '%s', '%s', '%s', '%s', '%f', '%d', '%s', '%d', '%d', '%s', '%f', '%f'
        ];
        $where_format = ['%d'];

        try {
            $result = $this->wpdb->update($this->wpdb->prefix . $this->table, $data, $where, $format, $where_format);
            if ($this->wpdb->last_error) {
                throw new \Exception($this->wpdb->last_error);
            }
            return $result !== false;
        } catch (\Exception $ex) {
            echo 'Caught exception: ', $ex->getMessage(), "\n";
            return false;
        }
    }

    // Delete Room
    public function delete(): bool
    {
        $where = ['post_id' => $this->post_id];
        $where_format = ['%d'];

        try {
            $result = $this->wpdb->delete($this->wpdb->prefix . $this->table, $where, $where_format);
            if ($this->wpdb->last_error) {
                throw new \Exception($this->wpdb->last_error);
            }
            return $result !== false;
        } catch (\Exception $ex) {
            echo 'Caught exception: ', $ex->getMessage(), "\n";
            return false;
        }
    }

    public function get(): ?\stdClass
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM " . $this->wpdb->prefix . $this->table . " WHERE post_id = %d AND room_parent = %d",
            $this->post_id,
            $this->room_parent
        );
        $result = $this->wpdb->get_row($query);
    
        if ($this->wpdb->last_error) {
            throw new \Exception($this->wpdb->last_error);
        }
    
        return $result;
    }

}
?>
