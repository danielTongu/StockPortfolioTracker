/**
 * Wait for the DOM to be fully loaded before rendering the chart.
 */
document.addEventListener("DOMContentLoaded", function () {
    // Only render the chart; time frame redirection is handled inline.
    renderStockChart();
});

/**
 * Renders the stock chart using Chart.js.
 *
 * @returns {void}
 */
function renderStockChart() {
    // Get the canvas element.
    var canvas = document.getElementById("stockChart");
    if (!canvas) {
        console.error("Canvas element for stock chart not found.");
        return;
    }

    // Get the 2D drawing context.
    var ctx = canvas.getContext("2d");

    // Retrieve the chart data from the data attribute.
    var chartData;
    try {
        chartData = JSON.parse(canvas.dataset.chart);
    } catch (error) {
        console.error("Failed to parse chart data:", error);
        return;
    }

    // Ensure required chart data fields are present.
    if (!chartData.dates || !chartData.prices) {
        console.error("Chart data is missing required fields:", chartData);
        return;
    }

    // Extract the data.
    var dates = chartData.dates;
    var prices = chartData.prices;
    var additionalStats = chartData.additionalStats;
    var defaultTimeUnit = chartData.timeUnit;
    var changePercent = chartData.changePercent;
    var selectedTimeFrame = chartData.timeFrame;

    // Determine colors based on overall percentage change.
    var colors = getOverallColor(changePercent);

    // Determine the time scale options based on the selected time frame.
    // For "1D", use hourly unit with a custom display format.
    var timeOptions = {};
    if (selectedTimeFrame === "1D") {
        timeOptions = {
            unit: 'hour',
            displayFormats: {
                hour: 'ha' // e.g., "2PM" format.
            }
        };
    } else {
        // Use the default time unit provided by the controller.
        timeOptions = {
            unit: defaultTimeUnit
        };
    }

    // Calculate the maximum date from the chart data.
    var dateObjects = dates.map(function (dateStr) {
        return new Date(dateStr);
    });
    // Determine the latest date among the data points.
    var maxDate = new Date(Math.max.apply(null, dateObjects));

    // Calculate the minimum date based on the selected time frame.
    var minDate = null;
    switch (selectedTimeFrame) {
        case "1D":
            minDate = new Date(maxDate);
            minDate.setDate(minDate.getDate() - 1);
            break;
        case "5D":
            minDate = new Date(maxDate);
            minDate.setDate(minDate.getDate() - 5);
            break;
        case "1M":
            minDate = new Date(maxDate);
            minDate.setMonth(minDate.getMonth() - 1);
            break;
        case "6M":
            minDate = new Date(maxDate);
            minDate.setMonth(minDate.getMonth() - 6);
            break;
        case "YTD":
            // Year-to-date: January 1st of the current year.
            minDate = new Date(maxDate.getFullYear(), 0, 1);
            break;
        case "1Y":
            minDate = new Date(maxDate);
            minDate.setFullYear(minDate.getFullYear() - 1);
            break;
        case "5Y":
            minDate = new Date(maxDate);
            minDate.setFullYear(minDate.getFullYear() - 5);
            break;
        default:
            // For "ALL" or unspecified time frames, do not restrict the date range.
            minDate = null;
            break;
    }

    // Destroy previous chart instance if it exists.
    if (window.stockChartInstance) {
        window.stockChartInstance.destroy();
    }

    // Create a new chart instance using Chart.js.
    window.stockChartInstance = new Chart(ctx, {
        type: "line",
        data: {
            labels: dates,
            datasets: [{
                    label: chartData.symbol + " Price",
                    data: prices,
                    borderColor: colors.borderColor,
                    backgroundColor: colors.backgroundColor,
                    fill: true,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: colors.borderColor,
                    tension: 0.1, // Smooth curves.
                }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: "time",
                    time: timeOptions,
                    // Set the min and max dates if applicable.
                    min: minDate,
                    max: maxDate,
                    title: {
                        display: true,
                        text: "Time",
                        color: "#1f1f1f",
                        font: {size: 20},
                    },
                    grid: {
                        color: "rgb(31,31,31)",
                        drawOnChartArea: true,
                        drawTicks: true,
                    },
                    ticks: {
                        color: "#e0e0e0",
                        maxTicksLimit: 10,
                        autoSkip: true,
                    },
                },
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: "Price",
                        color: "#1f1f1f",
                        font: {size: 20},
                    },
                    grid: {
                        color: "rgb(31,31,31)",
                        drawOnChartArea: true,
                        drawTicks: true,
                    },
                    ticks: {
                        color: "#e0e0e0",
                        callback: function (value) {
                            return formatPrice(value);
                        },
                    },
                },
            },
            plugins: {
                tooltip: {
                    backgroundColor: "rgba(255,255,255, 0.9)",
                    borderColor: colors.borderColor,
                    borderWidth: 3,
                    titleColor: colors.borderColor,
                    bodyColor: "#000000",
                    padding: 10,
                    callbacks: {
                        label: function (context) {
                            var index = context.dataIndex;
                            var stat = additionalStats[index];
                            if (!stat) {
                                return "Loading data. Click another timeline tab, then revisit this.";
                            }
                            return [
                                "Close: " + formatPrice(stat.close),
                                "Open: " + formatPrice(stat.open),
                                "High: " + formatPrice(stat.high),
                                "Low: " + formatPrice(stat.low),
                                "Volume: " + stat.volume.toLocaleString()
                            ];
                        },
                    },
                },
                legend: {
                    labels: {color: "#1f1f1f"},
                },
            },
        },
    });
}

/**
 * Determines the color for the chart based on overall percentage change.
 *
 * @param {number} percentageChange - The overall percentage change.
 * @returns {Object} An object containing borderColor and backgroundColor.
 */
function getOverallColor(percentageChange) {
    var borderColor;
    var backgroundColor;

    if (percentageChange < 0) {
        borderColor = "red";
        backgroundColor = "rgba(255, 0, 0, 0.1)";
    } else if (percentageChange > 0) {
        borderColor = "green";
        backgroundColor = "rgba(0, 255, 0, 0.1)";
    } else {
        borderColor = "blue";
        backgroundColor = "rgba(0, 0, 255, 0.1)";
    }
    return {borderColor: borderColor, backgroundColor: backgroundColor};
}

/**
 * Formats a price value to two decimal places.
 *
 * @param {number} value - The price value.
 * @returns {string} The formatted price.
 */
function formatPrice(value) {
    return parseFloat(value).toFixed(2);
}