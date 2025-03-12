document.addEventListener("DOMContentLoaded", function () {
    // Hides stock details section on page load.
    document.getElementById("stock-details").style.display = "none";
    
    setupStockSelection();
    setupHomeButton();
    setupAddStockDialog();
    updateCopyrightYear();
});

/**
 * Handles stock selection events to switch views between grid and stock details.
 * Updates the stock details with the selected stock's information.
 */
function setupStockSelection() {
    const stockSections = document.querySelectorAll("#stocks-grid li:not(.empty-slot) section");
    const stockDetails = document.getElementById("stock-details");
    
    stockSections.forEach(section => {
        section.addEventListener("click", function () {
            document.getElementById("stocks-grid").style.display = "none";
            stockDetails.style.display = "block";
            document.querySelector("header h1").textContent = "Details";
            
            // Update stock details based on the selected stock
            const symbol = section.querySelector(".stock-symbol").textContent;
            const name = section.querySelector(".stock-name").textContent;
            const price = section.querySelector(".current-price").textContent;
            const priceChange = section.querySelector(".price-change").textContent;
            const priceChangeElement = section.querySelector(".price-change");
            
            stockDetails.querySelector(".stock-symbol").textContent = symbol;
            stockDetails.querySelector(".stock-name").textContent = name;
            stockDetails.querySelector(".current-price").textContent = price;
            
            const stockDetailsPriceChange = stockDetails.querySelector(".price-change");
            stockDetailsPriceChange.textContent = priceChange;
            stockDetailsPriceChange.className = `price-change ${priceChange < 0 ? "negative" : (priceChange > 0 ? "positive" : "")}`;
        });
    });
}

/**
 * Handles the Home button click event to return to the stocks grid view.
 */
function setupHomeButton() {
    document.getElementById("button-home").addEventListener("click", function () {
        document.getElementById("stocks-grid").style.display = "grid";
        document.getElementById("stock-details").style.display = "none";
        document.querySelector("header h1").textContent = "Stock Cards";
    });
}

/**
 * Opens the add stock dialog when an empty slot is clicked or when 'replace' button is clicked.
 */
function setupAddStockDialog() {
    const emptySlots = document.querySelectorAll("#stocks-grid li.empty-slot");
    const addStockDialog = document.getElementById("add-stock-dialog");
    const replaceButtons = document.querySelectorAll("#stocks-grid button[data-button-text='replace']");
    
    emptySlots.forEach(slot => {
        slot.addEventListener("click", function () {
            addStockDialog.style.display = 'block';
        });
    });
    
    replaceButtons.forEach(button => {
        button.addEventListener("click", function (event) {
            event.stopPropagation(); // Prevents triggering section click event
            addStockDialog.style.display = 'block';
        });
    });
    
    document.querySelector("#add-stock-dialog .button-close").addEventListener("click", function () {
        addStockDialog.style.display = 'none';
    });
    
    document.querySelector("#button-group button[data-button-text='cancel']").addEventListener("click", function () {
        addStockDialog.style.display = 'none';
    });
}

/**
 * Updates the copyright year dynamically to always reflect the current year.
 */
function updateCopyrightYear() {
    const currentYear = new Date().getFullYear();
    document.getElementById("current-year").textContent = currentYear;
}
