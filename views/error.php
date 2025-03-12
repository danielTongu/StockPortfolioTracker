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
        <p><?php echo htmlspecialchars($error); ?></p><br>
        <a href="index.php">Exit</a>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>