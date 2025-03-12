<?php
// models/StockModel.php

/**
 * Handles fetching and processing stock data from the Alpha Vantage API.
 */
class StockModel {

    /**
     * @var string API key for Alpha Vantage.
     */
    private $apiKey;

    /**
     * @var string Base URL for the Alpha Vantage API.
     */
    private $baseUrl = "https://www.alphavantage.co/query";

    /**
     * Array of available time frames.
     *
     * @var array
     */
    public static $availableTimeFrames = ['1D', '5D', '1M', '6M', 'YTD', '1Y', '5Y', 'ALL'];

    /**
     * Constructor loads the API key from configuration.
     */
    public function __construct() {
        $config = require 'config.php';
        $this->apiKey = $config['API_KEY'];
    }

    /**
     * Fetch stock data including time series and overview information.
     *
     * @param string $symbol Stock symbol.
     * @param string $timeFrame Time frame for the data (e.g., '1D', '5D', '1M', etc.).
     * @return array Stock data including various statistics.
     * @throws Exception If the data cannot be fetched.
     */
    public function fetchStockData($symbol, $timeFrame = '1D') {
        // First, search for the symbol to get the proper name.
        $searchResult = $this->searchSymbol($symbol);
        if (!$searchResult) {
            throw new Exception("No matches found for symbol '{$symbol}'");
        }
        
        // Update symbol and name based on search result.
        $symbol = $searchResult['symbol'];
        $name = $searchResult['name'];

        // Get API parameters based on the time frame.
        $params = $this->getTimeFrameParams($timeFrame);
        $endpoint = $params['endpoint'];
        $interval = $params['interval'];
        $timeUnit = $params['timeUnit'];
        $ticks = $params['ticks'];

        // Determine the volume key.
        $volumeKey = (strpos($endpoint, 'ADJUSTED') !== false) ? '6. volume' : '5. volume';

        // Build the API URL.
        if ($endpoint === 'TIME_SERIES_INTRADAY') {
            $url = "{$this->baseUrl}?function={$endpoint}&symbol={$symbol}&interval={$interval}&apikey={$this->apiKey}&outputsize=full";
        } else {
            $url = "{$this->baseUrl}?function={$endpoint}&symbol={$symbol}&apikey={$this->apiKey}&outputsize=full";
        }
        
        // Execute the API request.
        $response = $this->curlGet($url);
        
        // Decode JSON response as an associative array.
        $data = json_decode($response, true);

        // Map endpoint to the time series key.
        $timeSeriesKeyMap = [
            'TIME_SERIES_INTRADAY' => "Time Series ({$interval})",
            'TIME_SERIES_DAILY' => 'Time Series (Daily)',
            'TIME_SERIES_DAILY_ADJUSTED' => 'Time Series (Daily)',
            'TIME_SERIES_WEEKLY' => 'Weekly Time Series',
            'TIME_SERIES_WEEKLY_ADJUSTED' => 'Weekly Adjusted Time Series',
            'TIME_SERIES_MONTHLY' => 'Monthly Time Series',
            'TIME_SERIES_MONTHLY_ADJUSTED' => 'Monthly Adjusted Time Series',
        ];
        
        $timeSeriesKey = $timeSeriesKeyMap[$endpoint];
        
        // Check if the time series data is available.
        if (!isset($data[$timeSeriesKey])) {
            throw new Exception("Time series data unavailable for '{$timeFrame}'.");
        }
        
        // Get the time series data.
        $timeSeries = $data[$timeSeriesKey];

        // Get the list of dates from the time series and filter them.
        $allDates = array_keys($timeSeries);
        
        // Sort dates in descending order.
        rsort($allDates);
        
        // Filter the dates according to the selected time frame.
        $dates = $this->getChartDates($allDates, $timeFrame);

        // Fetch overview data.
        $overviewData = $this->fetchStockOverview($symbol);
        
        // Extract statistics from the overview data.
        $peRatio = isset($overviewData['PERatio']) ? floatval($overviewData['PERatio']) : null;
        $marketCap = isset($overviewData['MarketCapitalization']) ? floatval($overviewData['MarketCapitalization']) : null;
        $beta = isset($overviewData['Beta']) ? floatval($overviewData['Beta']) : null;
        $eps = isset($overviewData['EPS']) ? floatval($overviewData['EPS']) : null;
        $earningsDate = isset($overviewData['EarningsDate']) ? $overviewData['EarningsDate'] : null;
        $targetEst = isset($overviewData['AnalystTargetPrice']) ? floatval($overviewData['AnalystTargetPrice']) : null;

        // Compute detailed statistics.
        $stats = $this->computeDetailedStats($timeSeries, $dates, $volumeKey);

        // Get the latest date and its corresponding data.
        $latestDate = end($dates);
        $latestData = $timeSeries[$latestDate];

        // The final result as an array:
        return [
            'symbol'           => $symbol,
            'name'             => $name,
            'latestDate'       => $latestDate,
            'price'            => floatval($latestData['4. close']),
            'changePercent'    => $this->calculatePercentageChange($timeSeries, $dates),
            'dates'            => $dates,
            'timeSeries'       => $timeSeries,
            'timeUnit'         => $timeUnit,
            'ticks'            => $ticks,
            'volumeKey'        => $volumeKey,
            'previousClose'    => $stats['previousClose'],
            'openPrice'        => $stats['openPrice'],
            'volume'           => $stats['volume'],
            'dayLow'           => $stats['dayLow'],
            'dayHigh'          => $stats['dayHigh'],
            'weekLow'          => $stats['weekLow'],
            'weekHigh'         => $stats['weekHigh'],
            'avgVolume'        => $stats['avgVolume'],
            'adjustedClose'    => $stats['adjustedClose'],
            'dividendAmount'   => $stats['dividendAmount'],
            'splitCoefficient' => $stats['splitCoefficient'],
            'beta'             => $beta,
            'peRatio'          => $peRatio,
            'eps'              => $eps,
            'earningsDate'     => $earningsDate,
            'targetEst'        => $targetEst,
            'marketCap'        => $marketCap,
        ];
    }

