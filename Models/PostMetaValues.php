<?php

namespace Models;

class PostMetaValues
{
    private $wpdb;
    private $table = 'postmeta';

    /** @var int */
    public int $post_id;
    /** @var array */
    public array $meta_values;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }
    
    // Create Meta Values
    public function create(): bool
    {
        try {
            foreach ($this->meta_values as $meta_key => $meta_value) {
                $data = [
                    'post_id' => $this->post_id,
                    'meta_key' => $meta_key,
                    'meta_value' => $meta_value,
                ];

                $format = ['%d', '%s', '%s'];

                $result = $this->wpdb->insert($this->wpdb->prefix . $this->table, $data, $format);

                if ($this->wpdb->last_error) {
                    throw new \Exception($this->wpdb->last_error);
                }

                if ($result === false) {
                    return false;
                }
            }
            return true;
        } catch (\Exception $ex) {
            echo 'Caught exception: ', $ex->getMessage(), "\n";
            return false;
        }
    }

    // Read Meta Value
    public function read($meta_key): ?\stdClass
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM " . $this->wpdb->prefix . $this->table . " WHERE post_id = %d AND meta_key = %s",
            $this->post_id,
            $meta_key
        );
        return $this->wpdb->get_row($query);
    }

    // Update Meta Values
    public function update(): bool
    {
        
        echo "Passed meta_values: <br>";
        print_r($this->meta_values, true);
        
        try {
            foreach ($this->meta_values as $meta_key => $meta_value) {
                
                if ($meta_key == 'metapolicy_struct'){
                    $data = [
                        'post_id' => $this->post_id,
                        'meta_key' => $meta_key,
                        'meta_value' => $meta_value,
                    ];

                    $format = ['%d', '%s', '%s'];
    
                    $result = $this->wpdb->insert($this->wpdb->prefix . $this->table, $data, $format);
                    
                    if ($this->wpdb->last_error) {
                       throw new \Exception($this->wpdb->last_error);
                    }
                }
                
                $data = ['meta_value' => $meta_value];
                $where = [
                    'post_id' => $this->post_id,
                    'meta_key' => $meta_key
                ];
                $format = ['%s'];
                $where_format = ['%d', '%s'];

                $result = $this->wpdb->update($this->wpdb->prefix . $this->table, $data, $where, $format, $where_format);
                if ($this->wpdb->last_error) {
                    throw new \Exception($this->wpdb->last_error);
                }

                if ($result === false) {
                    return false;
                }
            }
            return true;
        } catch (\Exception $ex) {
            echo 'Caught exception: ', $ex->getMessage(), "\n";
            return false;
        }
    }

    // Delete Meta Value
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
            echo 'Caught exception: ', $ex->getMessage(), "\n";
            return false;
        }
    }

    public function get(): ?\stdClass
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM " . $this->wpdb->prefix . $this->table . " WHERE post_id = %d",
            $this->post_id
        );

        $result = $this->wpdb->get_row($query);

        if ($this->wpdb->last_error) {
            throw new \Exception($this->wpdb->last_error);
        }

        return $result;
    }
}


?>