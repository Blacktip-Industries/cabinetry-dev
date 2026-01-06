<?php
/**
 * Formula Builder Component - Internationalization
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/i18n.php';

$userId = $_SESSION['user_id'] ?? 1;
$currentLanguage = formula_builder_get_user_language($userId);
$availableLanguages = formula_builder_get_available_languages();

// Handle language change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_language'])) {
    $language = $_POST['language'] ?? 'en';
    $result = formula_builder_set_user_language($userId, $language);
    if ($result['success']) {
        $currentLanguage = $language;
        header('Location: index.php?updated=1');
        exit;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Internationalization - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn-secondary { background: #6c757d; }
        .language-selector { padding: 20px; background: #f5f5f5; border-radius: 4px; margin: 20px 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select { width: 100%; padding: 8px; box-sizing: border-box; }
    </style>
</head>
<body>
    <h1>Internationalization</h1>
    <a href="../formulas/index.php" class="btn btn-secondary">Back to Formulas</a>
    
    <div class="language-selector">
        <h2>Language Settings</h2>
        <form method="POST">
            <div class="form-group">
                <label for="language">Select Language</label>
                <select id="language" name="language" onchange="this.form.submit()">
                    <?php foreach ($availableLanguages as $code => $name): ?>
                        <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $currentLanguage === $code ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="set_language" class="btn">Save Language</button>
        </form>
        <p><strong>Current Language:</strong> <?php echo htmlspecialchars($availableLanguages[$currentLanguage] ?? 'English'); ?></p>
        <?php if (formula_builder_is_rtl($currentLanguage)): ?>
            <p><em>RTL (Right-to-Left) language detected</em></p>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 30px;">
        <h2>Available Languages</h2>
        <ul>
            <?php foreach ($availableLanguages as $code => $name): ?>
                <li>
                    <strong><?php echo htmlspecialchars($name); ?></strong> (<?php echo htmlspecialchars($code); ?>)
                    <?php if (formula_builder_is_rtl($code)): ?>
                        <span style="color: #666;">- RTL</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div style="margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
        <h3>Translation System</h3>
        <p>The translation system uses the <code>formula_builder_translations</code> table to store translations.</p>
        <p>Use <code>formula_builder_translate('key', 'language')</code> to get translated strings.</p>
        <p><strong>Example:</strong> <?php echo formula_builder_translate('formula.name', $currentLanguage); ?></p>
    </div>
</body>
</html>

