<?php
/**
 * Layout Component - Monitoring Dashboard
 * Track metrics and make decisions about advanced features
 */

// Load component files
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/monitoring.php';
require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Monitoring Dashboard', true, 'layout_monitoring');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Monitoring Dashboard</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_metrics') {
        // Update manual metrics
        if (isset($_POST['manual_user_count'])) {
            layout_monitoring_set_manual_user_count((int)$_POST['manual_user_count']);
        }
        if (isset($_POST['project_count'])) {
            layout_monitoring_set_project_count((int)$_POST['project_count']);
        }
        $success = 'Metrics updated successfully';
    } elseif ($action === 'add_feature_request') {
        $request = [
            'phase' => (int)($_POST['phase'] ?? 0),
            'feature_name' => $_POST['feature_name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'use_case' => $_POST['use_case'] ?? '',
            'users_affected' => (int)($_POST['users_affected'] ?? 1),
            'frequency' => (int)($_POST['frequency'] ?? 1),
            'time_impact' => (int)($_POST['time_impact'] ?? 1),
            'business_impact' => (int)($_POST['business_impact'] ?? 1),
            'dev_time' => (int)($_POST['dev_time'] ?? 4),
            'complexity' => (int)($_POST['complexity'] ?? 4),
            'dependencies' => (int)($_POST['dependencies'] ?? 4),
            'status' => 'pending'
        ];
        
        $result = layout_monitoring_save_feature_request($request);
        if ($result['success']) {
            $success = 'Feature request added successfully';
        } else {
            $error = 'Failed to save feature request';
        }
    } elseif ($action === 'add_pain_point') {
        $painPoint = [
            'category' => $_POST['category'] ?? '',
            'description' => $_POST['description'] ?? '',
            'severity' => $_POST['severity'] ?? 'medium',
            'status' => 'open'
        ];
        
        $result = layout_monitoring_save_pain_point($painPoint);
        if ($result['success']) {
            $success = 'Pain point added successfully';
        } else {
            $error = 'Failed to save pain point';
        }
    } elseif ($action === 'delete_feature_request') {
        $requestId = $_POST['request_id'] ?? '';
        if ($requestId) {
            layout_monitoring_delete_feature_request($requestId);
            $success = 'Feature request deleted';
        }
    } elseif ($action === 'delete_pain_point') {
        $painPointId = $_POST['pain_point_id'] ?? '';
        if ($painPointId) {
            layout_monitoring_delete_pain_point($painPointId);
            $success = 'Pain point deleted';
        }
    }
}

// Get metrics
$metrics = layout_monitoring_get_metrics();
$recommendations = layout_monitoring_get_recommendations($metrics);
$checklistStatus = layout_monitoring_get_checklist_status($metrics);
$featureRequests = layout_monitoring_get_feature_requests();
$painPoints = layout_monitoring_get_pain_points();

