<?php
// Include the StockModel class, which handles data fetching and other stock-related operations.
require_once 'models/StockModel.php';

/**
 * Class StockController
 *
 * Implements a Singleton pattern to ensure only one instance exists.
 * Handles various stock-related actions including listing stocks, viewing details,
 * adding/replacing a stock, and removing a stock.
 */
class StockController {
    
    /**
     * @var StockController|null The single instance of the class (singleton instance).
     */
    private static $instance = null;
    
    /**
     * @var StockModel Instance of StockModel to fetch stock data.
     */
    private $model;
    
    /**
     * Private constructor to prevent direct instantiation.
     * Initializes the session, model, and session-based stock storage.
     */
    private function __construct() {
        // Start the session if it hasn't been started already.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Instantiate the StockModel.
        $this->model = new StockModel();
        
        // Define the number of stock slots if not already defined.
        if (!defined('NUM_STOCKS')) {
            define('NUM_STOCKS', 10);
        }
        
        // Initialize the stocks in the session:
        // If the 'stocks' array doesn't exist or isn't an array, create it with NUM_STOCKS null slots.
        if (!isset($_SESSION['stocks']) || !is_array($_SESSION['stocks'])) {
            $_SESSION['stocks'] = array_fill(0, NUM_STOCKS, null);
        } else {
            // Ensure there are exactly NUM_STOCKS slots in the session.
            for ($i = 0; $i < NUM_STOCKS; $i++) {
                if (!array_key_exists($i, $_SESSION['stocks'])) {
                    $_SESSION['stocks'][$i] = null;
                }
            }
        }
    }

    /**
     * Returns the single instance of StockController.
     * This method implements the singleton logic: if an instance doesn't already exist,
     * it creates one, otherwise, it returns the existing instance.
     *
     * @return StockController The singleton instance of this class.
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new StockController();
        }
        return self::$instance;
    }

    /**
     * Display the stocks grid.
     * Retrieves the current stocks from the session and includes the view file that displays the grid.
     */
    public function index() {
        // Retrieve stocks from the session.
        $stocks = $_SESSION['stocks'];
        
        // Set the page title for the stocks grid.
        $pageTitle = 'Stock Cards';
        
        // Include the view that renders the stocks grid.
        include 'views/stocks_grid.php';
    }
    
    /**
     * Display details for a single stock.
     * Validates the provided stock symbol, fetches the corresponding stock data using the model,
     * prepares data for Chart.js visualization (including dates, prices, and additional stats),
     * and includes the stock details view.
     *
     * @param string|null $symbol The stock symbol to view. If null, the user is redirected.
     * @param string $timeFrame The selected time frame (default is '1D').
     */
    public function view($symbol, $timeFrame = '1D') {
        // If no stock symbol is provided, redirect to the index page.
        if ($symbol === null) {
            header('Location: index.php');
            exit();
        }

        try {
            // Fetch stock data with the selected time frame using the StockModel.
            $stockData = $this->model->fetchStockData($symbol, $timeFrame);

            // Prepare time series data for Chart.js.
            $dates = [];              // Array to hold dates for the chart.
            $formattedPrices = [];    // Array to hold formatted price values.
            $additionalStats = [];    // Array to hold additional statistics for each date.

            // Iterate over each data point in the time series.
            foreach ($stockData['timeSeries'] as $date => $dataPoint) {
                // Collect the date.
                $dates[] = $date;
                
                // Use the adjusted close price if available; otherwise, use the close price.
                $formattedPrices[] = isset($dataPoint['5. adjusted close'])
                    ? floatval($dataPoint['5. adjusted close'])
                    : floatval($dataPoint['4. close']);

                // Prepare additional statistics for the current date.
                $additionalStats[] = [
                    'date'            => $date,
                    'open'            => floatval($dataPoint['1. open']),
                    'high'            => floatval($dataPoint['2. high']),
                    'low'             => floatval($dataPoint['3. low']),
                    'close'           => floatval($dataPoint['4. close']),
                    'adjustedClose'   => isset($dataPoint['5. adjusted close']) ? floatval($dataPoint['5. adjusted close']) : null,
                    'dividendAmount'  => isset($dataPoint['7. dividend amount']) ? floatval($dataPoint['7. dividend amount']) : null,
                    'splitCoefficient'=> isset($dataPoint['8. split coefficient']) ? floatval($dataPoint['8. split coefficient']) : null,
                    'volume'          => intval($dataPoint['6. volume']),
                ];
            }

            // Convert the chart data to JSON format for consumption by JavaScript.
            $chartData = json_encode([
                'dates'           => $dates,
                'prices'          => $formattedPrices,
                'additionalStats' => $additionalStats,
                'timeUnit'        => $stockData['timeUnit'],
                'symbol'          => $stockData['symbol'],
                'changePercent'   => $stockData['changePercent'],
                'timeFrame'       => $timeFrame
            ]);

            // Reference available time frames from the model for consistency.
            $timeFrames = StockModel::$availableTimeFrames;

            // Set the page title dynamically including the stock symbol.
            $pageTitle = "Stock Details";

            // Include the view file that renders the stock details and chart.
            include 'views/stock_details.php';
        } catch (Exception $e) {
            $pageTitle = "Error while viewing {$symbol} details - {$timeFrame}";
            // In case of any error, capture the exception message.
            $error = $e->getMessage();
            
            // Include the error view to display the error message.
            include 'views/error.php';
        }
    }
    
