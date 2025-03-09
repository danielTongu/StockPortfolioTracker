/**
 * Utility function to simplify document.querySelectorAll
 * @param {string} selector - CSS selector to query elements.
 * @returns {NodeList} - List of matching elements.
 */
function $(selector) {
    return document.querySelectorAll(selector);
}

document.addEventListener('DOMContentLoaded', function () {
    const dialog = document.getElementById('add-stock-dialog');

    // Close dialog and return to main page when close button is clicked
    $('.close-dialog').forEach(button => {
        button.addEventListener('click', function () {
            window.location.href = 'index.php';
        });
    });

    // Cancel button also redirects to main page
    $('.cancel-dialog').forEach(button => {
        button.addEventListener('click', function () {
            window.location.href = 'index.php';
        });
    });
});