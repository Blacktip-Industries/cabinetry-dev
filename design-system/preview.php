<?php
// Design System Preview Page
// This page showcases all components from the design system

// Database connection (optional - for demonstration)
require_once 'db-config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design System Preview - Larkon Admin Dashboard</title>
    <link rel="stylesheet" href="preview.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Play:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Icon styles using Unicode/Emoji for simplicity */
        .icon {
            display: inline-block;
            width: 22px;
            height: 22px;
            text-align: center;
            line-height: 22px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="section">
            <h1>Larkon Admin Dashboard Design System</h1>
            <p class="text-muted">Version 1.0.0 - Comprehensive component preview</p>
        </header>

        <!-- Color Palette -->
        <section class="section">
            <div class="card">
                <h2 class="card-title">Color Palette</h2>
                
                <h3>Primary Colors</h3>
                <div class="color-palette">
                    <div class="color-swatch">
                        <div class="color-box" style="background-color: #ff6c2f;"></div>
                        <div class="color-name">Primary</div>
                        <div class="color-value">#ff6c2f</div>
                    </div>
                    <div class="color-swatch">
                        <div class="color-box" style="background-color: #d95c28;"></div>
                        <div class="color-name">Primary Hover</div>
                        <div class="color-value">#d95c28</div>
                    </div>
                    <div class="color-swatch">
                        <div class="color-box" style="background-color: #ffe2d5;"></div>
                        <div class="color-name">Primary Subtle</div>
                        <div class="color-value">#ffe2d5</div>
                    </div>
                </div>

                <h3>Semantic Colors</h3>
                <div class="color-palette">
                    <div class="color-swatch">
                        <div class="color-box" style="background-color: #22c55e;"></div>
                        <div class="color-name">Success</div>
                        <div class="color-value">#22c55e</div>
                    </div>
                    <div class="color-swatch">
                        <div class="color-box" style="background-color: #ef5f5f;"></div>
                        <div class="color-name">Danger</div>
                        <div class="color-value">#ef5f5f</div>
                    </div>
                    <div class="color-swatch">
                        <div class="color-box" style="background-color: #f9b931;"></div>
                        <div class="color-name">Warning</div>
                        <div class="color-value">#f9b931</div>
                    </div>
                    <div class="color-swatch">
                        <div class="color-box" style="background-color: #4ecac2;"></div>
                        <div class="color-name">Info</div>
                        <div class="color-value">#4ecac2</div>
                    </div>
                </div>

                <h3>Neutral Colors</h3>
                <div class="color-palette">
                    <div class="color-swatch">
                        <div class="color-box" style="background-color: #f8f9fa;"></div>
                        <div class="color-name">Gray 100</div>
                        <div class="color-value">#f8f9fa</div>
                    </div>
                    <div class="color-swatch">
                        <div class="color-box" style="background-color: #eef2f7;"></div>
                        <div class="color-name">Gray 200</div>
                        <div class="color-value">#eef2f7</div>
                    </div>
                    <div class="color-swatch">
                        <div class="color-box" style="background-color: #d8dfe7;"></div>
                        <div class="color-name">Gray 300</div>
                        <div class="color-value">#d8dfe7</div>
                    </div>
                    <div class="color-swatch">
                        <div class="color-box" style="background-color: #5d7186;"></div>
                        <div class="color-name">Gray 600</div>
                        <div class="color-value">#5d7186</div>
                    </div>
                    <div class="color-swatch">
                        <div class="color-box" style="background-color: #424e5a;"></div>
                        <div class="color-name">Gray 700</div>
                        <div class="color-value">#424e5a</div>
                    </div>
                    <div class="color-swatch">
                        <div class="color-box" style="background-color: #262d34;"></div>
                        <div class="color-name">Sidebar</div>
                        <div class="color-value">#262d34</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Typography -->
        <section class="section">
            <div class="card">
                <h2 class="card-title">Typography</h2>
                
                <h1>Heading 1 - 2.5rem</h1>
                <h2>Heading 2 - 2rem</h2>
                <h3>Heading 3 - 1.75rem</h3>
                <h4>Heading 4 - 1.5rem</h4>
                <h5>Heading 5 - 1.25rem</h5>
                <h6>Heading 6 - 1rem</h6>
                
                <p>This is body text (14px) with a line height of 21px. The font family is "Play" sans-serif, providing a modern and friendly appearance.</p>
                
                <p class="text-muted">This is muted text for secondary information.</p>
                
                <small>This is small text (12px)</small><br>
                <span class="caption">This is caption text (10px, semibold)</span>
            </div>
        </section>

        <!-- Buttons -->
        <section class="section">
            <div class="card">
                <h2 class="card-title">Buttons</h2>
                
                <h3>Primary Buttons</h3>
                <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px;">
                    <button class="btn btn-primary btn-small">Small Button</button>
                    <button class="btn btn-primary btn-medium">Medium Button</button>
                    <button class="btn btn-primary btn-large">Large Button</button>
                </div>
                
                <h3>Secondary Buttons</h3>
                <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px;">
                    <button class="btn btn-secondary btn-small">Small Button</button>
                    <button class="btn btn-secondary btn-medium">Medium Button</button>
                    <button class="btn btn-secondary btn-large">Large Button</button>
                </div>
            </div>
        </section>

        <!-- Inputs -->
        <section class="section">
            <div class="card">
                <h2 class="card-title">Form Inputs</h2>
                
                <div class="input-group">
                    <label class="input-label">Text Input</label>
                    <input type="text" class="input" placeholder="Enter text here...">
                </div>
                
                <div class="input-group">
                    <label class="input-label">Email Input</label>
                    <input type="email" class="input" placeholder="email@example.com">
                </div>
                
                <div class="input-group">
                    <label class="input-label">Password Input</label>
                    <input type="password" class="input" placeholder="Enter password">
                </div>
            </div>
        </section>

        <!-- Badges -->
        <section class="section">
            <div class="card">
                <h2 class="card-title">Badges</h2>
                
                <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                    <span class="badge badge-primary">Primary</span>
                    <span class="badge badge-success">Success</span>
                    <span class="badge badge-danger">Danger</span>
                    <span class="badge badge-warning">Warning</span>
                    <span class="badge badge-info">Info</span>
                    <span class="badge badge-secondary">Secondary</span>
                </div>
            </div>
        </section>

        <!-- Alerts -->
        <section class="section">
            <div class="card">
                <h2 class="card-title">Alerts</h2>
                
                <div class="alert alert-primary">
                    <strong>Primary Alert:</strong> This is a primary alert message.
                </div>
                
                <div class="alert alert-success">
                    <strong>Success Alert:</strong> Operation completed successfully!
                </div>
                
                <div class="alert alert-danger">
                    <strong>Danger Alert:</strong> An error occurred. Please try again.
                </div>
                
                <div class="alert alert-warning">
                    <strong>Warning Alert:</strong> Please review this information carefully.
                </div>
                
                <div class="alert alert-info">
                    <strong>Info Alert:</strong> Here's some helpful information.
                </div>
            </div>
        </section>

        <!-- Tables -->
        <section class="section">
            <div class="card">
                <h2 class="card-title">Data Tables</h2>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>001</td>
                            <td>John Doe</td>
                            <td><span class="badge badge-success">Active</span></td>
                            <td>2024-01-15</td>
                            <td><button class="btn btn-secondary btn-small">Edit</button></td>
                        </tr>
                        <tr>
                            <td>002</td>
                            <td>Jane Smith</td>
                            <td><span class="badge badge-warning">Pending</span></td>
                            <td>2024-01-16</td>
                            <td><button class="btn btn-secondary btn-small">Edit</button></td>
                        </tr>
                        <tr>
                            <td>003</td>
                            <td>Bob Johnson</td>
                            <td><span class="badge badge-danger">Cancelled</span></td>
                            <td>2024-01-17</td>
                            <td><button class="btn btn-secondary btn-small">Edit</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Navigation -->
        <section class="section">
            <div class="card">
                <h2 class="card-title">Navigation</h2>
                
                <h3>Sidebar Navigation</h3>
                <div class="nav-sidebar" style="max-width: 300px;">
                    <a href="#" class="nav-item active">Dashboard</a>
                    <a href="#" class="nav-item">Products</a>
                    <a href="#" class="nav-item">Orders</a>
                    <a href="#" class="nav-item">Customers</a>
                    <a href="#" class="nav-item">Settings</a>
                </div>
                
                <h3 style="margin-top: 32px;">Breadcrumb</h3>
                <div class="breadcrumb">
                    <a href="#">Home</a>
                    <span class="breadcrumb-separator">/</span>
                    <a href="#">Products</a>
                    <span class="breadcrumb-separator">/</span>
                    <span class="active">Design System</span>
                </div>
            </div>
        </section>

        <!-- Avatars -->
        <section class="section">
            <div class="card">
                <h2 class="card-title">Avatars</h2>
                
                <div style="display: flex; gap: 24px; align-items: center;">
                    <div class="avatar avatar-small">JD</div>
                    <div class="avatar avatar-medium">JS</div>
                    <div class="avatar avatar-large">BJ</div>
                </div>
            </div>
        </section>

        <!-- Status Indicators -->
        <section class="section">
            <div class="card">
                <h2 class="card-title">Status Indicators</h2>
                
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="status status-completed">
                        <span>✓</span> Completed
                    </div>
                    <div class="status status-processing">
                        <span>⏱</span> Processing
                    </div>
                    <div class="status status-pending">
                        <span>⏳</span> Pending
                    </div>
                    <div class="status status-cancelled">
                        <span>✗</span> Cancelled
                    </div>
                </div>
            </div>
        </section>

        <!-- Pagination -->
        <section class="section">
            <div class="card">
                <h2 class="card-title">Pagination</h2>
                
                <ul class="pagination">
                    <li class="pagination-item">Previous</li>
                    <li class="pagination-item">1</li>
                    <li class="pagination-item active">2</li>
                    <li class="pagination-item">3</li>
                    <li class="pagination-item">4</li>
                    <li class="pagination-item">Next</li>
                </ul>
            </div>
        </section>

        <!-- Cards Grid -->
        <section class="section">
            <div class="card">
                <h2 class="card-title">Card Grid Layout</h2>
                
                <div class="grid grid-3">
                    <div class="card">
                        <h4>Card 1</h4>
                        <p>This is a card in a grid layout. Cards have subtle shadows and rounded corners.</p>
                    </div>
                    <div class="card">
                        <h4>Card 2</h4>
                        <p>This is another card demonstrating the grid system with consistent spacing.</p>
                    </div>
                    <div class="card">
                        <h4>Card 3</h4>
                        <p>Cards can contain any content and maintain visual consistency.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Design Philosophy -->
        <section class="section">
            <div class="card">
                <h2 class="card-title">Design Philosophy</h2>
                
                <h3>Design Principles</h3>
                <ul style="margin-left: 24px; margin-bottom: 24px; color: var(--text-secondary);">
                    <li>Clarity over decoration - every element serves a purpose</li>
                    <li>Consistent spacing creates visual rhythm and hierarchy</li>
                    <li>Subtle shadows and borders define depth without heaviness</li>
                    <li>Warm color palette creates approachability while maintaining professionalism</li>
                    <li>Typography hierarchy guides user attention naturally</li>
                    <li>Generous white space prevents visual clutter</li>
                    <li>Rounded corners soften the interface and feel modern</li>
                </ul>
                
                <h3>Visual Characteristics</h3>
                <ul style="margin-left: 24px; color: var(--text-secondary);">
                    <li>Light, airy backgrounds with subtle texture</li>
                    <li>Soft shadows for depth (not harsh or dramatic)</li>
                    <li>Rounded corners throughout (8px-12px typical)</li>
                    <li>Clean borders when needed (often subtle or transparent)</li>
                    <li>Icon-driven navigation with clear labels</li>
                    <li>Card-based content organization</li>
                    <li>Consistent color coding for status and actions</li>
                </ul>
            </div>
        </section>

        <!-- Footer -->
        <footer style="margin-top: 64px; padding: 24px 0; text-align: center; color: var(--text-muted); border-top: 1px solid var(--border-default);">
            <p>Larkon Admin Dashboard Design System v1.0.0</p>
            <p style="margin-top: 8px; font-size: 12px;">Built with HTML, CSS, JavaScript, PHP, and MySQL</p>
        </footer>
    </div>

    <script src="preview.js"></script>
</body>
</html>