    /**
     * Helper method to perform a cURL GET request.
     *
     * @param string $url The URL to fetch.
     * @return string The response.
     * @throws Exception If a cURL error occurs.
     */
    private function curlGet($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        curl_close($ch);
        return $output;
    }

    /**
     * Searches for a stock symbol using Alpha Vantage's SYMBOL_SEARCH.
     *
     * @param string $query The stock symbol query.
     * @return array|null An array with 'symbol' and 'name', or null if not found.
     */
    private function searchSymbol($query) {
        $url = "{$this->baseUrl}?function=SYMBOL_SEARCH&keywords={$query}&apikey={$this->apiKey}";
        $response = $this->curlGet($url);
        
        // Decode response as an associative array.
        $data = json_decode($response, true);
        if (!isset($data['bestMatches']) || count($data['bestMatches']) == 0) {
            return null;
        }
        
        // Use the first match.
        $match = $data['bestMatches'][0];
        return [
            'symbol' => $match['1. symbol'],
            'name'   => $match['2. name']
        ];
    }

    /**
     * Fetches the overview data for a given stock symbol.
     *
     * @param string $symbol The stock symbol.
     * @return array Overview data.
     */
    private function fetchStockOverview($symbol) {
        $url = "{$this->baseUrl}?function=OVERVIEW&symbol={$symbol}&apikey={$this->apiKey}";
        $response = $this->curlGet($url);
        
        // Decode JSON response as an associative array.
        $data = json_decode($response, true);
        
        // If no data or an empty array is returned, return an empty array.
        if (!$data || count($data) === 0) {
            return [];
        }
        return $data;
    }