// Sort feature requests by priority score
usort($featureRequests, function($a, $b) {
    $scoreA = $a['priority_score'] ?? 0;
    $scoreB = $b['priority_score'] ?? 0;
    return $scoreB <=> $scoreA;
});

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Monitoring Dashboard</h1>
        <div class="layout__actions">
            <a href="../element-templates/index.php" class="btn btn-secondary">Templates</a>
            <a href="../design-systems/index.php" class="btn btn-secondary">Design Systems</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Metrics Summary -->
    <div class="monitoring-section">
        <h2>Metrics Summary</h2>
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-label">Templates</div>
                <div class="metric-value"><?php echo $metrics['templates']['total']; ?></div>
                <div class="metric-detail">
                    <span class="badge badge-success"><?php echo $metrics['templates']['published']; ?> Published</span>
                    <span class="badge"><?php echo $metrics['templates']['draft']; ?> Draft</span>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Design Systems</div>
                <div class="metric-value"><?php echo $metrics['design_systems']['total']; ?></div>
                <div class="metric-detail">
                    <span class="badge badge-success"><?php echo $metrics['design_systems']['published']; ?> Published</span>
                    <span class="badge"><?php echo $metrics['design_systems']['draft']; ?> Draft</span>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Active Users</div>
                <div class="metric-value"><?php echo $metrics['users']['count']; ?></div>
                <div class="metric-detail">
                    <?php if ($metrics['users']['manual'] > 0): ?>
                        <small>Manual entry</small>
                    <?php else: ?>
                        <small>From audit logs (last 30 days)</small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Projects</div>
                <div class="metric-value"><?php echo $metrics['projects']['count']; ?></div>
                <div class="metric-detail">
                    <small>Manual entry</small>
                </div>
            </div>
        </div>

        <!-- Update Manual Metrics -->
        <div class="metrics-update-form">
            <h3>Update Manual Metrics</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_metrics">
                <div class="form-row">
                    <div class="form-group">
                        <label>Manual User Count</label>
                        <input type="number" name="manual_user_count" value="<?php echo $metrics['users']['manual']; ?>" min="0">
                        <small>Override automatic count from audit logs</small>
                    </div>
                    <div class="form-group">
                        <label>Project Count</label>
                        <input type="number" name="project_count" value="<?php echo $metrics['projects']['count']; ?>" min="0">
                        <small>Number of projects using templates</small>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Update Metrics</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Recommendations -->
    <?php if (!empty($recommendations)): ?>
    <div class="monitoring-section">
        <h2>Feature Recommendations</h2>
        <div class="recommendations-list">
            <?php foreach ($recommendations as $key => $rec): ?>
                <div class="recommendation-item recommendation-<?php echo $rec['priority']; ?>">
                    <div class="recommendation-header">
                        <strong>Phase <?php echo $rec['phase']; ?></strong>
                        <span class="badge badge-<?php echo $rec['priority']; ?>"><?php echo ucfirst($rec['priority']); ?> Priority</span>
                    </div>
                    <p><?php echo htmlspecialchars($rec['reason']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Monitoring Checklist -->
    <div class="monitoring-section">
        <h2>Monitoring Checklist</h2>
        <div class="checklist-grid">
            <div class="checklist-group">
                <h3>Template Count</h3>
                <label class="checklist-item <?php echo $checklistStatus['templates_0_20'] ? 'active' : ''; ?>">
                    <input type="checkbox" <?php echo $checklistStatus['templates_0_20'] ? 'checked' : ''; ?> disabled>
                    0-20 templates (Basic features sufficient)
                </label>
                <label class="checklist-item <?php echo $checklistStatus['templates_21_50'] ? 'active' : ''; ?>">
                    <input type="checkbox" <?php echo $checklistStatus['templates_21_50'] ? 'checked' : ''; ?> disabled>
                    21-50 templates (Consider Organization & Search)
                </label>
                <label class="checklist-item <?php echo $checklistStatus['templates_51_plus'] ? 'active' : ''; ?>">
                    <input type="checkbox" <?php echo $checklistStatus['templates_51_plus'] ? 'checked' : ''; ?> disabled>
                    51+ templates (Organization & Search recommended)
                </label>
            </div>
            <div class="checklist-group">
                <h3>User Count</h3>
                <label class="checklist-item <?php echo $checklistStatus['users_1'] ? 'active' : ''; ?>">
                    <input type="checkbox" <?php echo $checklistStatus['users_1'] ? 'checked' : ''; ?> disabled>
                    1 user (Skip Collaboration, Advanced Permissions)
                </label>
                <label class="checklist-item <?php echo $checklistStatus['users_2_3'] ? 'active' : ''; ?>">
                    <input type="checkbox" <?php echo $checklistStatus['users_2_3'] ? 'checked' : ''; ?> disabled>
                    2-3 users (Consider Collaboration)
                </label>
                <label class="checklist-item <?php echo $checklistStatus['users_4_plus'] ? 'active' : ''; ?>">
                    <input type="checkbox" <?php echo $checklistStatus['users_4_plus'] ? 'checked' : ''; ?> disabled>
                    4+ users (Collaboration recommended)
                </label>
            </div>
            <div class="checklist-group">
                <h3>Project Count</h3>
                <label class="checklist-item <?php echo $checklistStatus['projects_1'] ? 'active' : ''; ?>">
                    <input type="checkbox" <?php echo $checklistStatus['projects_1'] ? 'checked' : ''; ?> disabled>
                    1 project (Skip Marketplace)
                </label>
                <label class="checklist-item <?php echo $checklistStatus['projects_2_3'] ? 'active' : ''; ?>">
                    <input type="checkbox" <?php echo $checklistStatus['projects_2_3'] ? 'checked' : ''; ?> disabled>
                    2-3 projects (Consider Marketplace)
                </label>
                <label class="checklist-item <?php echo $checklistStatus['projects_4_plus'] ? 'active' : ''; ?>">
                    <input type="checkbox" <?php echo $checklistStatus['projects_4_plus'] ? 'checked' : ''; ?> disabled>
                    4+ projects (Marketplace recommended)
                </label>
            </div>
        </div>
    </div>

    <!-- Feature Requests -->
    <div class="monitoring-section">
        <h2>Feature Requests</h2>
        
        <!-- Add Feature Request Form -->
        <div class="feature-request-form">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('add-request-form').style.display='block'">Add Feature Request</button>
            <div id="add-request-form" style="display:none; margin-top: 20px;">
                <form method="POST">
                    <input type="hidden" name="action" value="add_feature_request">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phase Number</label>
                            <input type="number" name="phase" min="11" max="22" required>
                        </div>
                        <div class="form-group">
                            <label>Feature Name</label>
                            <input type="text" name="feature_name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Use Case</label>
                        <textarea name="use_case" rows="2"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Users Affected (1-4)</label>
                            <input type="number" name="users_affected" min="1" max="4" value="1" required>
                        </div>
                        <div class="form-group">
                            <label>Frequency (1-4)</label>
                            <select name="frequency" required>
                                <option value="1">Rarely</option>
                                <option value="2">Monthly</option>
                                <option value="3">Weekly</option>
                                <option value="4">Daily</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Time Impact (1-4)</label>
                            <select name="time_impact" required>
                                <option value="1"><1 hour/week</option>
                                <option value="2">1-5 hours/week</option>
                                <option value="3">6-10 hours/week</option>
                                <option value="4">10+ hours/week</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Business Impact (1-4)</label>
                            <select name="business_impact" required>
                                <option value="1">Low</option>
                                <option value="2">Medium</option>
                                <option value="3">High</option>
                                <option value="4">Critical</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Dev Time (1-4, 4=fast)</label>
                            <select name="dev_time" required>
                                <option value="4">1-2 days</option>
                                <option value="3">3-5 days</option>
                                <option value="2">1-2 weeks</option>
                                <option value="1">2+ weeks</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Complexity (1-4, 4=simple)</label>
                            <select name="complexity" required>
                                <option value="4">Low</option>
                                <option value="3">Medium</option>
                                <option value="2">High</option>
                                <option value="1">Very High</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Dependencies (1-4, 4=none)</label>
                            <select name="dependencies" required>
                                <option value="4">None</option>
                                <option value="3">Some</option>
                                <option value="2">Many</option>
                                <option value="1">Major changes</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Add Request</button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-request-form').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Feature Requests List -->
        <div class="feature-requests-list">
            <?php if (empty($featureRequests)): ?>
                <p>No feature requests yet. Add one to start tracking.</p>
            <?php else: ?>
                <?php foreach ($featureRequests as $request): ?>
                    <div class="feature-request-item">
                        <div class="request-header">
                            <div>
                                <strong>Phase <?php echo $request['phase']; ?>: <?php echo htmlspecialchars($request['feature_name'] ?? 'Unnamed'); ?></strong>
                                <span class="priority-badge priority-<?php 
                                    $score = $request['priority_score'] ?? 0;
                                    if ($score >= 1.5) echo 'critical';
                                    elseif ($score >= 1.0) echo 'high';
                                    elseif ($score >= 0.7) echo 'medium';
                                    else echo 'low';
                                ?>">
                                    Priority: <?php echo number_format($score, 2); ?>
                                </span>
                            </div>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this feature request?');">
                                <input type="hidden" name="action" value="delete_feature_request">
                                <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                        <p><?php echo htmlspecialchars($request['description'] ?? ''); ?></p>
                        <div class="request-meta">
                            <small>Created: <?php echo htmlspecialchars($request['created_at'] ?? ''); ?></small>
                            <small>Status: <?php echo htmlspecialchars($request['status'] ?? 'pending'); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pain Points -->
    <div class="monitoring-section">
        <h2>Pain Points</h2>
        
        <!-- Add Pain Point Form -->
        <div class="pain-point-form">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('add-pain-form').style.display='block'">Add Pain Point</button>
            <div id="add-pain-form" style="display:none; margin-top: 20px;">
                <form method="POST">
                    <input type="hidden" name="action" value="add_pain_point">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" required>
                                <option value="performance">Performance</option>
                                <option value="quality">Quality</option>
                                <option value="collaboration">Collaboration</option>
                                <option value="organization">Organization</option>
                                <option value="efficiency">Efficiency</option>
                                <option value="access_control">Access Control</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Severity</label>
                            <select name="severity" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" required></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Add Pain Point</button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-pain-form').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Pain Points List -->
        <div class="pain-points-list">
            <?php if (empty($painPoints)): ?>
                <p>No pain points tracked yet. Add one to start tracking issues.</p>
            <?php else: ?>
                <?php foreach ($painPoints as $point): ?>
                    <div class="pain-point-item pain-severity-<?php echo $point['severity']; ?>">
                        <div class="point-header">
                            <div>
                                <strong><?php echo htmlspecialchars(ucfirst($point['category'])); ?></strong>
                                <span class="badge badge-<?php echo $point['severity']; ?>"><?php echo ucfirst($point['severity']); ?></span>
                                <span class="badge badge-<?php echo $point['status'] === 'resolved' ? 'success' : 'warning'; ?>"><?php echo ucfirst($point['status']); ?></span>
                            </div>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this pain point?');">
                                <input type="hidden" name="action" value="delete_pain_point">
                                <input type="hidden" name="pain_point_id" value="<?php echo htmlspecialchars($point['id']); ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                        <p><?php echo htmlspecialchars($point['description'] ?? ''); ?></p>
                        <div class="point-meta">
                            <small>Created: <?php echo htmlspecialchars($point['created_at'] ?? ''); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Decision Helper -->
    <div class="monitoring-section">
        <h2>Quick Decision Helper</h2>
        <div class="decision-helper">
            <p>Use this guide to quickly decide if a feature should be implemented:</p>
            <div class="decision-matrix">
                <div class="matrix-item">
                    <strong>High Impact + Low Effort</strong> → Implement First
                </div>
                <div class="matrix-item">
                    <strong>High Impact + High Effort</strong> → Plan Carefully
                </div>
                <div class="matrix-item">
                    <strong>Low Impact + Low Effort</strong> → Maybe Later
                </div>
                <div class="matrix-item">
                    <strong>Low Impact + High Effort</strong> → Skip
                </div>
            </div>
            <div class="decision-questions">
                <h3>Quick Questions:</h3>
                <ul>
                    <li>Is this blocking production? (Yes → High Priority)</li>
                    <li>Do 3+ users need this? (Yes → High Priority)</li>
                    <li>Will this save 5+ hours/week? (Yes → High Priority)</li>
                    <li>Can we work around it? (No → High Priority)</li>
                    <li>Is it &lt;3 days to implement? (Yes → Consider)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.monitoring-section {
    background: var(--layout-color-surface, #fff);
    padding: var(--layout-spacing-lg, 20px);
    border-radius: var(--layout-border-radius-md, 8px);
    border: 1px solid var(--layout-color-border, #ddd);
    margin-bottom: var(--layout-spacing-lg, 20px);
}

.monitoring-section h2 {
    margin-top: 0;
    margin-bottom: var(--layout-spacing-md, 16px);
    font-size: 24px;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--layout-spacing-md, 16px);
    margin-bottom: var(--layout-spacing-lg, 20px);
}

.metric-card {
    background: var(--layout-color-surface-secondary, #f8f9fa);
    padding: var(--layout-spacing-md, 16px);
    border-radius: var(--layout-border-radius-sm, 4px);
    border: 1px solid var(--layout-color-border, #ddd);
    text-align: center;
}

.metric-label {
    font-size: 14px;
    color: var(--layout-color-text-secondary, #666);
    margin-bottom: 8px;
}

.metric-value {
    font-size: 32px;
    font-weight: 600;
    color: var(--layout-color-primary, #007bff);
    margin-bottom: 8px;
}

.metric-detail {
    font-size: 12px;
}

.metrics-update-form {
    margin-top: var(--layout-spacing-lg, 20px);
    padding-top: var(--layout-spacing-lg, 20px);
    border-top: 1px solid var(--layout-color-border, #ddd);
}

.recommendations-list {
    display: flex;
    flex-direction: column;
    gap: var(--layout-spacing-md, 16px);
}

.recommendation-item {
    padding: var(--layout-spacing-md, 16px);
    border-radius: var(--layout-border-radius-sm, 4px);
    border-left: 4px solid;
}

.recommendation-high {
    background: #fff3cd;
    border-left-color: #ffc107;
}

.recommendation-medium {
    background: #d1ecf1;
    border-left-color: #17a2b8;
}

.recommendation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.checklist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--layout-spacing-lg, 20px);
}

.checklist-group h3 {
    margin-top: 0;
    margin-bottom: var(--layout-spacing-sm, 8px);
    font-size: 18px;
}

.checklist-item {
    display: block;
    padding: var(--layout-spacing-sm, 8px);
    margin-bottom: 4px;
    border-radius: var(--layout-border-radius-sm, 4px);
    cursor: default;
}

.checklist-item.active {
    background: var(--layout-color-surface-secondary, #f8f9fa);
    font-weight: 600;
}

.feature-request-item,
.pain-point-item {
    background: var(--layout-color-surface-secondary, #f8f9fa);
    padding: var(--layout-spacing-md, 16px);
    border-radius: var(--layout-border-radius-sm, 4px);
    margin-bottom: var(--layout-spacing-md, 16px);
    border: 1px solid var(--layout-color-border, #ddd);
}

.request-header,
.point-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 8px;
}

.priority-badge {
    padding: 4px 8px;
    border-radius: var(--layout-border-radius-sm, 4px);
    font-size: 11px;
    font-weight: 600;
    margin-left: 8px;
}

.priority-critical {
    background: #dc3545;
    color: white;
}

.priority-high {
    background: #ffc107;
    color: #000;
}

.priority-medium {
    background: #17a2b8;
    color: white;
}

.priority-low {
    background: #6c757d;
    color: white;
}

.request-meta,
.point-meta {
    margin-top: 8px;
    font-size: 12px;
    color: var(--layout-color-text-secondary, #666);
}

.pain-severity-critical {
    border-left: 4px solid #dc3545;
}

.pain-severity-high {
    border-left: 4px solid #ffc107;
}

.pain-severity-medium {
    border-left: 4px solid #17a2b8;
}

.pain-severity-low {
    border-left: 4px solid #6c757d;
}

.decision-helper {
    background: var(--layout-color-surface-secondary, #f8f9fa);
    padding: var(--layout-spacing-md, 16px);
    border-radius: var(--layout-border-radius-sm, 4px);
}

.decision-matrix {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--layout-spacing-md, 16px);
    margin: var(--layout-spacing-md, 16px) 0;
}

.matrix-item {
    padding: var(--layout-spacing-sm, 8px);
    background: white;
    border-radius: var(--layout-border-radius-sm, 4px);
    border: 1px solid var(--layout-color-border, #ddd);
}

.decision-questions ul {
    margin: var(--layout-spacing-md, 16px) 0;
    padding-left: 20px;
}

.decision-questions li {
    margin-bottom: 8px;
}

@media (max-width: 768px) {
    .metrics-grid,
    .checklist-grid,
    .decision-matrix {
        grid-template-columns: 1fr;
    }
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

