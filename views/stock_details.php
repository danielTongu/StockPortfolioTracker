<?php// views/stock_details.php ?>
<!DOCTYPE html>
<html lang="en">
    <?php include 'head.php'; ?>
    <body>
        <?php include 'header.php'; ?>
        <main>
            <section id="stock-details">
                <!-- Stock symbol with a data attribute for JS -->
                <span class="stock-symbol" data-symbol="<?php echo htmlspecialchars($stockData['symbol']); ?>">
                    <?php echo htmlspecialchars($stockData['symbol']); ?>
                </span>
                <span class="stock-name"><?php echo htmlspecialchars($stockData['name']); ?></span>
                <div id="stock-price-change">
                    <span class="current-price"><?php echo number_format($stockData['price'], 2); ?></span>
                    <span class="price-change <?php echo ($stockData['changePercent'] > 0) ? 'positive' : (($stockData['changePercent'] < 0) ? 'negative' : ''); ?>">
                        <?php echo number_format($stockData['changePercent'], 2); ?>
                    </span>
                </div>
                
                <!-- Time frame options using data from the controller -->
                <ul id="time-options">
                    <?php foreach ($timeFrames as $period): ?>
                        <li data-period="<?php echo $period; ?>"
                            class="<?php echo ($timeFrame === $period) ? 'active' : ''; ?>"
                            onclick="window.location.href='index.php?action=view&symbol=<?php echo urlencode($stockData['symbol']); ?>&timeFrame=<?php echo $period; ?>'">
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <!-- Chart container -->
                <figure id="chart-container">
                    <canvas id="stockChart" height="300"
                        data-chart='<?php echo htmlspecialchars($chartData, ENT_QUOTES, 'UTF-8'); ?>'>
                    </canvas>
                </figure>
                
                <!-- Detailed statistics -->
                <ul id="stock-stats">
                    <li data-stat='previousClose'>
                        <?php echo ($stockData['previousClose'] !== null) ? number_format($stockData['previousClose'], 2) : 'N/A'; ?>
                    </li>
                    <li data-stat='Open Price'>
                        <?php echo ($stockData['openPrice'] !== null) ? number_format($stockData['openPrice'], 2) : 'N/A'; ?>
                    </li>
                    <li data-stat='Day Range'>
                        <?php 
                        if (isset($stockData['dayLow']) && isset($stockData['dayHigh'])) { 
                            echo "<span class='low'>" . number_format($stockData['dayLow'], 2) . "</span>";
                            echo "<span class='high'>" . number_format($stockData['dayHigh'], 2) . "</span>";
                        } else { 
                            echo 'N/A'; 
                        } 
                        ?>
                    </li>
                    <li data-stat='52 Week Range'>
                        <?php 
                        if (isset($stockData['weekLow']) && isset($stockData['weekHigh'])) { 
                            echo "<span class='low'>" . number_format($stockData['weekLow'], 2) . "</span>";
                            echo "<span class='high'>" . number_format($stockData['weekHigh'], 2) . "</span>";
                        } else { 
                            echo 'N/A'; 
                        } 
                        ?>
                    </li>
                    <li data-stat='Volume'>
                        <?php echo ($stockData['volume'] !== null) ? number_format($stockData['volume']) : 'N/A'; ?>
                    </li>
                    <li data-stat='Avg. Volume'>
                        <?php echo ($stockData['avgVolume'] !== null) ? number_format($stockData['avgVolume']) : 'N/A'; ?>
                    </li>
                    <li data-stat='Market Cap'>
                        <?php echo ($stockData['marketCap'] !== null) ? $stockData['marketCap'] : 'N/A'; ?>
                    </li>
                    <li data-stat='Beta'>
                        <?php echo ($stockData['beta'] !== null) ? number_format($stockData['beta'], 2) : 'N/A'; ?>
                    </li>
                    <li data-stat='PE Ratio'>
                        <?php echo ($stockData['peRatio'] !== null) ? number_format($stockData['peRatio'], 2) : 'N/A'; ?>
                    </li>
                    <li data-stat='EPS'>
                        <?php echo ($stockData['eps'] !== null) ? number_format($stockData['eps'], 2) : 'N/A'; ?>
                    </li>
                    <li data-stat='Target Est.'>
                        <?php echo ($stockData['targetEst'] !== null) ? number_format($stockData['targetEst'], 2) : 'N/A'; ?>
                    </li>
                    <li data-stat='Dividend'>
                        <?php echo ($stockData['dividendAmount'] !== null) ? number_format($stockData['dividendAmount'], 2) : 'N/A'; ?>
                    </li>
                </ul>
            </section>
        </main>
        <?php include 'footer.php'; ?>
        <script src="./scripts/stock_details.js"></script>
        
        <!-- Include Chart.js and Moment.js adapter -->
        <script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
        <script src='https://cdn.jsdelivr.net/npm/moment@2.29.1'></script>
        <script src='https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.0'></script>
    </body>
</html>