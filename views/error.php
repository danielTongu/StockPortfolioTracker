<?php
// views/error.php
/**
 * View: Error.
 *
 * Displays an error message.
 */
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'head.php'; ?>
<body>
    <?php include 'header.php'; ?>
    <main>
        <h1>Error</h1>
        <p><?php echo htmlspecialchars($error); ?></p>
        <a href="index.php">Back to Stocks</a>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>