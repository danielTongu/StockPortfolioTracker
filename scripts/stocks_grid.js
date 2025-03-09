/**
 * Utility function to simplify document.querySelectorAll
 * @param {string} selector - CSS selector to query elements.
 * @returns {NodeList} - List of matching elements.
 */
function $(selector) {
    return document.querySelectorAll(selector);
}

document.addEventListener('DOMContentLoaded', function () {
    // Handle clicks on stock slots
    $('#stocks-grid li').forEach(slot => {
        slot.addEventListener('click', function () {
            let slotId = this.dataset.slotId;
            window.location.href = `index.php?action=add&slot=${slotId}`;
        });
    });

    // Handle clicks on stock details section
    $('.stock-card').forEach(section => {
        section.addEventListener('click', function (event) {
            event.stopPropagation(); // Prevents parent <li> from being triggered
            let symbol = this.dataset.symbol;
            window.location.href = `index.php?action=view&symbol=${encodeURIComponent(symbol)}&timeFrame=1D`;
        });
    });

    // Handle clicks on replace buttons
    $('.replace-stock').forEach(button => {
        button.addEventListener('click', function (event) {
            event.stopPropagation(); // Prevents parent <li> click
            let slotId = this.dataset.slot;
            window.location.href = `index.php?action=add&slot=${slotId}`;
        });
    });

    // Handle clicks on remove buttons
    $('.remove-stock').forEach(button => {
        button.addEventListener('click', function (event) {
            event.stopPropagation(); // Prevents parent <li> click
            let slotId = this.dataset.slot;
            window.location.href = `index.php?action=remove&slot=${slotId}`;
        });
    });
});