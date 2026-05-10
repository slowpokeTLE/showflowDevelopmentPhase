<?php
session_start();
require 'db.php';
require 'session_handler.php';

requireRole(['ROLE_MANAGER']);

$manager_theatre = $_SESSION['theatre_id'] ?? null;

if (!$manager_theatre) {
    die(jsonResponse('error', null, 'Theatre ID not found'));
}

$stmt = $conn->prepare("SELECT t_name, t_location FROM theatre WHERE t_id = ?");
$stmt->bind_param("i", $manager_theatre);
$stmt->execute();
$theatre = $stmt->get_result()->fetch_assoc();

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Analytics - ShowFlow</title>
    <link rel="stylesheet" href="netflix-theme.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .analytics-container {
            padding: 20px;
            background: #0f0f0f;
            min-height: 100vh;
        }

        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left h1 {
            color: #fff;
            font-size: 28px;
            margin: 0;
        }

        .header-left p {
            color: #999;
            margin: 5px 0 0 0;
            font-size: 14px;
        }

        .date-range-wrapper {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .date-range-wrapper label {
            color: #fff;
            font-size: 14px;
            font-weight: 500;
        }

        .date-range-wrapper input {
            background: #1a1a1a;
            border: 1px solid #333;
            color: #fff;
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }

        .analytics-tabs {
            display: flex;
            gap: 0;
            margin-bottom: 30px;
            border-bottom: 1px solid #333;
            flex-wrap: wrap;
        }

        .tab-button {
            background: transparent;
            border: none;
            color: #999;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }

        .tab-button:hover {
            color: #fff;
        }

        .tab-button.active {
            color: #e50914;
            border-bottom-color: #e50914;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: #1a1a1a;
            border: 1px solid #333;
            padding: 20px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .kpi-card:hover {
            border-color: #e50914;
            box-shadow: 0 0 20px rgba(229, 9, 20, 0.2);
        }

        .kpi-label {
            color: #999;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .kpi-value {
            color: #fff;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .kpi-change {
            font-size: 12px;
            font-weight: 500;
        }

        .kpi-change.positive {
            color: #22c55e;
        }

        .kpi-change.negative {
            color: #ef4444;
        }

        .chart-container {
            background: #1a1a1a;
            border: 1px solid #333;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .chart-title {
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .table-responsive {
            overflow-x: auto;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: #fff;
        }

        thead {
            border-bottom: 2px solid #333;
        }

        th {
            text-align: left;
            padding: 12px;
            color: #999;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #333;
        }

        tr:hover {
            background: rgba(229, 9, 20, 0.1);
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-high {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }

        .badge-medium {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }

        .badge-low {
            background: rgba(229, 9, 20, 0.2);
            color: #ef4444;
        }

        .filter-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .filter-btn {
            background: #1a1a1a;
            border: 1px solid #333;
            color: #fff;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #e50914;
            border-color: #e50914;
            color: #fff;
        }

        .loading {
            text-align: center;
            color: #999;
            padding: 40px;
        }

        .loading::after {
            content: '';
            display: block;
            border: 3px solid #333;
            border-top-color: #e50914;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            margin: 20px auto;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .analytics-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .kpi-grid {
                grid-template-columns: 1fr;
            }

            .date-range-wrapper {
                flex-direction: column;
                width: 100%;
            }

            .date-range-wrapper input {
                width: 100%;
            }

            .analytics-tabs {
                overflow-x: auto;
            }

            .tab-button {
                padding: 12px 15px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="analytics-container">
        <!-- Header -->
        <div class="analytics-header">
            <div class="header-left">
                <h1>📊 Advanced Analytics</h1>
                <p><?php echo htmlspecialchars($theatre['t_name']); ?> • <?php echo htmlspecialchars($theatre['t_location']); ?></p>
            </div>
            <div class="date-range-wrapper">
                <label>Date Range:</label>
                <input type="text" id="dateRange" name="dateRange" value="<?php echo date('m/d/Y', strtotime('-30 days')); ?> - <?php echo date('m/d/Y'); ?>">
            </div>
        </div>

        <!-- Tabs -->
        <div class="analytics-tabs">
            <button class="tab-button active" data-tab="revenue">💰 Revenue</button>
            <button class="tab-button" data-tab="shows">🎬 Shows</button>
            <button class="tab-button" data-tab="occupancy">🪑 Occupancy</button>
            <button class="tab-button" data-tab="food">🍿 Food Sales</button>
            <button class="tab-button" data-tab="complaints">💬 Complaints</button>
            <button class="tab-button" data-tab="forecast">🔮 Forecast</button>
        </div>

        <!-- Revenue Tab -->
        <div id="revenue" class="tab-content active">
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Total Revenue</div>
                    <div class="kpi-value" id="totalRevenue">₹0</div>
                    <div class="kpi-change positive">↑ 12% from last period</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Bookings</div>
                    <div class="kpi-value" id="totalBookings">0</div>
                    <div class="kpi-change positive">↑ 8 more bookings</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Avg. Booking Value</div>
                    <div class="kpi-value" id="avgBookingValue">₹0</div>
                    <div class="kpi-change positive">↑ ₹50 increase</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Food Revenue</div>
                    <div class="kpi-value" id="foodRevenue">₹0</div>
                    <div class="kpi-change">← No change</div>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-title">📈 Revenue Trend (Daily)</div>
                <div class="chart-wrapper">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="table-responsive">
                <div class="chart-title">Top Performing Shows</div>
                <table id="topShowsTable">
                    <thead>
                        <tr>
                            <th>Show</th>
                            <th>Movie</th>
                            <th>Date</th>
                            <th>Revenue</th>
                            <th>Bookings</th>
                            <th>Occupancy</th>
                        </tr>
                    </thead>
                    <tbody id="topShowsBody">
                        <tr>
                            <td colspan="6" class="loading">Loading data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Shows Tab -->
        <div id="shows" class="tab-content">
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Total Shows</div>
                    <div class="kpi-value" id="totalShows">0</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Best Performer</div>
                    <div class="kpi-value" id="bestShow">-</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Avg. Occupancy</div>
                    <div class="kpi-value" id="avgOccupancy">0%</div>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-title">🎬 Show Performance Ranking</div>
                <div class="chart-wrapper">
                    <canvas id="showPerformanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Occupancy Tab -->
        <div id="occupancy" class="tab-content">
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Avg. Occupancy Rate</div>
                    <div class="kpi-value" id="avgOccupancyRate">0%</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Seats Booked</div>
                    <div class="kpi-value" id="seatsBooked">0</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Total Seats</div>
                    <div class="kpi-value" id="totalSeats">0</div>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-title">🪑 Occupancy by Show</div>
                <div class="chart-wrapper">
                    <canvas id="occupancyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Food Sales Tab -->
        <div id="food" class="tab-content">
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Food Revenue</div>
                    <div class="kpi-value" id="foodRevenueKPI">₹0</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Orders</div>
                    <div class="kpi-value" id="foodOrders">0</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Avg. Order Value</div>
                    <div class="kpi-value" id="avgFoodOrder">₹0</div>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-title">🍿 Top Food Items</div>
                <div class="chart-wrapper">
                    <canvas id="foodChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Complaints Tab -->
        <div id="complaints" class="tab-content">
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Total Complaints</div>
                    <div class="kpi-value" id="totalComplaints">0</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Resolved</div>
                    <div class="kpi-value" id="resolvedComplaints">0</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Avg. Resolution Time</div>
                    <div class="kpi-value" id="avgResolutionTime">0 hrs</div>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-title">💬 Complaints by Type</div>
                <div class="chart-wrapper">
                    <canvas id="complaintChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Forecast Tab -->
        <div id="forecast" class="tab-content">
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">30-Day Forecast Revenue</div>
                    <div class="kpi-value" id="forecastRevenue">₹0</div>
                    <div class="kpi-change positive">↑ Trending up</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Confidence Level</div>
                    <div class="kpi-value" id="confidenceLevel">85%</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Predicted Occupancy</div>
                    <div class="kpi-value" id="predictedOccupancy">62%</div>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-title">🔮 Revenue Forecast (Next 30 Days)</div>
                <div class="chart-wrapper">
                    <canvas id="forecastChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        let charts = {};
        let dateRange = {
            start: null,
            end: null
        };

        // Initialize date range picker
        $(function() {
            $('#dateRange').daterangepicker({
                startDate: moment().subtract(30, 'days'),
                endDate: moment(),
                ranges: {
                    'Last 7 days': [moment().subtract(7, 'days'), moment()],
                    'Last 30 days': [moment().subtract(30, 'days'), moment()],
                    'This month': [moment().startOf('month'), moment().endOf('month')],
                    'Last month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Last year': [moment().subtract(1, 'year'), moment()]
                }
            });

            $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
                dateRange.start = picker.startDate.format('YYYY-MM-DD');
                dateRange.end = picker.endDate.format('YYYY-MM-DD');
                loadAllData();
            });

            // Initialize data
            loadAllData();
        });

        // Tab switching
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.addEventListener('click', function() {
                const tab = this.dataset.tab;
                
                document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(tab).classList.add('active');
            });
        });

        function loadAllData() {
            loadRevenueData();
            loadShowData();
            loadOccupancyData();
            loadFoodData();
            loadComplaintData();
            loadForecastData();
        }

        function loadRevenueData() {
            fetch(`api_analytics_revenue.php?start=${dateRange.start}&end=${dateRange.end}`)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        const d = data.data;
                        document.getElementById('totalRevenue').textContent = '₹' + d.total_revenue.toLocaleString();
                        document.getElementById('totalBookings').textContent = d.total_bookings;
                        document.getElementById('avgBookingValue').textContent = '₹' + (d.total_revenue / d.total_bookings || 0).toFixed(0);
                        
                        // Update chart
                        updateRevenueChart(d.daily_revenue);
                        updateShowsTable(d.top_shows);
                    }
                });
        }

        function loadShowData() {
            fetch(`api_analytics_revenue.php?start=${dateRange.start}&end=${dateRange.end}`)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        const shows = data.data.top_shows || [];
                        document.getElementById('totalShows').textContent = shows.length;
                        if (shows.length > 0) {
                            document.getElementById('bestShow').textContent = shows[0].mov_name;
                        }
                        updateShowChart(shows);
                    }
                });
        }

        function loadOccupancyData() {
            fetch(`api_analytics_occupancy.php?start=${dateRange.start}&end=${dateRange.end}`)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        const d = data.data;
                        document.getElementById('avgOccupancyRate').textContent = (d.avg_occupancy || 0).toFixed(0) + '%';
                        document.getElementById('seatsBooked').textContent = d.seats_booked || 0;
                        document.getElementById('totalSeats').textContent = d.total_seats || 0;
                        updateOccupancyChart(d.occupancy_by_show || []);
                    }
                });
        }

        function loadFoodData() {
            fetch(`api_analytics_food.php?start=${dateRange.start}&end=${dateRange.end}`)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        const d = data.data;
                        document.getElementById('foodRevenueKPI').textContent = '₹' + (d.total_revenue || 0).toLocaleString();
                        document.getElementById('foodOrders').textContent = d.total_orders || 0;
                        document.getElementById('avgFoodOrder').textContent = '₹' + ((d.total_revenue || 0) / (d.total_orders || 1)).toFixed(0);
                        updateFoodChart(d.top_items || []);
                    }
                });
        }

        function loadComplaintData() {
            fetch(`api_analytics_complaints.php?start=${dateRange.start}&end=${dateRange.end}`)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        const d = data.data;
                        document.getElementById('totalComplaints').textContent = d.total_complaints || 0;
                        document.getElementById('resolvedComplaints').textContent = d.resolved_complaints || 0;
                        document.getElementById('avgResolutionTime').textContent = (d.avg_resolution_time || 0).toFixed(1) + ' hrs';
                        updateComplaintChart(d.by_type || {});
                    }
                });
        }

        function loadForecastData() {
            fetch(`api_forecast_revenue.php?days=30`)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        const d = data.data;
                        document.getElementById('forecastRevenue').textContent = '₹' + (d.forecast_total || 0).toLocaleString();
                        document.getElementById('confidenceLevel').textContent = (d.confidence || 85) + '%';
                        document.getElementById('predictedOccupancy').textContent = (d.predicted_occupancy || 62) + '%';
                        updateForecastChart(d.daily_forecast || []);
                    }
                });
        }

        function updateRevenueChart(data) {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            if (charts.revenue) charts.revenue.destroy();
            
            charts.revenue = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.date),
                    datasets: [{
                        label: 'Daily Revenue',
                        data: data.map(d => d.revenue),
                        borderColor: '#e50914',
                        backgroundColor: 'rgba(229, 9, 20, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#333' }, ticks: { color: '#999' } },
                        x: { grid: { color: '#333' }, ticks: { color: '#999' } }
                    }
                }
            });
        }

        function updateShowChart(data) {
            const ctx = document.getElementById('showPerformanceChart').getContext('2d');
            if (charts.shows) charts.shows.destroy();
            
            charts.shows = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: (data || []).slice(0, 5).map(d => d.mov_name),
                    datasets: [{
                        label: 'Revenue',
                        data: (data || []).slice(0, 5).map(d => d.revenue),
                        backgroundColor: '#e50914'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { color: '#333' }, ticks: { color: '#999' } },
                        y: { grid: { display: false }, ticks: { color: '#999' } }
                    }
                }
            });
        }

        function updateOccupancyChart(data) {
            const ctx = document.getElementById('occupancyChart').getContext('2d');
            if (charts.occupancy) charts.occupancy.destroy();
            
            charts.occupancy = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: (data || []).map(d => d.show_id),
                    datasets: [{
                        label: 'Occupancy %',
                        data: (data || []).map(d => d.occupancy_pct),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, max: 100, grid: { color: '#333' }, ticks: { color: '#999' } },
                        x: { grid: { color: '#333' }, ticks: { color: '#999' } }
                    }
                }
            });
        }

        function updateFoodChart(data) {
            const ctx = document.getElementById('foodChart').getContext('2d');
            if (charts.food) charts.food.destroy();
            
            charts.food = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: (data || []).slice(0, 8).map(d => d.food_name),
                    datasets: [{
                        data: (data || []).slice(0, 8).map(d => d.revenue),
                        backgroundColor: ['#e50914', '#22c55e', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: '#999' } }
                    }
                }
            });
        }

        function updateComplaintChart(data) {
            const ctx = document.getElementById('complaintChart').getContext('2d');
            if (charts.complaint) charts.complaint.destroy();
            
            const types = Object.keys(data || {});
            charts.complaint = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: types,
                    datasets: [{
                        data: types.map(t => data[t]),
                        backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#22c55e', '#8b5cf6']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: '#999' } }
                    }
                }
            });
        }

        function updateForecastChart(data) {
            const ctx = document.getElementById('forecastChart').getContext('2d');
            if (charts.forecast) charts.forecast.destroy();
            
            charts.forecast = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: (data || []).map(d => d.date),
                    datasets: [{
                        label: 'Forecasted Revenue',
                        data: (data || []).map(d => d.forecast),
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        borderDash: [5, 5],
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#333' }, ticks: { color: '#999' } },
                        x: { grid: { color: '#333' }, ticks: { color: '#999' } }
                    }
                }
            });
        }

        function updateShowsTable(data) {
            const tbody = document.getElementById('topShowsBody');
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999;">No data available</td></tr>';
                return;
            }
            
            tbody.innerHTML = (data || []).map(show => `
                <tr>
                    <td>${show.show_id}</td>
                    <td>${htmlEscape(show.mov_name)}</td>
                    <td>${show.show_date}</td>
                    <td>₹${(show.revenue || 0).toLocaleString()}</td>
                    <td>${show.bookings || 0}</td>
                    <td>
                        <span class="badge ${show.occupancy_pct > 70 ? 'badge-high' : show.occupancy_pct > 40 ? 'badge-medium' : 'badge-low'}">
                            ${(show.occupancy_pct || 0).toFixed(0)}%
                        </span>
                    </td>
                </tr>
            `).join('');
        }

        function htmlEscape(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    </script>
</body>
</html>
