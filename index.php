<?php
// index.php
session_start();

// Include the StockController
require_once 'controllers/StockController.php';


/**
 * Simple routing based on the "action" query parameter.
 */
$action = isset($_GET['action']) ? $_GET['action'] : 'index';
$controller = new StockController();

if ($action === 'index') {
    $controller->index();
} 
elseif ($action === 'view') {
    $symbol = isset($_GET['symbol']) ? $_GET['symbol'] : null;
    // Pass timeFrame if provided; default to '1D'
    $timeFrame = isset($_GET['timeFrame']) ? $_GET['timeFrame'] : '1D';
    $controller->view($symbol, $timeFrame);
} 
elseif ($action === 'add') {
    // For add/replace, we expect a slot parameter.
    $slot = isset($_GET['slot']) ? intval($_GET['slot']) : null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Optionally, also get timeFrame from POST if needed.
        $timeFrame = isset($_POST['timeFrame']) ? $_POST['timeFrame'] : '1D';
        $controller->add($_POST, $slot, $timeFrame);
    } else {
        $controller->showAddForm($slot);
    }
} 
elseif ($action === 'remove') {
    $slot = isset($_GET['slot']) ? intval($_GET['slot']) : null;
    $controller->remove($slot);
} 
else {
    echo "Invalid action";
}
