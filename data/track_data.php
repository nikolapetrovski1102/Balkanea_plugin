<?php

    if (!file_exists('region_state.json')) {
            file_put_contents('region_state.json', json_encode(['last_index' => -1]));
        }
        
        $state = json_decode(file_get_contents('region_state.json'), true);

?>