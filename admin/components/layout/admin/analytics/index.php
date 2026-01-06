<?php
/**
 * Layout Component - Analytics Dashboard
 * Tracking, dashboards, and reports
 */

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/analytics.php';
require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Analytics Dashboard', true, 'layout_analytics');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Analytics Dashboard</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

// Get filters
$filters = [
    'event_type' => $_GET['event_type'] ?? null,
    'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
    'date_to' => $_GET['date_to'] ?? date('Y-m-d')
];

$report = layout_analytics_get_report($filters);

// Calculate statistics
$totalEvents = count($report);
$eventsByType = [];
foreach ($report as $row) {
    $type = $row['event_type'];
    if (!isset($eventsByType[$type])) {
        $eventsByType[$type] = 0;
    }
    $eventsByType[$type] += (int)$row['count'];
}

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Analytics Dashboard</h1>
    </div>

    <!-- Filters -->
    <div class="section">
        <h2>Filters</h2>
        <form method="get" class="form">
            <div class="form-group">
                <label for="event_type">Event Type</label>
                <select name="event_type" id="event_type" class="form-control">
                    <option value="">All Events</option>
                    <option value="view" <?php echo $filters['event_type'] === 'view' ? 'selected' : ''; ?>>View</option>
                    <option value="edit" <?php echo $filters['event_type'] === 'edit' ? 'selected' : ''; ?>>Edit</option>
                    <option value="create" <?php echo $filters['event_type'] === 'create' ? 'selected' : ''; ?>>Create</option>
                    <option value="delete" <?php echo $filters['event_type'] === 'delete' ? 'selected' : ''; ?>>Delete</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="date_from">From Date</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
            </div>
            
            <div class="form-group">
                <label for="date_to">To Date</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Apply Filters</button>
        </form>
    </div>

    <!-- Statistics -->
    <div class="section">
        <h2>Statistics</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalEvents; ?></div>
                <div class="stat-label">Total Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($eventsByType); ?></div>
                <div class="stat-label">Event Types</div>
            </div>
        </div>
    </div>

    <!-- Events by Type -->
    <div class="section">
        <h2>Events by Type</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Event Type</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($eventsByType)): ?>
                    <tr>
                        <td colspan="2" class="text-center">No events found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($eventsByType as $type => $count): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($type); ?></td>
                        <td><?php echo $count; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Report Data -->
    <div class="section">
        <h2>Detailed Report</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Event Type</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report)): ?>
                    <tr>
                        <td colspan="3" class="text-center">No data available</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($report as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['date']); ?></td>
                        <td><?php echo htmlspecialchars($row['event_type']); ?></td>
                        <td><?php echo $row['count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-card {
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
}

.stat-value {
    font-size: 2.5em;
    font-weight: bold;
    color: #007bff;
}

.stat-label {
    color: #666;
    margin-top: 0.5rem;
}
</style>

<?php
if ($hasBaseLayout) {
    endLayout();
} else {
    ?>
    </body>
    </html>
    <?php
}
?>