    /**
     * Returns API parameters based on the selected time frame.
     *
     * @param string $timeFrame The selected time frame.
     * @return array Parameters including endpoint, interval, timeUnit, and ticks.
     * @throws Exception If the time frame is invalid.
     */
    private function getTimeFrameParams($timeFrame = '1D') {
        // Default parameters.
        $params = [
            'endpoint' => 'TIME_SERIES_DAILY',
            'interval' => '',
            'timeUnit' => 'day',
            'ticks'    => 22
        ];
        
        switch ($timeFrame) {
            case '1D':
                $params['endpoint'] = 'TIME_SERIES_INTRADAY';
                $params['interval'] = '15min';
                $params['timeUnit'] = 'hour';
                $params['ticks']    = 24;
                break;
            case '5D':
                $params['endpoint'] = 'TIME_SERIES_INTRADAY';
                $params['interval'] = '60min';
                $params['timeUnit'] = 'day';
                $params['ticks']    = 5;
                break;
            case '1M':
                $params['endpoint'] = 'TIME_SERIES_DAILY_ADJUSTED';
                $params['timeUnit'] = 'day';
                $params['ticks']    = 22;
                break;
            case '6M':
                $params['endpoint'] = 'TIME_SERIES_DAILY_ADJUSTED';
                $params['timeUnit'] = 'month';
                $params['ticks']    = 6;
                break;
            case 'YTD':
                $params['endpoint'] = 'TIME_SERIES_WEEKLY_ADJUSTED';
                $params['timeUnit'] = 'month';
                $params['ticks']    = date("n"); // Months since beginning of year.
                break;
            case '1Y':
                $params['endpoint'] = 'TIME_SERIES_WEEKLY_ADJUSTED';
                $params['timeUnit'] = 'month';
                $params['ticks']    = 12;
                break;
            case '5Y':
                $params['endpoint'] = 'TIME_SERIES_WEEKLY_ADJUSTED';
                $params['timeUnit'] = 'year';
                $params['ticks']    = 5;
                break;
            case 'ALL':
                $params['endpoint'] = 'TIME_SERIES_MONTHLY_ADJUSTED';
                $params['timeUnit'] = 'year';
                $params['ticks']    = 20;
                break;
            default:
                throw new Exception("Invalid time frame: " . $timeFrame);
        }
        return $params;
    }

    /**
     * Filters and returns an array of dates appropriate for charting.
     *
     * @param array $allDates All dates from the time series.
     * @param string $timeFrame The selected time frame.
     * @return array Filtered dates.
     */
    private function getChartDates($allDates, $timeFrame) {
        $currentDate = new DateTime();
        $dates = [];
        switch ($timeFrame) {
            case '1D': // For a 1-day timeframe, include dates within the past 24 hours.
                $past24 = (clone $currentDate)->modify('-24 hours');
                foreach ($allDates as $dateStr) {
                    $dateObj = new DateTime($dateStr);
                    if ($dateObj >= $past24) {
                        $dates[] = $dateStr;
                    }
                }
                // Reverse the dates so the oldest is first.
                $dates = array_reverse($dates);
                if (empty($dates)) {
                    // Fallback: take first 96 entries.
                    $dates = array_reverse(array_slice($allDates, 0, 96));
                }
                break;
            case '5D': // For a 5-day timeframe, include dates within the past 5 days.
                $past5 = (clone $currentDate)->modify('-5 days');
                foreach ($allDates as $dateStr) {
                    $dateObj = new DateTime($dateStr);
                    if ($dateObj >= $past5) {
                        $dates[] = $dateStr;
                    }
                }
                $dates = array_reverse($dates);
                break;
            case '1M':
            case '6M':
            case 'YTD':
            case '1Y':
            case '5Y':
            case 'ALL': // For these time frames, simply take the most recent N dates.
                $dates = array_reverse(array_slice($allDates, 0, 50));
                break;
            default:
                $dates = array_reverse($allDates);
                break;
        }
        return $dates;
    }

