<?php

namespace Models;

use Log;

class PostMetaValues
{
    private $wpdb;
    private $table = 'postmeta';

    /** @var int */
    public int $post_id;
    /** @var array */
    public array $meta_values;

    // Cache for existing meta keys to reduce database queries
    private array $existingMetaCache = [];
    private Log $log;

    public function __construct($wpdb, Log $log)
    {
        $this->wpdb = $wpdb;
        $this->log = $log;
    }

    /**
     * Bulk insert meta values - much faster than individual inserts
     */
    public function create(): bool
    {
        try {
            $this->log->info("Bulk insert meta values");
            if (empty($this->meta_values)) {
                return true;
            }

            // Prepare for bulk insert
            $values = [];
            $placeholders = [];
            $query_args = [];

            foreach ($this->meta_values as $meta_key => $meta_value) {
                $placeholders[] = "(%d, %s, %s)";
                array_push($query_args, $this->post_id, $meta_key, $meta_value);
            }

            // Construct bulk insert query
            $sql = "INSERT INTO {$this->wpdb->prefix}{$this->table} (post_id, meta_key, meta_value) VALUES ";
            $sql .= implode(', ', $placeholders);

            $prepared_query = $this->wpdb->prepare($sql, $query_args);
            $result = $this->wpdb->query($prepared_query);

            if ($this->wpdb->last_error) {
                throw new \Exception($this->wpdb->last_error);
            }

            return $result !== false;
        } catch (\Exception $ex) {
            $this->log->error('Error in PostMetaValues::create: ' . $ex->getMessage());
            return false;
        }
    }

