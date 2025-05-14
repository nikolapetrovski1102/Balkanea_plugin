<?php
namespace Models;

class RoomAvailability {
    private $wpdb;
    private const BATCH_SIZE = 500; // Number of records to insert in a single query
    
    public function __construct($wpdb) {
        $this->wpdb = $wpdb;
    }
    
    /**
     * Insert room availability using bulk operations for better performance
     * 
     * @param int $post_id Room post ID
     * @param int $parent_id Parent hotel ID
     * @return int|string 0 on success, error message on failure
     */
    public function insertRoomAvailability($post_id, $parent_id) {
        if (!$post_id || !$parent_id) {
            return 'Invalid post_id or parent_id';
        }
        
        // Base data for all records
        $baseData = [
            'post_id' => $post_id,
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
        
        $years = [2024, 2025, 2026];
        
        try {
            // Begin transaction for atomicity
            $this->wpdb->query('START TRANSACTION');
            
            // Create availability records in batches
            $dateValues = [];
            $valueCount = 0;
            $totalInserted = 0;
            
            foreach ($years as $year) {
                for ($month = 1; $month <= 12; $month++) {
                    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                    
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        // We can use cal_days_in_month instead of checkdate for better performance
                        $check_in = mktime(0, 0, 0, $month, $day, $year);
                        $check_out = $check_in;
                        
                        // Add values for this date
                        $dateValues[] = $post_id;
                        $dateValues[] = $check_in;
                        $dateValues[] = $check_out;
                        $dateValues[] = $baseData['number'];
                        $dateValues[] = $baseData['post_type'];
                        $dateValues[] = $baseData['price'];
                        $dateValues[] = $baseData['status'];
                        $dateValues[] = $baseData['priority'];
                        $dateValues[] = $baseData['number_booked'];
                        $dateValues[] = $baseData['parent_id'];
                        $dateValues[] = $baseData['allow_full_day'];
                        $dateValues[] = $baseData['number_end'];
                        $dateValues[] = $baseData['booking_period'];
                        $dateValues[] = $baseData['is_base'];
                        $dateValues[] = $baseData['adult_number'];
                        $dateValues[] = $baseData['child_number'];
                        $dateValues[] = $baseData['adult_price'];
                        $dateValues[] = $baseData['child_price'];
                        
                        $valueCount++;
                        
                        // Execute batch insert when batch size is reached
                        if ($valueCount >= self::BATCH_SIZE) {
                            $this->executeBatchInsert($dateValues, $valueCount);
                            $totalInserted += $valueCount;
                            $dateValues = [];
                            $valueCount = 0;
                        }
                    }
                }
            }
            
            // Insert remaining records
            if ($valueCount > 0) {
                $this->executeBatchInsert($dateValues, $valueCount);
                $totalInserted += $valueCount;
            }
            
            // Commit the transaction
            $this->wpdb->query('COMMIT');
            
            return 0;
        } catch (\Exception $ex) {
            // Rollback on error
            $this->wpdb->query('ROLLBACK');
            error_log('Error in insertRoomAvailability: ' . $ex->getMessage());
            return 'Caught exception: ' . $ex->getMessage() . "\n";
        }
    }
    
    /**
     * Execute a batch insert of room availability records
     * 
     * @param array $values Array of values to insert
     * @param int $count Number of records in the batch
     * @return void
     * @throws \Exception If the query fails
     */
    private function executeBatchInsert(array $values, int $count): void {
        // Create the placeholders for the batch insert
        $placeholders = [];
        for ($i = 0; $i < $count; $i++) {
            $placeholders[] = "(%d, %d, %d, %d, %s, %f, %s, %s, %d, %d, %s, %d, %d, %d, %d, %d, %f, %f)";
        }
        
        // Build the SQL query
        $sql = "INSERT INTO {$this->wpdb->prefix}st_room_availability 
                (post_id, check_in, check_out, number, post_type, price, status, priority, 
                number_booked, parent_id, allow_full_day, number_end, booking_period, 
                is_base, adult_number, child_number, adult_price, child_price)
                VALUES " . implode(', ', $placeholders) . "
                ON DUPLICATE KEY UPDATE 
                price = VALUES(price), 
                status = VALUES(status), 
                number_booked = VALUES(number_booked), 
                priority = VALUES(priority)";
        
        // Prepare and execute the query
        $prepared_query = $this->wpdb->prepare($sql, $values);
        $result = $this->wpdb->query($prepared_query);
        
        if ($this->wpdb->last_error) {
            throw new \Exception($this->wpdb->last_error);
        }
    }
    
    /**
     * Bulk update availability status for a date range
     * 
     * @param int $post_id Room post ID
     * @param int $start_date Start timestamp
     * @param int $end_date End timestamp
     * @param string $status New status
     * @param float|null $price Optional new price
     * @return int|string Number of updated rows or error message
     */
    public function updateAvailabilityRange($post_id, $start_date, $end_date, $status, $price = null) {
        try {
            $sets = ["status = %s"];
            $params = [$status];
            
            if ($price !== null) {
                $sets[] = "price = %f";
                $params[] = $price;
            }
            
            $sql = "UPDATE {$this->wpdb->prefix}st_room_availability 
                    SET " . implode(', ', $sets) . " 
                    WHERE post_id = %d 
                    AND check_in >= %d 
                    AND check_out <= %d";
            
            $params[] = $post_id;
            $params[] = $start_date;
            $params[] = $end_date;
            
            $query = $this->wpdb->prepare($sql, $params);
            $result = $this->wpdb->query($query);
            
            if ($this->wpdb->last_error) {
                throw new \Exception($this->wpdb->last_error);
            }
            
            return $result;
        } catch (\Exception $ex) {
            error_log('Error in updateAvailabilityRange: ' . $ex->getMessage());
            return 'Error: ' . $ex->getMessage();
        }
    }
    
    /**
     * Generate and insert availability for multiple rooms at once
     * 
     * @param array $room_ids Array of room post IDs
     * @param int $parent_id Parent hotel ID
     * @return int|string 0 on success, error message on failure
     */
    public function insertMultiRoomAvailability(array $room_ids, $parent_id) {
        if (empty($room_ids) || !$parent_id) {
            return 'Invalid room_ids or parent_id';
        }
        
        try {
            $this->wpdb->query('START TRANSACTION');
            
            foreach ($room_ids as $post_id) {
                $result = $this->insertRoomAvailability($post_id, $parent_id);
                if ($result !== 0) {
                    throw new \Exception("Failed to insert availability for room $post_id: $result");
                }
            }
            
            $this->wpdb->query('COMMIT');
            return 0;
        } catch (\Exception $ex) {
            $this->wpdb->query('ROLLBACK');
            error_log('Error in insertMultiRoomAvailability: ' . $ex->getMessage());
            return 'Caught exception: ' . $ex->getMessage();
        }
    }
    
    /**
     * Clear all availability records for a room
     * 
     * @param int $post_id Room post ID
     * @return int|string Number of deleted rows or error message
     */
    public function clearRoomAvailability($post_id) {
        try {
            $sql = "DELETE FROM {$this->wpdb->prefix}st_room_availability WHERE post_id = %d";
            $query = $this->wpdb->prepare($sql, $post_id);
            $result = $this->wpdb->query($query);
            
            if ($this->wpdb->last_error) {
                throw new \Exception($this->wpdb->last_error);
            }
            
            return $result;
        } catch (\Exception $ex) {
            error_log('Error in clearRoomAvailability: ' . $ex->getMessage());
            return 'Error: ' . $ex->getMessage();
        }
    }
}
?>