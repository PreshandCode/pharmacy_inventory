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

    Chart.defaults.font.family = '"Inter", system-ui, sans-serif';
    Chart.defaults.font.size = 12;
    Chart.defaults.color = "#64748b";

    function bars(label, values, color) {
        return {
            label: label,
            data: values,
            backgroundColor: color,
            borderRadius: { topLeft: 4, topRight: 4 },
            borderSkipped: "bottom",
            maxBarThickness: 18
        };
    }

    new Chart(canvas, {
        type: "bar",
        data: {
            labels: data.labels,
            datasets: [bars("In", data["in"], "#16a34a"), bars("Out", data.out, "#0369a1")]
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
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        title: function (items) { return data.full[items[0].dataIndex]; },
                        label: function (ctx) { return " " + ctx.dataset.label + ": " + fmt.format(ctx.parsed.y) + " units"; }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, border: { color: "#e5e7eb" } },
                y: {
                    beginAtZero: true,
                    grid: { color: "#f1f5f9" },
                    border: { display: false },
                    ticks: { maxTicksLimit: 5, callback: function (v) { return fmt.format(v); } }
                }
            }
        }
    });
})();
