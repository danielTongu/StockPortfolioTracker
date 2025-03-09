<!DOCTYPE html>
<html lang='en'>
    <?php include 'head.php'; ?>
    <body>
        <?php include 'header.php'; ?>
        <main>
            <ul id='stocks-grid'>
                <?php
                // Loop through each stock slot.
                for ($slotId = 0; $slotId < NUM_STOCKS; $slotId++) {
                    
                    // Retrieve the stock for the current slot.
                    $stock = $stocks[$slotId];
                    
                    // Determine if a valid stock exists (i.e. it is not null and has a 'symbol' key).
                    $isStockAdded = ($stock && isset($stock['symbol']));
                    ?>
                    <li data-slot-id='<?php echo $slotId; ?>'
                        class='<?php echo $isStockAdded ? 'stock-slot' : 'empty-slot'; ?>'>
                        
                        <?php if ($isStockAdded): ?>
                        
                            <!-- Section to display stock details -->
                            <section class="stock-card" data-symbol="<?php echo htmlspecialchars($stock['symbol']); ?>">
                                <p class='stock-symbol'><?php echo htmlspecialchars($stock['symbol']); ?></p>
                                <p class='stock-name'><?php echo htmlspecialchars($stock['name']); ?></p>
                                <span class='current-price'><?php echo number_format($stock['price'], 2); ?></span>
                                <span class='price-change <?php echo ($stock['changePercent'] > 0) ? 'positive' : (($stock['changePercent'] < 0) ? 'negative' : ''); ?>'>
                                    <?php echo number_format($stock['changePercent'], 2); ?>
                                </span>
                            </section>
                            
                            <!-- Button to replace the stock -->
                            <button data-button-text='replace' data-slot='<?php echo $slotId; ?>'></button>
                            
                            <!-- Button to remove the stock -->
                            <button class='remove-stock button-close' data-slot='<?php echo $slotId; ?>'></button>
                        <?php endif; ?>
                    </li>
                    <?php
                }
                // end of loop
                ?>
            </ul>
        </main>
        <?php include 'footer.php'; ?>
        <script src="./scripts/stocks_grid.js"></script>
    </body>
</html>