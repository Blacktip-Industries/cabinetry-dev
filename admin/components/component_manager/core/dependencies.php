<?php
/**
 * Component Manager - Dependency Functions
 * Dependency checking and ordering
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check component dependencies
 * @param string $componentName Component name
 * @return array Dependency check result
 */
function component_manager_check_dependencies($componentName) {
    $component = component_manager_get_component($componentName);
    if (!$component) {
        return ['success' => false, 'error' => 'Component not found'];
    }
    
    $dependencies = $component['dependencies'] ?? [];
    $results = [
        'met' => [],
        'unmet' => [],
        'warnings' => []
    ];
    
    foreach ($dependencies as $dep) {
        $depName = is_array($dep) ? $dep['name'] : $dep;
        $requiredVersion = is_array($dep) ? ($dep['version'] ?? null) : null;
        
        $depComponent = component_manager_get_component($depName);
        if ($depComponent) {
            $installedVersion = $depComponent['installed_version'];
            
            // Check version if required
            if ($requiredVersion !== null) {
                if (component_manager_compare_versions($installedVersion, $requiredVersion) >= 0) {
                    $results['met'][] = ['name' => $depName, 'version' => $installedVersion, 'required' => $requiredVersion];
                } else {
                    $results['unmet'][] = ['name' => $depName, 'version' => $installedVersion, 'required' => $requiredVersion];
                    $results['warnings'][] = "Dependency {$depName} version {$installedVersion} does not meet requirement {$requiredVersion}";
                }
            } else {
                $results['met'][] = ['name' => $depName, 'version' => $installedVersion];
            }
        } else {
            $results['unmet'][] = ['name' => $depName, 'version' => null];
            $results['warnings'][] = "Dependency {$depName} is not installed";
        }
    }
    
    // Update dependency status
    $dependenciesStatus = empty($results['unmet']) ? 'met' : 'unmet';
    $conn = component_manager_get_db_connection();
    if ($conn !== null) {
        $tableName = component_manager_get_table_name('registry');
        $warningsJson = json_encode($results['warnings']);
        $stmt = $conn->prepare("UPDATE {$tableName} SET dependencies_status = ?, dependencies_warnings = ? WHERE component_name = ?");
        $stmt->bind_param("sss", $dependenciesStatus, $warningsJson, $componentName);
        $stmt->execute();
        $stmt->close();
    }
    
    return $results;
}

/**
 * Get dependency warnings
 * @param string $componentName Component name
 * @return array Dependency warnings
 */
function component_manager_get_dependency_warnings($componentName) {
    $component = component_manager_get_component($componentName);
    if (!$component) {
        return [];
    }
    
    return $component['dependencies_warnings'] ?? [];
}

/**
 * Check if dependency is met
 * @param string $componentName Component name
 * @param string $dependencyName Dependency name
 * @param string|null $requiredVersion Required version
 * @return bool True if met
 */
function component_manager_is_dependency_met($componentName, $dependencyName, $requiredVersion = null) {
    $depComponent = component_manager_get_component($dependencyName);
    if (!$depComponent) {
        return false;
    }
    
    if ($requiredVersion !== null) {
        $installedVersion = $depComponent['installed_version'];
        return component_manager_compare_versions($installedVersion, $requiredVersion) >= 0;
    }
    
    return true;
}

/**
 * Get dependency tree
 * @param string $componentName Component name
 * @return array Dependency tree
 */
function component_manager_get_dependency_tree($componentName) {
    $tree = [];
    $visited = [];
    
    function buildTree($name, &$tree, &$visited) {
        if (isset($visited[$name])) {
            return;
        }
        
        $visited[$name] = true;
        $component = component_manager_get_component($name);
        if (!$component) {
            return;
        }
        
        $dependencies = $component['dependencies'] ?? [];
        $tree[$name] = [];
        
        foreach ($dependencies as $dep) {
            $depName = is_array($dep) ? $dep['name'] : $dep;
            buildTree($depName, $tree, $visited);
            $tree[$name][] = $depName;
        }
    }
    
    buildTree($componentName, $tree, $visited);
    return $tree;
}

