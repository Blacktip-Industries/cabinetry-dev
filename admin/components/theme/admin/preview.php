<?php
/**
 * Theme Component - Design System Preview Page
 * Interactive preview of all design system components
 */

// Load component files
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/theme-loader.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    if (function_exists('startLayout')) {
        startLayout('Design System Preview', true, 'theme_preview');
    }
}

// Get theme parameters for display
$conn = theme_get_db_connection();
$colors = theme_get_section_parameters('colors');
$typography = theme_get_section_parameters('typography');
$spacing = theme_get_section_parameters('spacing');

if (!$hasBaseLayout || !function_exists('startLayout')) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Design System Preview - Theme Component</title>
        <?php echo theme_load_assets(true); ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Play:wght@400;500;600;700&display=swap" rel="stylesheet">
    </head>
    <body>
    <?php
}
?>

<div class="container" style="max-width: 1400px; margin: 0 auto; padding: var(--spacing-xl, 24px);">
    <!-- Header -->
    <header style="margin-bottom: var(--spacing-3xl, 48px);">
        <h1>Design System Preview</h1>
        <p class="text-muted">Theme Component - Version <?php echo theme_get_version(); ?></p>
        
        <!-- Theme Switcher -->
        <div style="margin-top: var(--spacing-lg, 16px);">
            <label for="theme-switcher">Theme: </label>
            <select id="theme-switcher" class="select" style="width: auto; display: inline-block;">
                <option value="light">Light</option>
                <option value="dark">Dark</option>
                <option value="custom">Custom</option>
            </select>
        </div>
    </header>

    <!-- Colors Section -->
    <section class="card" style="margin-bottom: var(--spacing-xl, 24px);">
        <h2 class="card-title">Colors</h2>
        <div class="card-body">
            <h3>Primary Colors</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: var(--spacing-md, 12px); margin-bottom: var(--spacing-lg, 16px);">
                <div style="text-align: center;">
                    <div style="width: 100%; height: 80px; background-color: var(--color-primary, #FF6C2F); border-radius: var(--radius-md, 8px); margin-bottom: var(--spacing-sm, 8px);"></div>
                    <div style="font-weight: var(--font-weight-semibold, 600);">Primary</div>
                    <div style="font-size: var(--font-size-small, 12px); color: var(--text-muted, #707793);">var(--color-primary)</div>
                </div>
                <div style="text-align: center;">
                    <div style="width: 100%; height: 80px; background-color: var(--color-secondary, #5D7186); border-radius: var(--radius-md, 8px); margin-bottom: var(--spacing-sm, 8px);"></div>
                    <div style="font-weight: var(--font-weight-semibold, 600);">Secondary</div>
                    <div style="font-size: var(--font-size-small, 12px); color: var(--text-muted, #707793);">var(--color-secondary)</div>
                </div>
            </div>
            
            <h3>Semantic Colors</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: var(--spacing-md, 12px);">
                <div style="text-align: center;">
                    <div style="width: 100%; height: 80px; background-color: var(--color-success, #22C55E); border-radius: var(--radius-md, 8px); margin-bottom: var(--spacing-sm, 8px);"></div>
                    <div style="font-weight: var(--font-weight-semibold, 600);">Success</div>
                </div>
                <div style="text-align: center;">
                    <div style="width: 100%; height: 80px; background-color: var(--color-danger, #EF5F5F); border-radius: var(--radius-md, 8px); margin-bottom: var(--spacing-sm, 8px);"></div>
                    <div style="font-weight: var(--font-weight-semibold, 600);">Danger</div>
                </div>
                <div style="text-align: center;">
                    <div style="width: 100%; height: 80px; background-color: var(--color-warning, #F9B931); border-radius: var(--radius-md, 8px); margin-bottom: var(--spacing-sm, 8px);"></div>
                    <div style="font-weight: var(--font-weight-semibold, 600);">Warning</div>
                </div>
                <div style="text-align: center;">
                    <div style="width: 100%; height: 80px; background-color: var(--color-info, #4ECAC2); border-radius: var(--radius-md, 8px); margin-bottom: var(--spacing-sm, 8px);"></div>
                    <div style="font-weight: var(--font-weight-semibold, 600);">Info</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Typography Section -->
    <section class="card" style="margin-bottom: var(--spacing-xl, 24px);">
        <h2 class="card-title">Typography</h2>
        <div class="card-body">
            <h1>Heading 1 - Page Titles</h1>
            <h2>Heading 2 - Section Titles</h2>
            <h3>Heading 3 - Subsection Titles</h3>
            <h4>Heading 4 - Card Titles</h4>
            <h5>Heading 5 - Small Headings</h5>
            <h6>Heading 6 - Subtle Headings</h6>
            <p>This is body text. It uses the primary font family and provides comfortable reading size for paragraphs and general content.</p>
            <p><small>This is small text for helper text, timestamps, and fine print.</small></p>
        </div>
    </section>

    <!-- Buttons Section -->
    <section class="card" style="margin-bottom: var(--spacing-xl, 24px);">
        <h2 class="card-title">Buttons</h2>
        <div class="card-body">
            <h3>Button Variants</h3>
            <div style="display: flex; gap: var(--spacing-md, 12px); flex-wrap: wrap; margin-bottom: var(--spacing-lg, 16px);">
                <button class="btn btn-primary">Primary Button</button>
                <button class="btn btn-secondary">Secondary Button</button>
            </div>
            
            <h3>Button Sizes</h3>
            <div style="display: flex; gap: var(--spacing-md, 12px); flex-wrap: wrap; align-items: center;">
                <button class="btn btn-primary btn-sm">Small</button>
                <button class="btn btn-primary btn-md">Medium</button>
                <button class="btn btn-primary btn-lg">Large</button>
            </div>
        </div>
    </section>

    <!-- Forms Section -->
    <section class="card" style="margin-bottom: var(--spacing-xl, 24px);">
        <h2 class="card-title">Forms</h2>
        <div class="card-body">
            <div class="input-group">
                <label class="input-label">Text Input</label>
                <input type="text" class="input" placeholder="Enter text...">
            </div>
            
            <div class="input-group">
                <label class="input-label">Select</label>
                <select class="select">
                    <option>Option 1</option>
                    <option>Option 2</option>
                    <option>Option 3</option>
                </select>
            </div>
            
            <div class="input-group">
                <label class="input-label">Textarea</label>
                <textarea class="textarea" placeholder="Enter text..."></textarea>
            </div>
            
            <div class="input-group">
                <label><input type="checkbox" class="checkbox"> Checkbox Option</label>
            </div>
            
            <div class="input-group">
                <label><input type="radio" class="radio" name="radio"> Radio Option 1</label>
                <label><input type="radio" class="radio" name="radio"> Radio Option 2</label>
            </div>
        </div>
    </section>

    <!-- Cards Section -->
    <section class="card" style="margin-bottom: var(--spacing-xl, 24px);">
        <h2 class="card-title">Cards</h2>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--spacing-lg, 16px);">
                <div class="card">
                    <h4 class="card-title">Default Card</h4>
                    <p>This is a default card with standard shadow.</p>
                </div>
                <div class="card card-elevated">
                    <h4 class="card-title">Elevated Card</h4>
                    <p>This card has a larger shadow for emphasis.</p>
                </div>
                <div class="card card-outlined">
                    <h4 class="card-title">Outlined Card</h4>
                    <p>This card uses a border instead of shadow.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Badges Section -->
    <section class="card" style="margin-bottom: var(--spacing-xl, 24px);">
        <h2 class="card-title">Badges</h2>
        <div class="card-body">
            <div style="display: flex; gap: var(--spacing-md, 12px); flex-wrap: wrap;">
                <span class="badge badge-primary">Primary</span>
                <span class="badge badge-success">Success</span>
                <span class="badge badge-danger">Danger</span>
                <span class="badge badge-warning">Warning</span>
                <span class="badge badge-info">Info</span>
                <span class="badge badge-secondary">Secondary</span>
            </div>
        </div>
    </section>

    <!-- Alerts Section -->
    <section class="card" style="margin-bottom: var(--spacing-xl, 24px);">
        <h2 class="card-title">Alerts</h2>
        <div class="card-body">
            <div class="alert alert-success">
                <strong>Success:</strong> Operation completed successfully!
            </div>
            <div class="alert alert-danger">
                <strong>Error:</strong> Something went wrong.
            </div>
            <div class="alert alert-warning">
                <strong>Warning:</strong> Please review this information.
            </div>
            <div class="alert alert-info">
                <strong>Info:</strong> Here's some helpful information.
            </div>
        </div>
    </section>

    <!-- Tables Section -->
    <section class="card" style="margin-bottom: var(--spacing-xl, 24px);">
        <h2 class="card-title">Tables</h2>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>John Doe</td>
                        <td>john@example.com</td>
                        <td><span class="badge badge-success">Active</span></td>
                    </tr>
                    <tr>
                        <td>Jane Smith</td>
                        <td>jane@example.com</td>
                        <td><span class="badge badge-warning">Pending</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Navigation Section -->
    <section class="card" style="margin-bottom: var(--spacing-xl, 24px);">
        <h2 class="card-title">Navigation</h2>
        <div class="card-body">
            <h3>Breadcrumb</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Category</a></li>
                    <li class="breadcrumb-item active">Current Page</li>
                </ol>
            </nav>
            
            <h3 style="margin-top: var(--spacing-xl, 24px);">Pagination</h3>
            <nav>
                <ul class="pagination">
                    <li class="pagination-item"><a href="#" class="pagination-link">Previous</a></li>
                    <li class="pagination-item"><a href="#" class="pagination-link">1</a></li>
                    <li class="pagination-item"><a href="#" class="pagination-link active">2</a></li>
                    <li class="pagination-item"><a href="#" class="pagination-link">3</a></li>
                    <li class="pagination-item"><a href="#" class="pagination-link">Next</a></li>
                </ul>
            </nav>
        </div>
    </section>

    <!-- Progress Section -->
    <section class="card" style="margin-bottom: var(--spacing-xl, 24px);">
        <h2 class="card-title">Progress</h2>
        <div class="card-body">
            <div class="progress" style="margin-bottom: var(--spacing-md, 12px);">
                <div class="progress-bar" style="width: 25%;"></div>
            </div>
            <div class="progress" style="margin-bottom: var(--spacing-md, 12px);">
                <div class="progress-bar progress-bar-success" style="width: 50%;"></div>
            </div>
            <div class="progress">
                <div class="progress-bar progress-bar-danger" style="width: 75%;"></div>
            </div>
        </div>
    </section>

    <!-- Avatars Section -->
    <section class="card" style="margin-bottom: var(--spacing-xl, 24px);">
        <h2 class="card-title">Avatars</h2>
        <div class="card-body">
            <div style="display: flex; gap: var(--spacing-md, 12px); align-items: center;">
                <div class="avatar avatar-sm">JD</div>
                <div class="avatar avatar-md">JD</div>
                <div class="avatar avatar-lg">JD</div>
            </div>
        </div>
    </section>

    <!-- Dividers Section -->
    <section class="card" style="margin-bottom: var(--spacing-xl, 24px);">
        <h2 class="card-title">Dividers</h2>
        <div class="card-body">
            <p>Content above</p>
            <hr class="divider">
            <p>Content below</p>
        </div>
    </section>

    <!-- Empty States Section -->
    <section class="card" style="margin-bottom: var(--spacing-xl, 24px);">
        <h2 class="card-title">Empty States</h2>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3 class="empty-state-title">No Items Found</h3>
                <p class="empty-state-description">There are no items to display at this time.</p>
                <div class="empty-state-action">
                    <button class="btn btn-primary">Add Item</button>
                </div>
            </div>
        </div>
    </section>
</div>

<?php echo theme_load_js(false); ?>
<script src="<?php echo theme_get_js_url(); ?>/preview.js"></script>

<?php
if ($hasBaseLayout && function_exists('endLayout')) {
    endLayout();
} else {
    ?>
    </body>
    </html>
    <?php
}
?>

