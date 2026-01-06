<?php
/**
 * Access Component - Hook/Event System
 * WordPress-style hooks for extending functionality
 */

/**
 * Register a hook callback
 * @param string $hookName Hook name
 * @param callable $callback Callback function
 * @param int $priority Priority (lower = earlier execution)
 * @param string $hookType 'action' or 'filter'
 * @return bool Success
 */
function access_add_hook($hookName, $callback, $priority = 10, $hookType = 'action') {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    // Store in database for persistence
    try {
        $callbackName = is_string($callback) ? $callback : (is_array($callback) ? get_class($callback[0]) . '::' . $callback[1] : 'closure');
        $stmt = $conn->prepare("INSERT INTO access_hooks (hook_name, hook_type, callback_function, priority, is_active) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE priority = VALUES(priority), is_active = 1");
        $stmt->bind_param("sssi", $hookName, $hookType, $callbackName, $priority);
        $result = $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error registering hook: " . $e->getMessage());
    }
    
    // Also store in memory for current request
    global $access_hooks;
    if (!isset($access_hooks)) {
        $access_hooks = [];
    }
    
    if (!isset($access_hooks[$hookName])) {
        $access_hooks[$hookName] = [];
    }
    
    $access_hooks[$hookName][] = [
        'callback' => $callback,
        'priority' => $priority,
        'type' => $hookType
    ];
    
    // Sort by priority
    usort($access_hooks[$hookName], function($a, $b) {
        return $a['priority'] - $b['priority'];
    });
    
    return true;
}

/**
 * Trigger an action hook
 * @param string $hookName Hook name
 * @param mixed ...$args Arguments to pass to callbacks
 * @return void
 */
function access_do_action($hookName, ...$args) {
    global $access_hooks;
    
    if (!isset($access_hooks[$hookName])) {
        return;
    }
    
    foreach ($access_hooks[$hookName] as $hook) {
        if ($hook['type'] === 'action' && is_callable($hook['callback'])) {
            call_user_func_array($hook['callback'], $args);
        }
    }
}

/**
 * Apply a filter hook
 * @param string $hookName Hook name
 * @param mixed $value Value to filter
 * @param mixed ...$args Additional arguments
 * @return mixed Filtered value
 */
function access_apply_filters($hookName, $value, ...$args) {
    global $access_hooks;
    
    if (!isset($access_hooks[$hookName])) {
        return $value;
    }
    
    foreach ($access_hooks[$hookName] as $hook) {
        if ($hook['type'] === 'filter' && is_callable($hook['callback'])) {
            $value = call_user_func_array($hook['callback'], array_merge([$value], $args));
        }
    }
    
    return $value;
}

/**
 * Remove a hook
 * @param string $hookName Hook name
 * @param callable|null $callback Callback to remove (null to remove all)
 * @return bool Success
 */
function access_remove_hook($hookName, $callback = null) {
    global $access_hooks;
    
    if (!isset($access_hooks[$hookName])) {
        return false;
    }
    
    if ($callback === null) {
        unset($access_hooks[$hookName]);
    } else {
        $access_hooks[$hookName] = array_filter($access_hooks[$hookName], function($hook) use ($callback) {
            return $hook['callback'] !== $callback;
        });
    }
    
    return true;
}

