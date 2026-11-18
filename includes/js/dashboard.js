/* =====================================================================
   DASHBOARD CHART - units added (In) and removed (Out) per month.
   Numbers come from <script id="dashboardData"> written by dashboard.php.
   ===================================================================== */
(function () {
    "use strict";

    var dataEl = document.getElementById("dashboardData");
    var canvas = document.getElementById("activityChart");
    if (!dataEl || !canvas || typeof Chart === "undefined") { return; }

    var data = JSON.parse(dataEl.textContent);
    var fmt = new Intl.NumberFormat("en-PH");

    Chart.defaults.font.family = '"Inter", system-ui, -apple-system, sans-serif';
    Chart.defaults.font.size = 12;
    Chart.defaults.color = "#64748b";

    function bars(label, values, color, hoverColor) {
        return {
            label: label,
            data: values,
            backgroundColor: color,
            hoverBackgroundColor: hoverColor,
            borderRadius: { topLeft: 6, topRight: 6 },
            borderSkipped: "bottom",
            maxBarThickness: 18
        };
    }

    var isDark = document.documentElement.getAttribute("data-bs-theme") === "dark";

    var chart = new Chart(canvas, {
        type: "bar",
        data: {
            labels: data.labels,
            datasets: [
                bars("Stock In", data["in"], "#059669", "#047857"),
                bars("Stock Out", data.out, "#0284c7", "#0369a1")
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: "index", intersect: false },
            datasets: { bar: { categoryPercentage: 0.55, barPercentage: 0.9 } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "#0f172a",
                    padding: 12,
                    cornerRadius: 10,
                    titleFont: { weight: "600", size: 13 },
                    bodyFont: { size: 12.5 },
                    callbacks: {
                        title: function (items) { return data.full[items[0].dataIndex]; },
                        label: function (ctx) { return " " + ctx.dataset.label + ": " + fmt.format(ctx.parsed.y) + " units"; }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { color: isDark ? "#334155" : "#e2e8f0" },
                    ticks: { color: isDark ? "#94a3b8" : "#64748b", font: { weight: "500" } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: isDark ? "rgba(51, 65, 85, 0.4)" : "#f1f5f9" },
                    border: { display: false },
                    ticks: { color: isDark ? "#94a3b8" : "#64748b", maxTicksLimit: 5, callback: function (v) { return fmt.format(v); } }
                }
            }
        }
    });

    window.addEventListener("themechanged", function (e) {
        var dark = e.detail && e.detail.theme === "dark";
        chart.options.scales.x.border.color = dark ? "#334155" : "#e2e8f0";
        chart.options.scales.x.ticks.color = dark ? "#94a3b8" : "#64748b";
        chart.options.scales.y.grid.color = dark ? "rgba(51, 65, 85, 0.4)" : "#f1f5f9";
        chart.options.scales.y.ticks.color = dark ? "#94a3b8" : "#64748b";
        chart.update();
    });
})();
