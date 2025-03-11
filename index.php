<?php
// index.php


// Start the session to persist user data across requests.
session_start();


// Include the StockController class which handles all stock-related actions.
require_once 'controllers/StockController.php';


// Determine the action to perform based on the query parameter "action".
// If no action is provided, default to 'index'.
$action = isset($_GET['action']) ? $_GET['action'] : 'index';


// Create a singleton instance of StockController using the getInstance() method.
// This ensures that only one instance exists during the application's lifecycle.
$controller = StockController::getInstance();


// Handle different actions using a switch statement.
switch ($action) {
    case 'index':// Case to display the stocks grid.
        $controller->index();
        break;
    
    
    case 'view':// Case to display details for a single stock.
        // Get the stock symbol from the query parameters.
        $symbol = isset($_GET['symbol']) ? $_GET['symbol'] : null;
        // Get the selected time frame from the query parameters; default is '1D'.
        $timeFrame = isset($_GET['timeFrame']) ? $_GET['timeFrame'] : '1D';
        $controller->view($symbol, $timeFrame);
        break;
    
    
    case 'add':// Case to add or replace a stock.
        // Get the slot index from query parameters (converted to integer).
        $slot = isset($_GET['slot']) ? intval($_GET['slot']) : null;
        
        // Check if the form is submitted via POST.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Get the time frame from POST data; default is '1D'.
            $timeFrame = isset($_POST['timeFrame']) ? $_POST['timeFrame'] : '1D';
            $controller->add($_POST, $slot, $timeFrame);
        } else {
            // If not a POST request, display the add/replace stock form.
            $controller->showAddForm($slot);
        }
        break;
        
    
    case 'remove':// Case to remove a stock from a given slot.
        // Get the slot index from query parameters (converted to integer).
        $slot = isset($_GET['slot']) ? intval($_GET['slot']) : null;
        $controller->remove($slot);
        break;
    
    
    default:// Default case when an unknown action is provided.
        echo "Invalid action";
        break;
}