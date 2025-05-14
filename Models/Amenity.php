<?php

namespace Models;

class Amenity
{
    private $wpdb;
    private string $table = 'term_relationships';

    public array $amenities = [];
    public string $group_name = '';
    public int $post_id = 0;

    // Cache amenity term ids to avoid redundant lookups
    private array $amenityCache = [];
    
    // Batch size for bulk operations
    private const BATCH_SIZE = 50;

    private array $nameMap = [
        '24-hour reception' => '24-hour front desk',
        'Free Wi-Fi' => 'Free WiFi',
    ];

    // Logging configuration
    private bool $enableLogging = true;
    private string $logFile = '';

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
        // Set default log file path to WordPress uploads directory
        $this->logFile = WP_CONTENT_DIR . '/uploads/amenity_logs.txt';
    }

    /**
     * Enable or disable logging
     */
    public function setLogging(bool $enable): void
    {
        $this->enableLogging = $enable;
    }

    /**
     * Set custom log file path
     */
    public function setLogFile(string $path): void
    {
        $this->logFile = $path;
    }

    /**
     * Write log message
     */
    private function log(string $message): void
    {
        if (!$this->enableLogging) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}" . PHP_EOL;
        
        try {
            file_put_contents($this->logFile, $logEntry, FILE_APPEND);
        } catch (\Exception $ex) {
            error_log("Failed to write to amenity log file: " . $ex->getMessage());
        }
    }

    /**
     * Get hotel amenities with optimized processing
     */
    public function getAmenities(): array
    {
        $this->log("Getting hotel amenities for post ID: {$this->post_id}");
        return $this->processAmenities($this->amenities['amenities'] ?? [], 'hotel-facilities');
    }

    /**
     * Get room amenities with optimized processing
     */
    public function getRoomAmenities(): array
    {
        $this->log("Getting room amenities for post ID: {$this->post_id}");
        return $this->processAmenities($this->amenities ?? [], 'room-facilities');
    }

    /**
     * Normalize amenity names using the name map
     */
    private function normalizeAmenity(string $name): string
    {
        $normalized = $this->nameMap[$name] ?? $name;
        if ($normalized !== $name) {
            $this->log("Normalized amenity name from '{$name}' to '{$normalized}'");
        }
        return $normalized;
    }

    /**
     * Get term ID for an amenity, first checking cache
     */
    private function getAmenityTermId(string $name, string $slug, string $taxonomy): ?int
    {
        // Generate a cache key
        $cacheKey = $taxonomy . ':' . $slug;
        
        // Check if we already looked up this term
        if (isset($this->amenityCache[$cacheKey])) {
            $this->log("Cache hit for amenity term: {$name} ({$taxonomy})");
            return $this->amenityCache[$cacheKey];
        }
        
        // Look up the term in database
        $sql = "
            SELECT tt.term_taxonomy_id 
            FROM {$this->wpdb->prefix}terms AS t
            INNER JOIN {$this->wpdb->prefix}term_taxonomy AS tt ON t.term_id = tt.term_id
            WHERE (t.name = %s OR t.slug = %s) AND tt.taxonomy = %s
            LIMIT 1
        ";

        $query = $this->wpdb->prepare($sql, $name, $slug, $taxonomy);
        $term = $this->wpdb->get_row($query);
        
        if ($term) {
            // Cache the result
            $this->amenityCache[$cacheKey] = (int) $term->term_taxonomy_id;
            $this->log("Found existing amenity term: {$name} ({$taxonomy}) with ID: {$term->term_taxonomy_id}");
            return $this->amenityCache[$cacheKey];
        }
        
        $this->log("Amenity term not found: {$name} ({$taxonomy})");
        return null;
    }
    
    /**
     * Prefetch amenity terms in bulk to reduce database queries
     */
    private function prefetchAmenities(array $amenities, string $taxonomy): void
    {
        if (empty($amenities)) {
            return;
        }
        
        $this->log("Prefetching " . count($amenities) . " amenity terms for taxonomy: {$taxonomy}");
        
        // Prepare normalized names and slugs
        $names = [];
        $slugs = [];
        $cacheKeys = [];
        
        foreach ($amenities as $amenity) {
            $normalized = $this->normalizeAmenity($amenity);
            $slug = str_replace(' ', '-', strtolower($normalized));
            
            $names[] = $normalized;
            $slugs[] = $slug;
            $cacheKeys[$normalized] = $taxonomy . ':' . $slug;
        }
        
        // Build a query that finds terms by either name or slug
        $nameParams = implode(',', array_fill(0, count($names), '%s'));
        $slugParams = implode(',', array_fill(0, count($slugs), '%s'));
        
        $sql = "
            SELECT t.name, t.slug, tt.taxonomy, tt.term_taxonomy_id 
            FROM {$this->wpdb->prefix}terms AS t
            INNER JOIN {$this->wpdb->prefix}term_taxonomy AS tt ON t.term_id = tt.term_id
            WHERE (t.name IN ($nameParams) OR t.slug IN ($slugParams)) AND tt.taxonomy = %s
        ";
        
        $query = $this->wpdb->prepare($sql, array_merge($names, $slugs, [$taxonomy]));
        $terms = $this->wpdb->get_results($query);
        
        $this->log("Prefetch found " . count($terms) . " existing amenity terms");
        
        // Cache all the found terms
        foreach ($terms as $term) {
            $key = $term->taxonomy . ':' . $term->slug;
            $this->amenityCache[$key] = (int) $term->term_taxonomy_id;
            
            // Also cache by name for faster lookups
            $nameKey = $term->taxonomy . ':' . str_replace(' ', '-', strtolower($term->name));
            $this->amenityCache[$nameKey] = (int) $term->term_taxonomy_id;
        }
    }

    /**
     * Process amenities with optimized batch operations
     */
    private function processAmenities(array $amenities, string $taxonomy): array
    {
        if (empty($amenities)) return [];
        
        $this->log("Processing " . count($amenities) . " amenities for taxonomy: {$taxonomy}");
        
        $collected_ids = [];
        $to_create = [];
        
        // Prefetch existing amenities to reduce individual queries
        $this->prefetchAmenities($amenities, $taxonomy);
        
        // Process each amenity
        foreach ($amenities as $amenity) {
            $normalized = $this->normalizeAmenity($amenity);
            $slug = str_replace(' ', '-', strtolower($normalized));
            
            // Try to get from cache or database
            $term_id = $this->getAmenityTermId($normalized, $slug, $taxonomy);
            
            if ($term_id) {
                $collected_ids[] = $term_id;
            } else {
                $to_create[] = [
                    'name' => $normalized,
                    'slug' => $slug,
                    'taxonomy' => $taxonomy
                ];
            }
        }
        
        // Create any new amenity terms needed
        if (!empty($to_create)) {
            $this->log("Creating " . count($to_create) . " new amenity terms");
            $new_ids = $this->createAmenitiesBulk($to_create);
            $collected_ids = array_merge($collected_ids, $new_ids);
        }
        
        // Insert term relationships in batches
        if (!empty($collected_ids)) {
            $this->log("Inserting " . count($collected_ids) . " amenity relationships for post ID: {$this->post_id}");
            $this->insertAmenitiesBulk($collected_ids);
        }
        
        return $collected_ids;
    }

    /**
     * Create multiple amenities in bulk
     */
    private function createAmenitiesBulk(array $amenities): array
    {
        if (empty($amenities)) {
            return [];
        }
        
        $new_ids = [];
        
        try {
            // Use transactions for bulk operations
            $this->wpdb->query("START TRANSACTION");
            $this->log("Starting transaction for bulk amenity creation");
            
            foreach ($amenities as $amenity) {
                // Insert term
                $term_sql = "
                    INSERT INTO {$this->wpdb->prefix}terms (name, slug, term_group)
                    VALUES (%s, %s, %d)
                ";
                $term_query = $this->wpdb->prepare($term_sql, $amenity['name'], $amenity['slug'], 0);
                $this->wpdb->query($term_query);
                
                if ($this->wpdb->last_error) {
                    throw new \Exception($this->wpdb->last_error);
                }
                
                $term_id = $this->wpdb->insert_id;
                $this->log("Created term: {$amenity['name']} (ID: {$term_id})");
                
                // Insert taxonomy
                $tax_sql = "
                    INSERT INTO {$this->wpdb->prefix}term_taxonomy (term_id, taxonomy, description, parent, count)
                    VALUES (%d, %s, '', 0, 0)
                ";
                $tax_query = $this->wpdb->prepare($tax_sql, $term_id, $amenity['taxonomy']);
                $this->wpdb->query($tax_query);
                
                if ($this->wpdb->last_error) {
                    throw new \Exception($this->wpdb->last_error);
                }
                
                $term_taxonomy_id = $this->wpdb->insert_id;
                $this->log("Created term taxonomy: {$amenity['name']} ({$amenity['taxonomy']}) with ID: {$term_taxonomy_id}");
                
                $new_ids[] = $term_taxonomy_id;
                
                // Update cache
                $cacheKey = $amenity['taxonomy'] . ':' . $amenity['slug'];
                $this->amenityCache[$cacheKey] = $term_taxonomy_id;
            }
            
            $this->wpdb->query("COMMIT");
            $this->log("Committed transaction for bulk amenity creation");
            
        } catch (\Exception $ex) {
            $this->wpdb->query("ROLLBACK");
            $this->log("ERROR: Rolled back transaction for bulk amenity creation: " . $ex->getMessage());
            error_log("Error in createAmenitiesBulk: " . $ex->getMessage());
        }
        
        return $new_ids;
    }

    /**
     * Create a single amenity term (optimized version)
     */
    private function createAmenity(string $name, string $taxonomy): ?int
    {
        $this->log("Creating single amenity: {$name} ({$taxonomy})");
        
        try {
            $name_clean = str_replace('-', ' ', $name);
            $slug = sanitize_title($name_clean);
            
            // Check if it already exists first
            $check_sql = "
                SELECT tt.term_taxonomy_id 
                FROM {$this->wpdb->prefix}terms AS t
                INNER JOIN {$this->wpdb->prefix}term_taxonomy AS tt ON t.term_id = tt.term_id
                WHERE (t.name = %s OR t.slug = %s) AND tt.taxonomy = %s
                LIMIT 1
            ";
            $check_query = $this->wpdb->prepare($check_sql, $name_clean, $slug, $taxonomy);
            $existing = $this->wpdb->get_row($check_query);
            
            if ($existing) {
                $this->log("Found existing amenity: {$name} with ID: {$existing->term_taxonomy_id}");
                return (int) $existing->term_taxonomy_id;
            }

            // Start transaction for atomicity
            $this->wpdb->query("START TRANSACTION");
            $this->log("Starting transaction for single amenity creation");
            
            // Insert term
            $term_sql = "
                INSERT INTO {$this->wpdb->prefix}terms (name, slug, term_group)
                VALUES (%s, %s, %d)
            ";
            $term_query = $this->wpdb->prepare($term_sql, $name_clean, $slug, 0);
            $this->wpdb->query($term_query);
            
            if ($this->wpdb->last_error) {
                throw new \Exception($this->wpdb->last_error);
            }
            
            $term_id = $this->wpdb->insert_id;
            $this->log("Created term: {$name_clean} (ID: {$term_id})");
            
            // Insert taxonomy
            $tax_sql = "
                INSERT INTO {$this->wpdb->prefix}term_taxonomy (term_id, taxonomy, description, parent, count)
                VALUES (%d, %s, '', 0, 0)
            ";
            $tax_query = $this->wpdb->prepare($tax_sql, $term_id, $taxonomy);
            $this->wpdb->query($tax_query);
            
            if ($this->wpdb->last_error) {
                throw new \Exception($this->wpdb->last_error);
            }
            
            $term_taxonomy_id = $this->wpdb->insert_id;
            $this->log("Created term taxonomy: {$name_clean} ({$taxonomy}) with ID: {$term_taxonomy_id}");
            
            // Cache the new term
            $cacheKey = $taxonomy . ':' . $slug;
            $this->amenityCache[$cacheKey] = $term_taxonomy_id;
            
            $this->wpdb->query("COMMIT");
            $this->log("Committed transaction for single amenity creation");
            
            return $term_taxonomy_id;
            
        } catch (\Exception $ex) {
            $this->wpdb->query("ROLLBACK");
            $this->log("ERROR: Rolled back transaction for single amenity creation: " . $ex->getMessage());
            error_log("Error in createAmenity: " . $ex->getMessage());
            return null;
        }
    }

    /**
     * Insert amenity relationships in bulk, checking for existing ones first
     */
    private function insertAmenitiesBulk(array $term_taxonomy_ids): void
    {
        if (empty($term_taxonomy_ids) || $this->post_id <= 0) {
            $this->log("Skipping bulk insertion - empty term IDs or invalid post ID: {$this->post_id}");
            return;
        }
        
        try {
            // Get existing relationships to avoid duplicates
            $in_placeholders = implode(',', array_fill(0, count($term_taxonomy_ids), '%d'));
            $sql = "
                SELECT term_taxonomy_id
                FROM {$this->wpdb->prefix}{$this->table}
                WHERE object_id = %d AND term_taxonomy_id IN ($in_placeholders)
            ";
            $prepared_sql = $this->wpdb->prepare($sql, array_merge([$this->post_id], $term_taxonomy_ids));
            $existing = $this->wpdb->get_col($prepared_sql);
            
            $this->log("Found " . count($existing) . " existing amenity relationships for post ID: {$this->post_id}");
            
            // Find relationships that need to be inserted
            $to_insert = array_diff($term_taxonomy_ids, $existing);
            
            if (empty($to_insert)) {
                $this->log("No new amenity relationships to insert for post ID: {$this->post_id}");
                return;
            }
            
            $this->log("Inserting " . count($to_insert) . " new amenity relationships for post ID: {$this->post_id}");
            
            // Process in batches to avoid overly large queries
            $batches = array_chunk($to_insert, self::BATCH_SIZE);
            
            foreach ($batches as $batch_index => $batch) {
                $insert_values = [];
                $placeholders = [];
                
                foreach ($batch as $id) {
                    $placeholders[] = "(%d, %d, %d)";
                    array_push($insert_values, $this->post_id, $id, 0);
                }
                
                $insert_sql = "INSERT INTO {$this->wpdb->prefix}{$this->table} (object_id, term_taxonomy_id, term_order) VALUES ";
                $insert_sql .= implode(', ', $placeholders);
                
                $final_sql = $this->wpdb->prepare($insert_sql, $insert_values);
                $this->wpdb->query($final_sql);
                
                if ($this->wpdb->last_error) {
                    throw new \Exception($this->wpdb->last_error);
                }
                
                $this->log("Successfully inserted batch " . ($batch_index + 1) . " of amenity relationships (" . count($batch) . " items)");
            }
            
        } catch (\Exception $ex) {
            $this->log("ERROR: Failed to insert amenity relationships: " . $ex->getMessage());
            error_log('Error in insertAmenitiesBulk: ' . $ex->getMessage());
        }
    }

    /**
     * Insert a single amenity relationship (optimized)
     */
    public function insertAmenity(int $term_taxonomy_id): string
    {
        if ($term_taxonomy_id <= 0 || $this->post_id <= 0) {
            $this->log("INVALID_ID: Attempted to insert invalid term ID: {$term_taxonomy_id} for post ID: {$this->post_id}");
            return 'INVALID_ID';
        }
        
        try {
            // Check if relationship exists
            $sql = "
                SELECT EXISTS(
                    SELECT 1 FROM {$this->wpdb->prefix}{$this->table}
                    WHERE object_id = %d AND term_taxonomy_id = %d
                ) as relationship_exists
            ";
            $query = $this->wpdb->prepare($sql, $this->post_id, $term_taxonomy_id);
            $exists = (bool) $this->wpdb->get_var($query);
            
            if ($exists) {
                $this->log("Amenity relationship already exists - post ID: {$this->post_id}, term ID: {$term_taxonomy_id}");
            }
            
            // Insert only if needed
            if (!$exists) {
                $insert_sql = "
                    INSERT INTO {$this->wpdb->prefix}{$this->table}
                    (object_id, term_taxonomy_id, term_order)
                    VALUES (%d, %d, %d)
                ";
                $query = $this->wpdb->prepare($insert_sql, $this->post_id, $term_taxonomy_id, 0);
                $this->wpdb->query($query);
                
                if ($this->wpdb->last_error) {
                    throw new \Exception($this->wpdb->last_error);
                }
                
                $this->log("Successfully inserted amenity relationship - post ID: {$this->post_id}, term ID: {$term_taxonomy_id}");
            }
            
            return 'OK';
        } catch (\Exception $ex) {
            $this->log("ERROR: Failed to insert amenity relationship - post ID: {$this->post_id}, term ID: {$term_taxonomy_id} - " . $ex->getMessage());
            error_log('Error in insertAmenity: ' . $ex->getMessage());
            return 'FAILED';
        }
    }
    
    /**
     * Bulk insert multiple amenity relationships in a single query
     */
    public function insertAmenities(array $term_taxonomy_ids): string
    {
        if (empty($term_taxonomy_ids) || $this->post_id <= 0) {
            $this->log("NO_AMENITIES: Attempted to insert empty amenity list for post ID: {$this->post_id}");
            return 'NO_AMENITIES';
        }
        
        try {
            $this->log("Inserting " . count($term_taxonomy_ids) . " amenity relationships for post ID: {$this->post_id}");
            $this->insertAmenitiesBulk($term_taxonomy_ids);
            $this->log("Successfully inserted all amenity relationships for post ID: {$this->post_id}");
            return 'OK';
        } catch (\Exception $ex) {
            $this->log("ERROR: Failed to insert amenity relationships for post ID: {$this->post_id} - " . $ex->getMessage());
            error_log('Error in insertAmenities: ' . $ex->getMessage());
            return 'FAILED';
        }
    }
}