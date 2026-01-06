<?php
/**
 * Frontend Landing Page
 * Main public-facing landing page for Bespoke Cabinetry
 */

require_once __DIR__ . '/includes/frontend_layout.php';

startFrontendLayout('Home', 'home');
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Welcome to Bespoke Cabinetry</h2>
        <p class="text-muted">Crafting custom cabinetry solutions for your home</p>
    </div>
</div>

<div class="card" style="margin-bottom: var(--spacing-lg);">
    <div class="card-body">
        <h3 style="margin-bottom: var(--spacing-md); color: var(--text-primary, #313b5e);">About Us</h3>
        <p style="color: var(--text-secondary, #6b7280); line-height: 1.6; margin-bottom: var(--spacing-md);">
            Welcome to Bespoke Cabinetry, where craftsmanship meets innovation. We specialize in creating 
            custom cabinetry solutions that perfectly match your style and functional needs.
        </p>
        <p style="color: var(--text-secondary, #6b7280); line-height: 1.6;">
            Our team of skilled craftsmen works closely with you to design and build cabinetry that 
            transforms your space. From kitchen cabinets to custom storage solutions, we bring your 
            vision to life with precision and attention to detail.
        </p>
    </div>
</div>

<div class="card" style="margin-bottom: var(--spacing-lg);">
    <div class="card-body">
        <h3 style="margin-bottom: var(--spacing-md); color: var(--text-primary, #313b5e);">Our Services</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-md);">
            <div style="padding: var(--spacing-md); background: var(--bg-secondary, #f3f4f6); border-radius: var(--radius-md, 8px);">
                <h4 style="margin-bottom: var(--spacing-sm); color: var(--text-primary, #313b5e);">Custom Kitchen Cabinets</h4>
                <p style="color: var(--text-secondary, #6b7280); font-size: 14px; line-height: 1.5;">
                    Tailored kitchen cabinetry designed to maximize your space and reflect your personal style.
                </p>
            </div>
            <div style="padding: var(--spacing-md); background: var(--bg-secondary, #f3f4f6); border-radius: var(--radius-md, 8px);">
                <h4 style="margin-bottom: var(--spacing-sm); color: var(--text-primary, #313b5e);">Bathroom Vanities</h4>
                <p style="color: var(--text-secondary, #6b7280); font-size: 14px; line-height: 1.5;">
                    Elegant and functional bathroom storage solutions crafted to your specifications.
                </p>
            </div>
            <div style="padding: var(--spacing-md); background: var(--bg-secondary, #f3f4f6); border-radius: var(--radius-md, 8px);">
                <h4 style="margin-bottom: var(--spacing-sm); color: var(--text-primary, #313b5e);">Custom Storage</h4>
                <p style="color: var(--text-secondary, #6b7280); font-size: 14px; line-height: 1.5;">
                    Built-in storage solutions for any room, designed to organize and enhance your living space.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h3 style="margin-bottom: var(--spacing-md); color: var(--text-primary, #313b5e);">Get Started</h3>
        <p style="color: var(--text-secondary, #6b7280); line-height: 1.6; margin-bottom: var(--spacing-md);">
            Ready to transform your space with custom cabinetry? Contact us today to schedule a consultation 
            and discuss your project.
        </p>
        <div style="display: flex; gap: var(--spacing-md); flex-wrap: wrap;">
            <a href="mailto:info@bespokecabinetry.au" class="btn btn-primary btn-medium" style="text-decoration: none; display: inline-block;">
                Contact Us
            </a>
            <a href="/admin" class="btn btn-secondary btn-medium" style="text-decoration: none; display: inline-block;">
                Admin Login
            </a>
        </div>
    </div>
</div>

<?php
endFrontendLayout();
?>

