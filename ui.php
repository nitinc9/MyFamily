<?php
/**
 * Description: The MyFamily UI template. This file should be used for URL and form targets to render the main UI content.
 * 
 * @author Nitin Patil
 */
?>
<?php
    require_once __DIR__ . '/core/MyFamily.php';
  
    $app = new MyFamily();
    $response = $app->process();
    // Handle error
    if ($response && isset($response['code'])) {
        http_response_code($response['code']);
        if (isset($response['data'])) {
            echo json_encode($response['data']);
        }
    }
?>
