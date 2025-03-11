<?php /* views/add_stock.php */?>
<!DOCTYPE html>
<html lang='en'>
    <?php include 'head.php'; ?>
    <body>
        <?php include 'header.php'; ?>
        <main>
            <!-- Dialog for adding or replacing a stock -->
            <dialog id='add-stock-dialog' class='open'>
                <form action='index.php?action=add&slot=<?php echo $slot; ?>' method='post'>
                    <!-- Close button -->
                    <button class='button-close close-dialog' type='button'></button>
                    
                    <!-- Title based on whether a slot is provided -->
                    <p><?php echo ($slot !== null) ? 'Replace Stock ' : 'Add Stock'; ?></p>
                    
                    <!-- Label and input for the stock ticker -->
                    <input id='stock-input' name='symbol' type='text' placeholder='Enter stock ticker' maxlength='20' required>
                    
                    <!-- Hidden input for time frame (defaulting to 1D) -->
                    <input type='hidden' name='timeFrame' value='1D'>
                    
                    <!-- Button group -->
                    <div id='button-group'>
                        <button type='submit' data-button-text='ok'></button>
                        <button type='button' class='cancel-dialog' data-button-text='cancel'></button>
                    </div>
                </form>
            </dialog>
        </main>
        <?php include 'footer.php'; ?>
        <script src="scripts/add_stock.js"></script>
    </body>
</html>