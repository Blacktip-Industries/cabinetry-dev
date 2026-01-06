<?php
/**
 * Formula Builder Component - AI Features
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/ai.php';

$formulaId = (int)($_GET['formula_id'] ?? 0);
$formula = null;
$suggestions = [];

if ($formulaId) {
    $formula = formula_builder_get_formula_by_id($formulaId);
    if ($formula) {
        $suggestions = formula_builder_get_ai_suggestions($formulaId);
    }
}

if (!$formula) {
    header('Location: ../formulas/index.php');
    exit;
}

// Handle AI actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['suggest_code'])) {
        $context = $_POST['context'] ?? $formula['formula_code'];
        $suggestionType = $_POST['suggestion_type'] ?? 'completion';
        $result = formula_builder_ai_suggest_code($formulaId, $context, $suggestionType);
        if ($result['success']) {
            header('Location: index.php?formula_id=' . $formulaId);
            exit;
        }
    } elseif (isset($_POST['detect_errors'])) {
        $result = formula_builder_ai_detect_errors($formula['formula_code']);
        // Display results
    } elseif (isset($_POST['optimize_code'])) {
        $result = formula_builder_ai_optimize_code($formula['formula_code']);
        // Display results
    } elseif (isset($_POST['nl_to_formula'])) {
        $nl = trim($_POST['natural_language'] ?? '');
        if (!empty($nl)) {
            $result = formula_builder_ai_natural_language_to_formula($nl);
            // Display results
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>AI Features - <?php echo htmlspecialchars($formula['formula_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn-secondary { background: #6c757d; }
        .suggestion { padding: 15px; margin: 10px 0; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #007bff; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        textarea { width: 100%; padding: 8px; box-sizing: border-box; min-height: 150px; font-family: monospace; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
    </style>
</head>
<body>
    <h1>AI Features: <?php echo htmlspecialchars($formula['formula_name']); ?></h1>
    <a href="../formulas/edit.php?id=<?php echo $formulaId; ?>" class="btn btn-secondary">Back to Formula</a>
    
    <div style="margin-top: 30px;">
        <h2>Natural Language to Formula</h2>
        <form method="POST">
            <div class="form-group">
                <label for="natural_language">Describe what you want the formula to do:</label>
                <textarea id="natural_language" name="natural_language" placeholder="e.g., Calculate the total price including material cost and hardware"></textarea>
            </div>
            <button type="submit" name="nl_to_formula" class="btn">Generate Formula</button>
        </form>
    </div>
    
    <div style="margin-top: 30px;">
        <h2>Code Suggestions</h2>
        <form method="POST">
            <div class="form-group">
                <label for="context">Code Context (optional)</label>
                <textarea id="context" name="context"><?php echo htmlspecialchars($formula['formula_code']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="suggestion_type">Suggestion Type</label>
                <select id="suggestion_type" name="suggestion_type">
                    <option value="completion">Code Completion</option>
                    <option value="optimization">Optimization</option>
                    <option value="error_fix">Error Fix</option>
                </select>
            </div>
            <button type="submit" name="suggest_code" class="btn">Get Suggestions</button>
        </form>
    </div>
    
    <div style="margin-top: 30px;">
        <h2>AI Analysis</h2>
        <form method="POST" style="display: inline-block; margin-right: 10px;">
            <button type="submit" name="detect_errors" class="btn">Detect Errors</button>
        </form>
        <form method="POST" style="display: inline-block;">
            <button type="submit" name="optimize_code" class="btn">Optimize Code</button>
        </form>
    </div>
    
    <?php if (!empty($suggestions)): ?>
        <div style="margin-top: 30px;">
            <h2>Saved Suggestions (<?php echo count($suggestions); ?>)</h2>
            <?php foreach ($suggestions as $suggestion): ?>
                <div class="suggestion">
                    <strong><?php echo htmlspecialchars($suggestion['suggestion_type']); ?></strong>
                    <p><?php echo htmlspecialchars($suggestion['suggestion_text']); ?></p>
                    <small>Confidence: <?php echo $suggestion['confidence_score']; ?>%</small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>

