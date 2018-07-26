<?php
/**
 * Description: The MyFamily API.
 * 
 * @author Nitin Patil
 */
?>
<?php
    require_once __DIR__ . '/core/MyFamily.php';
  
    $app = new MyFamily();
    $response = $app->process();
    $status = $response['code'];
    header('Content-Type: application/json');
    http_response_code($status);
    if (isset($response['data'])) {
        echo json_encode($response['data']);
    }
?>