    /**
     * Computes detailed statistics from the time series data.
     *
     * @param array $timeSeries The time series data.
     * @param array $dates Array of dates.
     * @param string $volumeKey The key to access volume.
     * @return array Detailed statistics.
     */
    private function computeDetailedStats($timeSeries, $dates, $volumeKey) {
        // If there are not enough data points, set all statistics to null.
        if (count($dates) < 2) {
            return [
                'previousClose'   => null,
                'openPrice'       => null,
                'dayLow'          => null,
                'dayHigh'         => null,
                'volume'          => null,
                'adjustedClose'   => null,
                'dividendAmount'  => null,
                'splitCoefficient'=> null,
                'weekLow'         => null,
                'weekHigh'        => null,
                'avgVolume'       => null,
            ];
        }
        
        // Retrieve the latest and previous data based on the dates array.
        $latestData = $timeSeries[$dates[count($dates) - 1]];
        $previousData = $timeSeries[$dates[count($dates) - 2]];
        
        // Calculate statistics from the latest and previous data.
        $previousClose = isset($previousData['4. close']) ? floatval($previousData['4. close']) : null;
        $openPrice = isset($latestData['1. open']) ? floatval($latestData['1. open']) : null;
        $dayLow = isset($latestData['3. low']) ? floatval($latestData['3. low']) : null;
        $dayHigh = isset($latestData['2. high']) ? floatval($latestData['2. high']) : null;
        $volume = isset($latestData[$volumeKey]) ? intval($latestData[$volumeKey]) : null;
        $adjustedClose = (isset($latestData['5. adjusted close'])) ? floatval($latestData['5. adjusted close']) : null;
        $dividendAmount = (isset($latestData['7. dividend amount'])) ? floatval($latestData['7. dividend amount']) : null;
        $splitCoefficient = (isset($latestData['8. split coefficient'])) ? floatval($latestData['8. split coefficient']) : null;
        
        // Compute 52-week range approximately using an explicit loop.
        $past52Weeks = array_slice(array_keys($timeSeries), -260);
        if (!empty($past52Weeks)) {
            $lowValues = array();
            $highValues = array();
            foreach ($past52Weeks as $date) {
                // Collect the low and high values for each date.
                if (isset($timeSeries[$date]['3. low'])) {
                    $lowValues[] = floatval($timeSeries[$date]['3. low']);
                }
                if (isset($timeSeries[$date]['2. high'])) {
                    $highValues[] = floatval($timeSeries[$date]['2. high']);
                }
            }
            $weekLow = !empty($lowValues) ? min($lowValues) : null;
            $weekHigh = !empty($highValues) ? max($highValues) : null;
        } else {
            $weekLow = null;
            $weekHigh = null;
        }
        
        // Compute average volume.
        $totalVolume = 0;
        $count = 0;
        foreach ($dates as $date) {
            if (isset($timeSeries[$date][$volumeKey])) {
                $totalVolume += intval($timeSeries[$date][$volumeKey]);
                $count++;
            }
        }
        $avgVolume = ($count > 0) ? ($totalVolume / $count) : null;
        
        return [
            'previousClose'    => $previousClose,
            'openPrice'        => $openPrice,
            'dayLow'           => $dayLow,
            'dayHigh'          => $dayHigh,
            'volume'           => $volume,
            'adjustedClose'    => $adjustedClose,
            'dividendAmount'   => $dividendAmount,
            'splitCoefficient' => $splitCoefficient,
            'weekLow'          => $weekLow,
            'weekHigh'         => $weekHigh,
            'avgVolume'        => $avgVolume,
        ];
    }

    /**
     * Calculates the percentage change between the first and last dates.
     *
     * @param array $timeSeries The time series data.
     * @param array $dates Array of dates.
     * @return float Percentage change.
     */
    private function calculatePercentageChange($timeSeries, $dates) {
        if (count($dates) < 2) {
            return 0;
        }
        $firstDate = $dates[0];
        $lastDate = end($dates);
        if (!isset($timeSeries[$firstDate]) || !isset($timeSeries[$lastDate])) {
            return 0;
        }
        $firstPrice = floatval($timeSeries[$firstDate]['4. close']);
        $lastPrice = floatval($timeSeries[$lastDate]['4. close']);
        return (($lastPrice - $firstPrice) / $firstPrice) * 100;
    }
}