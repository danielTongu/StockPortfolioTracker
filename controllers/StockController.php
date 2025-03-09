<?php
// controllers/StockController.php

require_once 'models/StockModel.php';

/**
 * Class StockController
 *
 * Handles stock-related actions.
 */
class StockController {

    /**
     * @var StockModel Instance of the StockModel.
     */
    private $model;

    /**
     * Constructor initializes the StockModel and session stock storage.
     */
    public function __construct() {
        // Start the session if it hasn't been started yet.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Instantiate the StockModel.
        $this->model = new StockModel();

        // Define the number of stock slots.
        if (!defined('NUM_STOCKS')) {
            define('NUM_STOCKS', 10);
        }

        // Initialize stocks in session if not already set or ensure each slot exists.
        if (!isset($_SESSION['stocks']) || !is_array($_SESSION['stocks'])) {
            // Create an array with NUM_STOCKS null slots.
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
     * Display the stocks grid.
     */
    public function index() {
        // Retrieve the stocks array from session.
        $stocks = $_SESSION['stocks'];
        include 'views/stocks_grid.php';
    }

    /**
     * Display details for a single stock.
     *
     * @param string|null $symbol The stock symbol to view.
     * @param string $timeFrame The selected time frame.
     */
    public function view($symbol, $timeFrame = '1D') {
        if ($symbol === null) {
            header('Location: index.php');
            exit();
        }

        try {
            // Fetch stock data with the selected time frame.
            $stockData = $this->model->fetchStockData($symbol, $timeFrame);

            // Prepare time series data for Chart.js.
            $dates = array();
            $formattedPrices = array();
            $additionalStats = array();

            // Process each data point from the time series.
            foreach ($stockData['timeSeries'] as $date => $dataPoint) {
                $dates[] = $date;

                // Use adjusted close price if available; fallback to close price.
                if (isset($dataPoint['5. adjusted close'])) {
                    $formattedPrices[] = floatval($dataPoint['5. adjusted close']);
                } else {
                    $formattedPrices[] = floatval($dataPoint['4. close']);
                }

                // Prepare additional stats for tooltips.
                $additionalStats[] = array(
                    'date' => $date,
                    'open' => floatval($dataPoint['1. open']),
                    'high' => floatval($dataPoint['2. high']),
                    'low' => floatval($dataPoint['3. low']),
                    'close' => floatval($dataPoint['4. close']),
                    'adjustedClose' => isset($dataPoint['5. adjusted close']) ? floatval($dataPoint['5. adjusted close']) : null,
                    'dividendAmount' => isset($dataPoint['7. dividend amount']) ? floatval($dataPoint['7. dividend amount']) : null,
                    'splitCoefficient' => isset($dataPoint['8. split coefficient']) ? floatval($dataPoint['8. split coefficient']) : null,
                    'volume' => intval($dataPoint['6. volume']),
                );
            }

            // Convert chart data to JSON format for JavaScript.
            $chartData = json_encode(array(
                'dates' => $dates,
                'prices' => $formattedPrices,
                'additionalStats' => $additionalStats,
                'timeUnit' => $stockData['timeUnit'], // Default time unit (e.g. 'day')
                'symbol' => $stockData['symbol'],
                'changePercent' => $stockData['changePercent'],
                'timeFrame' => $timeFrame  // Pass the selected time frame to the view
            ));

            // Define time frame options in the controller.
            $timeFrames = array('1D', '5D', '1M', '6M', 'YTD', '1Y', '5Y', 'ALL');

            // Pass data to the view.
            include 'views/stock_details.php';
        } catch (Exception $e) {
            $error = $e->getMessage();
            include 'views/error.php';
        }
    }

    /**
     * Show the add/replace stock form.
     *
     * @param int|null $slot The slot index to add/replace.
     */
    public function showAddForm($slot = null) {
        include 'views/add_stock.php';
    }

    /**
     * Handle adding or replacing a stock.
     *
     * @param array $data POST data containing the stock symbol.
     * @param int|null $slot The slot index where the stock will be added or replaced.
     * @param string $timeFrame The selected time frame.
     */
    public function add($data, $slot, $timeFrame = '1D') {
        // Get and sanitize the stock symbol.
        $symbol = isset($data['symbol']) ? strtoupper(trim($data['symbol'])) : '';
        if ($symbol === '') {
            $error = 'Please enter a valid stock symbol.';
            include 'views/error.php';
            return;
        }
        // Check for duplicate stocks in other slots.
        foreach ($_SESSION['stocks'] as $index => $stock) {
            if ($stock && $stock['symbol'] === $symbol && $index !== $slot) {
                $error = 'Duplicate stock symbol in another slot.';
                include 'views/error.php';
                return;
            }
        }
        try {
            // Fetch stock data from the API.
            $stockData = $this->model->fetchStockData($symbol, $timeFrame);
            // Save the stock in session at the specified slot.
            $_SESSION['stocks'][$slot] = $stockData;
            header('Location: index.php');
            exit();
        } catch (Exception $e) {
            $error = $e->getMessage();
            include 'views/error.php';
        }
    }

    /**
     * Remove a stock from a given slot.
     *
     * @param int|null $slot The slot index to remove the stock from.
     */
    public function remove($slot) {
        if ($slot !== null && isset($_SESSION['stocks'][$slot])) {
            $_SESSION['stocks'][$slot] = null;
        }
        header('Location: index.php');
        exit();
    }
}