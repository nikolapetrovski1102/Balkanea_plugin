<?php

namespace Models;

class ProcessAmenity
{
    private $wpdb;
    public array $amenities = [];
    private array $nameMap = [
        '24-hour reception' => '24-hour front desk',
        'Free Wi-Fi' => 'Free WiFi',
    ];
    private string $table = 'term_relationships';
    public int $post_id;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function getAmenities(): array
    {
        return $this->processAmenities($this->amenities['amenities'] ?? [], 'hotel-facilities');
    }

    public function getRoomAmenities(): array
    {
        return $this->processAmenities($this->amenities ?? [], 'room-facilities');
    }

    public function processAmenities(array $amenities, string $taxonomy): array
    {
        // $this->term_taxonomy = $taxonomy;
        if (empty($amenities)) {
            return [];
        }

        $collected_ids = [];
        $prepared_amenities = [];
        $slugs = [];

        // Pre-process all amenities to normalize and generate slugs
        foreach ($amenities as $amenity) {
            $normalized = $this->normalizeAmenity($amenity);
            $slug = str_replace(' ', '-', strtolower($normalized));
            $prepared_amenities[] = $normalized;
            $slugs[] = $slug;
        }

        // Single query to find all existing amenities
        $placeholders = rtrim(str_repeat('(%s,%s,%s),', count($amenities)), ',');
        $values = [];

        foreach ($amenities as $index => $amenity) {
            $values[] = $prepared_amenities[$index];
            $values[] = $slugs[$index];
            $values[] = $taxonomy;
        }

        $sql = "SELECT t.term_id, t.name, t.slug, tt.term_taxonomy_id 
                FROM {$this->wpdb->prefix}terms AS t
                INNER JOIN {$this->wpdb->prefix}term_taxonomy AS tt ON t.term_id = tt.term_id
                WHERE (t.name, t.slug, tt.taxonomy) IN ($placeholders)";
        // $start = microtime(true);
        $query = $this->wpdb->prepare($sql, $values);
        $existing_terms = $this->wpdb->get_results($query, OBJECT_K);
        //   $end = microtime(true);

        // Process results and create missing amenities
        $terms_to_create = [];
        foreach ($amenities as $index => $amenity) {
            $normalized = $prepared_amenities[$index];
            $slug = $slugs[$index];

            $found = false;
            foreach ($existing_terms as $term) {
                if (($term->name === $normalized || $term->slug === $slug)) {
                    $collected_ids[] = (int)$term->term_taxonomy_id;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $terms_to_create[] = $normalized;
            }
        }

        // Bulk create missing amenities
        if (!empty($terms_to_create)) {
            $new_term_ids = $this->createAmenitiesBulk($terms_to_create, $taxonomy);
            $collected_ids = array_merge($collected_ids, $new_term_ids);
        }

        // Bulk insert all collected IDs
        if (!empty($collected_ids)) {
            $this->insertAmenitiesBulk($collected_ids);
        }

        return $collected_ids;
    }

    private function createAmenitiesBulk(array $amenities, string $taxonomy): array
    {
        if (empty($amenities)) {
            return [];
        }

        $created_ids = [];
        $existing_terms = [];

        // First check which terms already exist
        $placeholders = implode(',', array_fill(0, count($amenities), '%s'));
        $sql = "SELECT t.term_id, t.name, t.slug 
                FROM {$this->wpdb->prefix}terms AS t
                INNER JOIN {$this->wpdb->prefix}term_taxonomy AS tt ON t.term_id = tt.term_id
                WHERE t.name IN ($placeholders) AND tt.taxonomy = %s";
        $params = array_merge($amenities, [$taxonomy]);
        // $start = microtime(true);
        $existing_terms = $this->wpdb->get_results($this->wpdb->prepare($sql, $params), OBJECT_K);
        // $end = microtime(true);

        // Prepare terms to insert (excluding existing ones)
        $terms_to_insert = [];
        foreach ($amenities as $amenity) {
            $normalized = $this->normalizeAmenity($amenity);
            if (!isset($existing_terms[$normalized])) {
                $terms_to_insert[$normalized] = sanitize_title($normalized);
            }
        }

        // Insert new terms one by one with proper duplicate checking
        if (!empty($terms_to_insert)) {
            $this->wpdb->query('START TRANSACTION');

            try {
                foreach ($terms_to_insert as $name => $slug) {
                    // Check again right before insert to handle race conditions
                    $existing = $this->wpdb->get_row(
                        $this->wpdb->prepare(
                            "SELECT term_id FROM {$this->wpdb->prefix}terms WHERE name = %s OR slug = %s LIMIT 1",
                            $name,
                            $slug
                        )
                    );

                    if (!$existing) {
                        // Insert term
                        $this->wpdb->insert(
                            $this->wpdb->prefix . 'terms',
                            ['name' => $name, 'slug' => $slug],
                            ['%s', '%s']
                        );

                        $term_id = $this->wpdb->insert_id;

                        // Insert term taxonomy
                        $this->wpdb->insert(
                            $this->wpdb->prefix . 'term_taxonomy',
                            [
                                'term_id' => $term_id,
                                'taxonomy' => $taxonomy,
                                'count' => 0
                            ],
                            ['%d', '%s', '%d']
                        );

                        $created_ids[] = (int)$this->wpdb->insert_id;
                    }
                }

                $this->wpdb->query('COMMIT');
            } catch (Exception $e) {
                $this->wpdb->query('ROLLBACK');
                error_log("Failed to create amenities: " . $e->getMessage());
            }
        }

        // Return IDs of both existing and newly created terms
        $all_term_ids = array_merge(
            array_column($existing_terms, 'term_id'),
            $created_ids
        );

        return array_map('intval', $all_term_ids);
    }

    private function normalizeAmenity(string $name): string
    {
        return $this->nameMap[$name] ?? $name;
    }

    private function insertAmenitiesBulk(array $term_taxonomy_ids): void
    {
        try {
            error_log("Inserting Amenities for post_id: {$this->post_id}\n");

            // Build placeholders and values for the IN clause
            $in_placeholders = implode(',', array_fill(0, count($term_taxonomy_ids), '%d'));

            $sql = "
                SELECT term_taxonomy_id
                FROM {$this->wpdb->prefix}{$this->table}
                WHERE object_id = %d AND term_taxonomy_id IN ($in_placeholders)
            ";
            $prepared_sql = $this->wpdb->prepare($sql, array_merge([$this->post_id], $term_taxonomy_ids));
            $existing = $this->wpdb->get_col($prepared_sql);
            $to_insert = array_diff($term_taxonomy_ids, $existing);

            if (empty($to_insert)) {
                return;
            }

            $insert_values = [];
            $placeholders = [];
            foreach ($to_insert as $id) {
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
        } catch (\Exception $ex) {
            error_log('Error in insertAmenitiesBulk: ' . $ex->getMessage());
        }
    }
}
