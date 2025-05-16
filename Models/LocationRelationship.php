<?php
namespace Models;

use Log;

class LocationRelationship
{
    public $post_id;
    public $location_from;
    public $location_to;
    public $post_type;
    public $location_type;

    private $wpdb;
    private $table = 'st_location_relationships';
    private $batch_size = 100; // Configure batch size based on your database capabilities
    private Log $log;

    public function __construct($wpdb, Log $log)
    {
        $this->wpdb = $wpdb;
        $this->log = $log;
    }

    /**
     * Insert location relationships using batch processing
     *
     * @return string Result message
     */
    public function insertLocationRelationship()
    {
        $this->log->info("Locations: " . json_encode($this->location_from));

        if (empty($this->location_from)) {
            $this->log->info("No locations to insert ");
            return 'No locations to insert';
        }

        try {
            // Prepare for batch insert
            $values = [];
            $placeholders = [];
            $query_args = [];

            foreach ($this->location_from as $location) {
                $placeholders[] = "(%d, %s, %s, %s, %s)";

                array_push(
                    $query_args,
                    $this->post_id,
                    $location,
                    $this->location_to,
                    $this->post_type,
                    $this->location_type
                );

                // If we've reached the batch size, execute the query
                if (count($placeholders) >= $this->batch_size) {
                    $this->executeBatchInsert($placeholders, $query_args);
                    $placeholders = [];
                    $query_args = [];
                }
            }

            // Insert any remaining items
            if (!empty($placeholders)) {
                $this->executeBatchInsert($placeholders, $query_args);
            }

            return 'Location relationships inserted successfully';

        } catch (\Exception $ex) {
            $this->log->error("Caught exception: '" . $ex->getMessage());

            return 'Caught exception: ' . $ex->getMessage() . "\n";
        }
    }

    /**
     * Execute a batch insert query
     *
     * @param array $placeholders Array of placeholder strings
     * @param array $query_args Query arguments
     * @throws \Exception When database error occurs
     */
    private function executeBatchInsert($placeholders, $query_args)
    {
        // Prepare the multi-value insert query
        $query = "INSERT INTO {$this->wpdb->prefix}{$this->table} "
               . "(post_id, location_from, location_to, post_type, location_type) "
               . "VALUES " . implode(', ', $placeholders);

        // Prepare and execute the query
        $prepared_query = $this->wpdb->prepare($query, $query_args);
        $this->wpdb->query($prepared_query);

        if ($this->wpdb->last_error) {
            $this->log->error($this->wpdb->last_error);
            throw new \Exception($this->wpdb->last_error);
        }
    }

    /**
     * Set the batch size for insert operations
     *
     * @param int $size Batch size
     * @return $this
     */
    public function setBatchSize($size)
    {
        $this->batch_size = (int)$size > 0 ? (int)$size : 100;
        return $this;
    }
}
