<?php
/**
 * Layout Component - Animations Management
 * Manage animation definitions
 */

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/animations.php';
require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Animations Management', true, 'layout_animations');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Animations Management</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'animation_type' => $_POST['animation_type'] ?? 'css',
            'animation_data' => [
                'keyframes' => json_decode($_POST['keyframes'] ?? '[]', true) ?: []
            ],
            'duration' => (int)($_POST['duration'] ?? 1000),
            'easing' => $_POST['easing'] ?? 'ease',
            'delay' => (int)($_POST['delay'] ?? 0),
            'iterations' => $_POST['iterations'] === 'infinite' ? 'infinite' : (int)($_POST['iterations'] ?? 1),
            'direction' => $_POST['direction'] ?? 'normal'
        ];
        
        if (!empty($data['name'])) {
            $result = layout_animation_create($data);
            if ($result['success']) {
                $success = 'Animation created successfully';
            } else {
                $error = 'Failed to create animation: ' . ($result['error'] ?? 'Unknown error');
            }
        } else {
            $error = 'Please provide animation name';
        }
    } elseif ($action === 'delete') {
        $animationId = (int)($_POST['animation_id'] ?? 0);
        if ($animationId > 0) {
            $result = layout_animation_delete($animationId);
            if ($result['success']) {
                $success = 'Animation deleted successfully';
            } else {
                $error = 'Failed to delete animation: ' . ($result['error'] ?? 'Unknown error');
            }
        }
    }
}

// Get all animations
$animations = layout_animation_get_all(['limit' => 100]);

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Animations Management</h1>
        <div class="layout__actions">
            <button onclick="document.getElementById('create-form').style.display='block'" class="btn btn-primary">Create Animation</button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Create Animation Form -->
    <div id="create-form" class="section" style="display: none;">
        <h2>Create Animation</h2>
        <form method="post" class="form">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label for="name">Animation Name</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="animation_type">Animation Type</label>
                <select name="animation_type" id="animation_type" class="form-control">
                    <option value="css">CSS</option>
                    <option value="js">JavaScript</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="duration">Duration (ms)</label>
                <input type="number" name="duration" id="duration" class="form-control" value="1000" min="0">
            </div>
            
            <div class="form-group">
                <label for="easing">Easing</label>
                <select name="easing" id="easing" class="form-control">
                    <option value="ease">ease</option>
                    <option value="ease-in">ease-in</option>
                    <option value="ease-out">ease-out</option>
                    <option value="ease-in-out">ease-in-out</option>
                    <option value="linear">linear</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="delay">Delay (ms)</label>
                <input type="number" name="delay" id="delay" class="form-control" value="0" min="0">
            </div>
            
            <div class="form-group">
                <label for="iterations">Iterations</label>
                <input type="text" name="iterations" id="iterations" class="form-control" value="1" placeholder="1 or 'infinite'">
            </div>
            
            <div class="form-group">
                <label for="direction">Direction</label>
                <select name="direction" id="direction" class="form-control">
                    <option value="normal">normal</option>
                    <option value="reverse">reverse</option>
                    <option value="alternate">alternate</option>
                    <option value="alternate-reverse">alternate-reverse</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="keyframes">Keyframes</label>
                <div id="timeline-editor-container"></div>
                <textarea name="keyframes" id="keyframes" class="form-control" rows="3" style="display: none;" placeholder='[{"percent": 0, "properties": {"opacity": "0"}}, {"percent": 100, "properties": {"opacity": "1"}}]'></textarea>
                <small>Click on the timeline to add keyframes. Drag keyframes to adjust timing.</small>
            </div>
            
            <div class="form-group">
                <label>Preview</label>
                <div id="animation-preview-container" style="margin: 1rem 0; padding: 1rem; background: #f8f9fa; border-radius: 4px; min-height: 200px;">
                    <p>Preview will appear here after creating animation</p>
                </div>
                <button type="button" class="btn btn-secondary" onclick="previewAnimation()" style="margin-top: 0.5rem;">Preview Animation</button>
            </div>
            
            <button type="submit" class="btn btn-primary">Create Animation</button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('create-form').style.display='none'">Cancel</button>
        </form>
    </div>
    
    <!-- Animation Templates -->
    <div class="section">
        <h2>Animation Templates</h2>
        <p>Click a template to load it into the form above:</p>
        <div class="template-grid">
            <div class="template-item" onclick="applyTemplate('fade')">
                <h4>Fade In</h4>
                <p>Opacity: 0 → 1</p>
            </div>
            <div class="template-item" onclick="applyTemplate('slide')">
                <h4>Slide In</h4>
                <p>Transform: translateX</p>
            </div>
            <div class="template-item" onclick="applyTemplate('bounce')">
                <h4>Bounce</h4>
                <p>Transform: scale</p>
            </div>
            <div class="template-item" onclick="applyTemplate('rotate')">
                <h4>Rotate</h4>
                <p>Transform: rotate</p>
            </div>
        </div>
    </div>

    <!-- Animations List -->
    <div class="section">
        <h2>Animations</h2>
        <?php if (empty($animations)): ?>
            <p>No animations created yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Duration</th>
                        <th>Easing</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($animations as $animation): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($animation['name']); ?></td>
                        <td><?php echo htmlspecialchars($animation['animation_type']); ?></td>
                        <td><?php echo $animation['duration']; ?>ms</td>
                        <td><?php echo htmlspecialchars($animation['easing']); ?></td>
                        <td>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Delete this animation?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="animation_id" value="<?php echo $animation['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="../../assets/css/animation-timeline-editor.css">
