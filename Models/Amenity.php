<?php

namespace Models;

class Amenity
{
    private $wpdb;
    private string $table = 'term_relationships';

    public array $amenities = [];
    public string $group_name = '';
    public int $post_id = 0;

    private array $nameMap = [
        '24-hour reception' => '24-hour front desk',
        'Free Wi-Fi' => 'Free WiFi',
    ];

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

    private function normalizeAmenity(string $name): string
    {
        return $this->nameMap[$name] ?? $name;
    }

    private function processAmenities(array $amenities, string $taxonomy): array
    {
        $collected_ids = [];

        if (empty($amenities)) return [];

        foreach ($amenities as $amenity) {
            $normalized = $this->normalizeAmenity($amenity);
            $slug = str_replace(' ', '-', strtolower($normalized));

            $sql = "
                SELECT tt.term_taxonomy_id 
                FROM {$this->wpdb->prefix}terms AS t
                INNER JOIN {$this->wpdb->prefix}term_taxonomy AS tt ON t.term_id = tt.term_id
                WHERE (t.name = %s OR t.slug = %s) AND tt.taxonomy = %s
                LIMIT 1
            ";

            $query = $this->wpdb->prepare($sql, $normalized, $slug, $taxonomy);
            $term = $this->wpdb->get_row($query);

            if ($term) {
                $collected_ids[] = (int) $term->term_taxonomy_id;
            } else {
                $new_term_id = $this->createAmenity($normalized, $taxonomy);
                if ($new_term_id !== null) {
                    $collected_ids[] = $new_term_id;
                }
            }
        }

        if (!empty($collected_ids)) {
            $this->insertAmenitiesBulk($collected_ids);
        }

        return $collected_ids;
    }

    private function createAmenity(string $name, string $taxonomy): ?int
    {
        try {
            error_log("Creating Amenity: $name");
            $name_clean = str_replace('-', ' ', $name);
            $slug = sanitize_title($name_clean); // Safe slug generation

            $term_sql = "
                INSERT INTO {$this->wpdb->prefix}terms (name, slug, term_group)
                VALUES (%s, %s, %d)
            ";
            $term_query = $this->wpdb->prepare($term_sql, $name_clean, $slug, 0);
            $this->wpdb->query($term_query);
            if ($this->wpdb->last_error) throw new \Exception($this->wpdb->last_error);

            $term_id = $this->wpdb->insert_id;

            $tax_sql = "
                INSERT INTO {$this->wpdb->prefix}term_taxonomy (term_id, taxonomy, description, parent, count)
                VALUES (%d, %s, '', 0, 0)
            ";
            $tax_query = $this->wpdb->prepare($tax_sql, $term_id, $taxonomy);
            $this->wpdb->query($tax_query);
            if ($this->wpdb->last_error) throw new \Exception($this->wpdb->last_error);

            return $this->wpdb->insert_id;

        } catch (\Exception $ex) {
            error_log("Error in createAmenity: " . $ex->getMessage());
            return null;
        }
    }

    private function insertAmenitiesBulk(array $term_taxonomy_ids): void
    {
        try {
            error_log("Inserting Amenities for post_id: {$this->post_id}");

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

            if (empty($to_insert)) return;

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

    public function insertAmenity(int $term_taxonomy_id): string
    {
        try {
            $sql = "
                SELECT COUNT(*) FROM {$this->wpdb->prefix}{$this->table}
                WHERE object_id = %d AND term_taxonomy_id = %d
            ";
            $query = $this->wpdb->prepare($sql, $this->post_id, $term_taxonomy_id);
            $exists = (int) $this->wpdb->get_var($query);

            if ($exists === 0) {
                $insert_sql = "
                    INSERT INTO {$this->wpdb->prefix}{$this->table}
                    (object_id, term_taxonomy_id, term_order)
                    VALUES (%d, %d, %d)
                ";
                $query = $this->wpdb->prepare($insert_sql, $this->post_id, $term_taxonomy_id, 0);
                $this->wpdb->query($query);
            }

            return 'OK';
        } catch (\Exception $ex) {
            error_log('Error in insertAmenity: ' . $ex->getMessage());
            return 'FAILED';
        }
    }
}
