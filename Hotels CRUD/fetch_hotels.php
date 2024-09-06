<?php 

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $path = $_SERVER['DOCUMENT_ROOT']; 

    include_once $path . '/wp-load.php';

    global $wpdb;

    $wpdb->show_errors();
    $prefix = $wpdb->prefix;

    if (isset($_GET['delete_post_name'])){

        $post_name = $_GET['delete_post_name'];
    
        $query = $wpdb->prepare("DELETE FROM " . $prefix . "posts WHERE post_name = %s", $post_name);
        $results = $wpdb->query($query);
        
        if ($results === false) {
            echo "Error: " . $wpdb->last_error;
        } else {
            echo "Post deleted successfully";
        }
        exit();
    }

    $query = $wpdb->prepare("SELECT * FROM " . $prefix . "posts WHERE post_type = 'st_hotel' AND post_author = %d", 6961);

    $results = $wpdb->get_results($query);

    foreach ($results as $hotel) {
        $output .= <<<HTML
        <div class="item st-border-radius m-5">
                <div class="row align-items-center align-items-stretch">
                    <div class="col-12 col-sm-12 col-md-12 col-lg-4">
                        <div class="image">
                            <img src="https://staging.balkanea.com/wp-content/uploads/RateHawk/{$hotel->post_name}/{$hotel->post_name}-1.jpg" alt="{$hotel->post_title}" class="img-fluid img-full st-hover-grow">
                        </div>
                    </div>
                    <div class="col-12 col-sm-12 col-md-12 col-lg-8">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-12 col-lg-7">
                                <div class="item-infor">
                                    <div class="st-border-right">
                                        <h2 class="heading">
                                            <a href="https://staging.balkanea.com/hotel/{$hotel->post_name}" target="_blank" class="heading-title">
                                                {$hotel->post_title}
                                            </a>
                                        </h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-12 col-lg-5">
                                <a "href="https://staging.balkanea.com/wp-plugin/Hotels_CRUD/fetch_hotels.php?delete_post_name={$hotel->post_name}" class="show-detail btn-v2 btn-danger">Delete</a>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
        HTML;
    }

    echo $output;
    

?>