/**
 * Validate dependencies before operation
 * @param string $componentName Component name
 * @param string $operation Operation type
 * @return array Validation result
 */
function component_manager_validate_dependencies($componentName, $operation = 'update') {
    $check = component_manager_check_dependencies($componentName);
    
    return [
        'valid' => empty($check['unmet']),
        'warnings' => $check['warnings'],
        'unmet' => $check['unmet']
    ];
}

/**
 * Get installation order (topological sort by dependencies)
 * @param array $componentNames Component names
 * @param bool $includeInstalled Include already installed components
 * @return array Components in installation order
 */
function component_manager_get_installation_order($componentNames = [], $includeInstalled = false) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    // Get all components if none specified
    if (empty($componentNames)) {
        $components = component_manager_list_components();
        $componentNames = array_column($components, 'component_name');
    }
    
    // Build dependency graph
    $graph = [];
    $inDegree = [];
    
    foreach ($componentNames as $name) {
        $component = component_manager_get_component($name);
        if (!$component) {
            continue;
        }
        
        if (!isset($inDegree[$name])) {
            $inDegree[$name] = 0;
        }
        
        $dependencies = $component['dependencies'] ?? [];
        $graph[$name] = [];
        
        foreach ($dependencies as $dep) {
            $depName = is_array($dep) ? $dep['name'] : $dep;
            if (in_array($depName, $componentNames)) {
                $graph[$name][] = $depName;
                if (!isset($inDegree[$depName])) {
                    $inDegree[$depName] = 0;
                }
                $inDegree[$depName]++;
            }
        }
    }
    
    // Topological sort (Kahn's algorithm)
    $queue = [];
    $result = [];
    
    // Find nodes with no incoming edges
    foreach ($inDegree as $node => $degree) {
        if ($degree === 0) {
            $queue[] = $node;
        }
    }
    
    while (!empty($queue)) {
        $node = array_shift($queue);
        $result[] = $node;
        
        if (isset($graph[$node])) {
            foreach ($graph[$node] as $neighbor) {
                $inDegree[$neighbor]--;
                if ($inDegree[$neighbor] === 0) {
                    $queue[] = $neighbor;
                }
            }
        }
    }
    
    // Check for circular dependencies
    if (count($result) < count($componentNames)) {
        return ['success' => false, 'error' => 'Circular dependencies detected', 'ordered' => $result];
    }
    
    return ['success' => true, 'ordered' => $result];
}

/**
 * Resolve dependency order for specific components
 * @param array $componentNames Component names
 * @return array Components in order with dependencies included
 */
function component_manager_resolve_dependency_order($componentNames) {
    $allComponents = [];
    $visited = [];
    
    function collectDependencies($name, &$allComponents, &$visited) {
        if (isset($visited[$name])) {
            return;
        }
        
        $visited[$name] = true;
        $component = component_manager_get_component($name);
        if (!$component) {
            return;
        }
        
        $dependencies = $component['dependencies'] ?? [];
        foreach ($dependencies as $dep) {
            $depName = is_array($dep) ? $dep['name'] : $dep;
            collectDependencies($depName, $allComponents, $visited);
        }
        
        $allComponents[] = $name;
    }
    
    foreach ($componentNames as $name) {
        collectDependencies($name, $allComponents, $visited);
    }
    
    // Get installation order for all collected components
    return component_manager_get_installation_order($allComponents);
}

/**
 * Check for circular dependencies
 * @param array $componentNames Component names
 * @return array Circular dependency chains if found
 */
