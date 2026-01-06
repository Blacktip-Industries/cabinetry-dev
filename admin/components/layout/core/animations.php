<?php
/**
 * Layout Component - Animation System
 * Animation engine and timeline editor
 */

require_once __DIR__ . '/database.php';

/**
 * Create animation definition
 * @param array $data Animation data
 * @return array Result with animation ID
 */
function layout_animation_create($data) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('animations');
        
        $name = $data['name'] ?? '';
        $animationType = $data['animation_type'] ?? 'css';
        $animationData = json_encode($data['animation_data'] ?? []);
        $duration = $data['duration'] ?? 1000;
        $easing = $data['easing'] ?? 'ease';
        $delay = $data['delay'] ?? 0;
        $iterations = $data['iterations'] ?? 1;
        $direction = $data['direction'] ?? 'normal';
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (name, animation_type, animation_data, duration, easing, delay, iterations, direction) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisiss", $name, $animationType, $animationData, $duration, $easing, $delay, $iterations, $direction);
        
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $id];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Animations: Error creating animation: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get animation by ID
 * @param int $animationId Animation ID
 * @return array|null Animation data
 */
function layout_animation_get($animationId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = layout_get_table_name('animations');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $animationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $animation = $result->fetch_assoc();
            $stmt->close();
            $animation['animation_data'] = json_decode($animation['animation_data'], true) ?? [];
            return $animation;
        }
        
        $stmt->close();
        return null;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Animations: Error getting animation: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all animations with optional filters
 * @param array $filters Optional filters (name, animation_type, limit)
 * @return array Array of animation data
 */
function layout_animation_get_all($filters = []) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = layout_get_table_name('animations');
        
        $query = "SELECT * FROM {$tableName} WHERE 1=1";
        $params = [];
        $types = '';
        
        if (!empty($filters['name'])) {
            $query .= " AND name LIKE ?";
            $params[] = '%' . $filters['name'] . '%';
            $types .= 's';
        }
        
        if (!empty($filters['animation_type'])) {
            $query .= " AND animation_type = ?";
            $params[] = $filters['animation_type'];
            $types .= 's';
        }
        
        $query .= " ORDER BY created_at DESC";
        
        if (!empty($filters['limit'])) {
            $query .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
            $types .= 'i';
        }
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return [];
        }
        
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $animations = [];
        while ($row = $result->fetch_assoc()) {
            $row['animation_data'] = json_decode($row['animation_data'], true) ?? [];
            $animations[] = $row;
        }
        
        $stmt->close();
        return $animations;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Animations: Error getting all animations: " . $e->getMessage());
        return [];
    }
}

/**
 * Delete animation by ID
 * @param int $animationId Animation ID
 * @return array Result with success status
 */
function layout_animation_delete($animationId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('animations');
        
        // Verify animation exists
        $animation = layout_animation_get($animationId);
        if (!$animation) {
            return ['success' => false, 'error' => 'Animation not found'];
        }
        
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error];
        }
        
        $stmt->bind_param("i", $animationId);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Animations: Error deleting animation: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Generate CSS keyframes for animation
 * @param array $animation Animation data
 * @return string CSS keyframes
 */
function layout_animation_generate_css($animation) {
    $name = 'animation-' . ($animation['id'] ?? uniqid());
    $keyframes = $animation['animation_data']['keyframes'] ?? [];
    
    if (empty($keyframes)) {
        return '';
    }
    
    $css = "@keyframes {$name} {\n";
    
    foreach ($keyframes as $keyframe) {
        $percent = $keyframe['percent'] ?? 0;
        $properties = $keyframe['properties'] ?? [];
        
        $css .= "  {$percent}% {\n";
        foreach ($properties as $prop => $value) {
            $css .= "    {$prop}: {$value};\n";
        }
        $css .= "  }\n";
    }
    
    $css .= "}\n";
    
    return $css;
}

/**
 * Generate animation CSS class
 * @param array $animation Animation data
 * @return string CSS class definition
 */
function layout_animation_generate_class($animation) {
    $name = 'animation-' . ($animation['id'] ?? uniqid());
    $duration = ($animation['duration'] ?? 1000) / 1000 . 's';
    $easing = $animation['easing'] ?? 'ease';
    $delay = ($animation['delay'] ?? 0) / 1000 . 's';
    $iterations = $animation['iterations'] ?? 1;
    $direction = $animation['direction'] ?? 'normal';
    
    return ".{$name} {\n  animation-name: {$name};\n  animation-duration: {$duration};\n  animation-timing-function: {$easing};\n  animation-delay: {$delay};\n  animation-iteration-count: {$iterations};\n  animation-direction: {$direction};\n}\n";
}

/**
 * Generate preview HTML for animation
 * @param int $animationId Animation ID
 * @param string|null $targetElement Target element selector (default: creates a div)
 * @return string HTML preview
 */
function layout_animation_preview($animationId, $targetElement = null) {
    $animation = layout_animation_get($animationId);
    if (!$animation) {
        return '<div class="preview-error">Animation not found</div>';
    }
    
    $name = 'animation-' . $animationId;
    $keyframesCSS = layout_animation_generate_css($animation);
    $classCSS = layout_animation_generate_class($animation);
    
    $target = $targetElement ?: '<div class="animation-preview-target" style="width: 100px; height: 100px; background: #007bff; border-radius: 4px;"></div>';
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animation Preview</title>
    <style>
        body {
            margin: 0;
            padding: 40px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .preview-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        ' . $keyframesCSS . '
        ' . $classCSS . '
        .animation-preview-target {
            margin: 20px auto;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <h2>' . htmlspecialchars($animation['name']) . '</h2>
        ' . str_replace('class="', 'class="' . $name . ' ', $target) . '
        <p style="margin-top: 20px; color: #666;">Duration: ' . $animation['duration'] . 'ms | Easing: ' . htmlspecialchars($animation['easing']) . '</p>
    </div>
</body>
</html>';
    
    return $html;
}