<script src="../../assets/js/animation-timeline-editor.js"></script>

<script>
// Animation templates
const animationTemplates = {
    fade: {
        keyframes: [
            {percent: 0, properties: {opacity: '0'}},
            {percent: 100, properties: {opacity: '1'}}
        ],
        duration: 1000,
        easing: 'ease'
    },
    slide: {
        keyframes: [
            {percent: 0, properties: {transform: 'translateX(-100px)', opacity: '0'}},
            {percent: 100, properties: {transform: 'translateX(0)', opacity: '1'}}
        ],
        duration: 800,
        easing: 'ease-out'
    },
    bounce: {
        keyframes: [
            {percent: 0, properties: {transform: 'scale(0.3)', opacity: '0'}},
            {percent: 50, properties: {transform: 'scale(1.1)'}},
            {percent: 100, properties: {transform: 'scale(1)', opacity: '1'}}
        ],
        duration: 1000,
        easing: 'ease-out'
    },
    rotate: {
        keyframes: [
            {percent: 0, properties: {transform: 'rotate(0deg)', opacity: '0'}},
            {percent: 100, properties: {transform: 'rotate(360deg)', opacity: '1'}}
        ],
        duration: 1500,
        easing: 'linear'
    }
};

function applyTemplate(templateName) {
    const template = animationTemplates[templateName];
    if (!template) return;
    
    // Set form values
    document.getElementById('duration').value = template.duration;
    document.getElementById('easing').value = template.easing;
    document.getElementById('keyframes').value = JSON.stringify(template.keyframes);
    
    // Update timeline editor if initialized
    if (timelineEditor) {
        timelineEditor.setKeyframes(template.keyframes);
    }
    
    // Show create form
    document.getElementById('create-form').style.display = 'block';
}

function previewAnimation() {
    const keyframesText = document.getElementById('keyframes').value;
    const duration = document.getElementById('duration').value || 1000;
    const easing = document.getElementById('easing').value || 'ease';
    
    try {
        const keyframes = keyframesText ? JSON.parse(keyframesText) : [];
        
        // Generate preview HTML
        let keyframesCSS = '@keyframes preview-animation {\n';
        keyframes.forEach(kf => {
            keyframesCSS += `  ${kf.percent}% {\n`;
            Object.keys(kf.properties || {}).forEach(prop => {
                keyframesCSS += `    ${prop}: ${kf.properties[prop]};\n`;
            });
            keyframesCSS += '  }\n';
        });
        keyframesCSS += '}\n';
        
        const previewHTML = `
            <style>
                ${keyframesCSS}
                .preview-target {
                    width: 100px;
                    height: 100px;
                    background: #007bff;
                    border-radius: 4px;
                    margin: 20px auto;
                    animation: preview-animation ${duration}ms ${easing} infinite;
                }
            </style>
            <div class="preview-target"></div>
            <p>Duration: ${duration}ms | Easing: ${easing}</p>
        `;
        
        document.getElementById('animation-preview-container').innerHTML = previewHTML;
    } catch (e) {
        alert('Error: ' + e.message);
    }
}
</script>

<style>
.section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.template-item {
    padding: 1.5rem;
    background: #f8f9fa;
    border: 2px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s;
}

.template-item:hover {
    border-color: #007bff;
    background: #e7f3ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.template-item h4 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.template-item p {
    margin: 0;
    color: #666;
    font-size: 0.9em;
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