function component_manager_detect_circular_dependencies($componentNames = []) {
    $orderResult = component_manager_get_installation_order($componentNames);
    
    if (!$orderResult['success']) {
        // Find the circular dependency
        $graph = [];
        foreach ($componentNames as $name) {
            $component = component_manager_get_component($name);
            if ($component) {
                $dependencies = $component['dependencies'] ?? [];
                $graph[$name] = [];
                foreach ($dependencies as $dep) {
                    $depName = is_array($dep) ? $dep['name'] : $dep;
                    if (in_array($depName, $componentNames)) {
                        $graph[$name][] = $depName;
                    }
                }
            }
        }
        
        // DFS to find cycle
        $visited = [];
        $recStack = [];
        $cycle = [];
        
        function findCycle($node, $graph, &$visited, &$recStack, &$cycle) {
            $visited[$node] = true;
            $recStack[$node] = true;
            
            if (isset($graph[$node])) {
                foreach ($graph[$node] as $neighbor) {
                    if (!isset($visited[$neighbor])) {
                        if (findCycle($neighbor, $graph, $visited, $recStack, $cycle)) {
                            $cycle[] = $node;
                            return true;
                        }
                    } elseif (isset($recStack[$neighbor]) && $recStack[$neighbor]) {
                        $cycle[] = $node;
                        $cycle[] = $neighbor;
                        return true;
                    }
                }
            }
            
            $recStack[$node] = false;
            return false;
        }
        
        foreach ($componentNames as $name) {
            if (!isset($visited[$name])) {
                if (findCycle($name, $graph, $visited, $recStack, $cycle)) {
                    return array_reverse($cycle);
                }
            }
        }
    }
    
    return [];
}

/**
 * Get missing dependencies for components
 * @param array $componentNames Component names
 * @return array Missing dependencies
 */
function component_manager_get_missing_dependencies($componentNames) {
    $missing = [];
    
    foreach ($componentNames as $name) {
        $check = component_manager_check_dependencies($name);
        if (!empty($check['unmet'])) {
            $missing[$name] = $check['unmet'];
        }
    }
    
    return $missing;
}

/**
 * Auto-install missing dependencies
 * @param string $componentName Component name
 * @param array $options Options
 * @return array Installation result
 */
function component_manager_auto_install_dependencies($componentName, $options = []) {
    $check = component_manager_check_dependencies($componentName);
    
    if (empty($check['unmet'])) {
        return ['success' => true, 'message' => 'All dependencies are met'];
    }
    
    $results = [];
    foreach ($check['unmet'] as $dep) {
        $depName = $dep['name'];
        $depPath = __DIR__ . '/../../' . $depName;
        
        if (is_dir($depPath) && file_exists($depPath . '/install.php')) {
            // Run installation
            $installResult = component_manager_install_component($depName, $options);
            $results[$depName] = $installResult;
        } else {
            $results[$depName] = ['success' => false, 'error' => 'Component not found or no installer'];
        }
    }
    
    return ['success' => true, 'results' => $results];
}

/**
 * Check dependency version requirements
 * @param string $componentName Component name
 * @param string $mode Mode (track, warn, enforce)
 * @return array Check result
 */
function component_manager_check_dependency_versions($componentName, $mode = 'track') {
    $component = component_manager_get_component($componentName);
    if (!$component) {
        return ['success' => false, 'error' => 'Component not found'];
    }
    
    $dependencies = $component['dependencies'] ?? [];
    $results = [];
    
    foreach ($dependencies as $dep) {
        if (is_array($dep) && isset($dep['version'])) {
            $depName = $dep['name'];
            $requiredVersion = $dep['version'];
            
            $depComponent = component_manager_get_component($depName);
            if ($depComponent) {
                $installedVersion = $depComponent['installed_version'];
                $meetsRequirement = component_manager_compare_versions($installedVersion, $requiredVersion) >= 0;
                
                $results[] = [
                    'dependency' => $depName,
                    'required' => $requiredVersion,
                    'installed' => $installedVersion,
                    'meets_requirement' => $meetsRequirement
                ];
            }
        }
    }
    
    return ['success' => true, 'results' => $results];
}

/**
 * Build dependency graph structure
 * @param array $componentNames Component names
 * @return array Dependency graph
 */
function component_manager_build_dependency_graph($componentNames = []) {
    $graph = [];
    
    if (empty($componentNames)) {
        $components = component_manager_list_components();
        $componentNames = array_column($components, 'component_name');
    }
    
    foreach ($componentNames as $name) {
        $component = component_manager_get_component($name);
        if ($component) {
            $dependencies = $component['dependencies'] ?? [];
            $graph[$name] = [];
            
            foreach ($dependencies as $dep) {
                $depName = is_array($dep) ? $dep['name'] : $dep;
                $graph[$name][] = $depName;
            }
        }
    }
    
    return $graph;
}

