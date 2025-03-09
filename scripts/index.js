document.addEventListener("DOMContentLoaded", function () {
    setupStockSelection();
    hideStockDetails();
});

/**
 * Hides stock details section on page load.
 */
function hideStockDetails() {
    document.getElementById("stock-details").style.display = "none";
}

/**
 * Shows stock details when a non-empty stock slot is clicked.
 */
function setupStockSelection() {
    const stockSlots = document.querySelectorAll("#stocks-grid li:not(.empty-slot)");

    stockSlots.forEach(slot => {
        slot.addEventListener("click", function () {
            document.getElementById("stock-details").style.display = "block";
        });
    });
}