    /**
     * Show the form to add or replace a stock.
     * Sets the page title and includes the view that contains the add/replace stock form.
     *
     * @param int|null $slot The slot index to add or replace a stock.
     */
    public function showAddForm($slot = null) {
        // Set the page title for the add stock form.
        $pageTitle = 'Add a Stock';
        
        // Include the view file with the add stock form.
        include 'views/add_stock.php';
    }
    
    /**
     * Handle the addition or replacement of a stock.
     * Processes the POST data to extract and validate the stock symbol, checks for duplicates,
     * fetches stock data using the model, updates the corresponding session slot, and redirects
     * to the index page upon successful operation.
     *
     * @param array $data POST data containing the stock symbol.
     * @param int|null $slot The slot index where the stock will be added or replaced.
     * @param string $timeFrame The selected time frame (default is '1D').
     */
    public function add($data, $slot, $timeFrame = '1D') {
        $pageTitle = 'Error while adding stock';
        
        // Retrieve and sanitize the stock symbol from POST data.
        $symbol = isset($data['symbol']) ? strtoupper(trim($data['symbol'])) : '';
        
        // If the symbol is empty, set an error message and include the error view.
        if ($symbol === '') {
            $error = 'Please enter a valid stock symbol.';
            include 'views/error.php';
            return;
        }

        // Check the session to ensure no duplicate stock symbols exist in a different slot.
        foreach ($_SESSION['stocks'] as $index => $stock) {
            if ($stock && $stock['symbol'] === $symbol && $index !== $slot) {
                $error = "Duplicate stock symbol [{$symbol}] in another slot.";
                include 'views/error.php';
                return;
            }
        }

        try {
            // Fetch stock data for the given symbol and time frame.
            $stockData = $this->model->fetchStockData($symbol, $timeFrame);
            
            // Update the specified slot in the session with the fetched stock data.
            $_SESSION['stocks'][$slot] = $stockData;
            
            // Redirect to the index page upon successful addition.
            header('Location: index.php');
            exit();
        } catch (Exception $e) {
            // If fetching data fails, capture the error message.
            $error = $e->getMessage();
            
            // Include the error view to display the error message.
            include 'views/error.php';
        }
    }
    
    /**
     * Remove a stock from a specified slot.
     * Sets the corresponding session slot to null and redirects to the index page.
     *
     * @param int|null $slot The slot index from which the stock will be removed.
     */
    public function remove($slot) {
        if ($slot !== null && isset($_SESSION['stocks'][$slot])) {
            $_SESSION['stocks'][$slot] = null;
        }
        header('Location: index.php');
        exit();
    }
}