    /**
     * Read a single meta value
     */
    public function read($meta_key): ?\stdClass
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}{$this->table} WHERE post_id = %d AND meta_key = %s LIMIT 1",
            $this->post_id,
            $meta_key
        );
        return $this->wpdb->get_row($query);
    }

    /**
     * Check if a meta key exists for this post
     */
    private function metaExists($meta_key): bool
    {
        // Check cache first
        if (isset($this->existingMetaCache[$meta_key])) {
            return $this->existingMetaCache[$meta_key];
        }

        $query = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}{$this->table} WHERE post_id = %d AND meta_key = %s",
            $this->post_id,
            $meta_key
        );
        $exists = (int)$this->wpdb->get_var($query) > 0;

        // Cache result
        $this->existingMetaCache[$meta_key] = $exists;

        return $exists;
    }

    /**
     * Prefetch existence of all meta keys to reduce individual queries
     */
    private function prefetchExistingMeta(): void
    {
        if (empty($this->meta_values)) {
            return;
        }

        $meta_keys = array_keys($this->meta_values);
        $placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));

        $query = $this->wpdb->prepare(
            "SELECT meta_key FROM {$this->wpdb->prefix}{$this->table} 
             WHERE post_id = %d AND meta_key IN ($placeholders)",
            array_merge([$this->post_id], $meta_keys)
        );

        $existing_keys = $this->wpdb->get_col($query);

        // Initialize all requested keys as non-existent
        foreach ($meta_keys as $key) {
            $this->existingMetaCache[$key] = false;
        }

        // Mark keys that do exist
        foreach ($existing_keys as $key) {
            $this->existingMetaCache[$key] = true;
        }
    }

    /**
     * Update meta values efficiently
     */
    public function update(): bool
    {
        try {
            if (empty($this->meta_values)) {
                return true;
            }

            // Prefetch which meta keys already exist
            $this->prefetchExistingMeta();

            // Separate into inserts and updates
            $inserts = [];
            $updates = [];

            foreach ($this->meta_values as $meta_key => $meta_value) {
                if ($this->existingMetaCache[$meta_key]) {
                    $updates[$meta_key] = $meta_value;
                } else {
                    $inserts[$meta_key] = $meta_value;
                }
            }

            // Process inserts using bulk operation
            if (!empty($inserts)) {
                $insert_placeholders = [];
                $insert_values = [];

                foreach ($inserts as $meta_key => $meta_value) {
                    $insert_placeholders[] = "(%d, %s, %s)";
                    array_push($insert_values, $this->post_id, $meta_key, $meta_value);
                }

                $insert_sql = "INSERT INTO {$this->wpdb->prefix}{$this->table} (post_id, meta_key, meta_value) VALUES ";
                $insert_sql .= implode(', ', $insert_placeholders);

                $prepared_insert = $this->wpdb->prepare($insert_sql, $insert_values);
                $insert_result = $this->wpdb->query($prepared_insert);

                if ($this->wpdb->last_error) {
                    throw new \Exception("Insert error: " . $this->wpdb->last_error);
                }
            }

            // Process updates with case statement for better performance
            if (!empty($updates)) {
                // Use a CASE statement for efficient multiple updates
                $case_stmt = "UPDATE {$this->wpdb->prefix}{$this->table} SET meta_value = CASE meta_key ";
                $where_keys = [];
                $values = [];

                foreach ($updates as $meta_key => $meta_value) {
                    $case_stmt .= $this->wpdb->prepare("WHEN %s THEN %s ", $meta_key, $meta_value);
                    $where_keys[] = $meta_key;
                    $values[] = $meta_key;
                }

                $case_stmt .= "END WHERE post_id = %d AND meta_key IN (";
                $case_stmt .= implode(',', array_fill(0, count($where_keys), '%s'));
                $case_stmt .= ")";

                $prepared_update = $this->wpdb->prepare($case_stmt, array_merge([$this->post_id], $values));
                $update_result = $this->wpdb->query($prepared_update);

                if ($this->wpdb->last_error) {
                    throw new \Exception("Update error: " . $this->wpdb->last_error);
                }
            }

            return true;
        } catch (\Exception $ex) {
            $this->log->error('Error in PostMetaValues::update: ' . $ex->getMessage());
            return false;
        }
    }

    /**
     * Delete a meta value
     */
    public function delete($meta_key): bool
    {
        $where = [
            'post_id' => $this->post_id,
            'meta_key' => $meta_key
        ];
        $where_format = ['%d', '%s'];

        try {
            $result = $this->wpdb->delete($this->wpdb->prefix . $this->table, $where, $where_format);
            if ($this->wpdb->last_error) {
                throw new \Exception($this->wpdb->last_error);
            }
            return $result !== false;
        } catch (\Exception $ex) {
            $this->log->error('Error in PostMetaValues::delete: ' . $ex->getMessage());
            return false;
        }
    }

    /**
     * Bulk delete multiple meta keys at once
     */
    public function bulkDelete(array $meta_keys): bool
    {
        if (empty($meta_keys)) {
            return true;
        }

        try {
            $placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));
            $query = $this->wpdb->prepare(
                "DELETE FROM {$this->wpdb->prefix}{$this->table} WHERE post_id = %d AND meta_key IN ($placeholders)",
                array_merge([$this->post_id], $meta_keys)
            );

            $result = $this->wpdb->query($query);

            if ($this->wpdb->last_error) {
                throw new \Exception($this->wpdb->last_error);
            }

            return $result !== false;
        } catch (\Exception $ex) {
            $this->log->error('Error in PostMetaValues::bulkDelete: ' . $ex->getMessage());
            return false;
        }
    }

    /**
     * Get all meta values for this post
     */
    public function getAll(): array
    {
        $query = $this->wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$this->wpdb->prefix}{$this->table} WHERE post_id = %d",
            $this->post_id
        );

        $results = $this->wpdb->get_results($query);

        if ($this->wpdb->last_error) {
            $this->log->error($this->wpdb->last_error);
            throw new \Exception($this->wpdb->last_error);
        }

        // Convert to associative array
        $meta_values = [];
        foreach ($results as $row) {
            $meta_values[$row->meta_key] = $row->meta_value;
        }

        return $meta_values;
    }

    /**
     * Get specific meta keys for this post in a single query
     */
    public function getSpecific(array $meta_keys): array
    {
        if (empty($meta_keys)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));
        $query = $this->wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$this->wpdb->prefix}{$this->table} 
             WHERE post_id = %d AND meta_key IN ($placeholders)",
            array_merge([$this->post_id], $meta_keys)
        );

        $results = $this->wpdb->get_results($query);

        if ($this->wpdb->last_error) {
            throw new \Exception($this->wpdb->last_error);
        }

        // Convert to associative array
        $meta_values = [];
        foreach ($results as $row) {
            $meta_values[$row->meta_key] = $row->meta_value;
        }

        return $meta_values;
    }
}
