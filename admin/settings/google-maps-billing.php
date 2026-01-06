<?php
/**
 * Google Maps Billing Monitor
 * Monitor Google Maps API usage and billing information
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Google Maps Billing Monitor', true, 'settings_google_maps_billing');

$conn = getDBConnection();
$apiKey = getGoogleMapsApiKey();
?>

<link rel="stylesheet" href="../assets/css/customers.css">

<div class="page-header">
    <div class="page-header__left">
        <h2>Google Maps Billing Monitor</h2>
    </div>
    <div class="page-header__right">
        <a href="google-maps.php" class="btn btn-secondary">Back to Settings</a>
    </div>
</div>

<?php if (!$apiKey): ?>
    <div class="alert alert-error">
        Google Maps API key is not configured. Please configure it in <a href="google-maps.php">Google Maps Settings</a>.
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Quick Access to Billing Dashboard</h3>
    </div>
    <div class="card-body">
        <p>Access your Google Cloud billing information directly:</p>
        <div class="billing-links" style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
            <a href="https://console.cloud.google.com/billing" target="_blank" class="btn btn-primary">
                Billing Dashboard
            </a>
            <a href="https://console.cloud.google.com/billing/budgets" target="_blank" class="btn btn-secondary">
                Budgets & Alerts
            </a>
            <a href="https://console.cloud.google.com/billing/reports" target="_blank" class="btn btn-secondary">
                Cost Reports
            </a>
            <a href="https://console.cloud.google.com/apis/dashboard" target="_blank" class="btn btn-secondary">
                API Dashboard
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>API Usage & Quotas</h3>
    </div>
    <div class="card-body">
        <p>Monitor your Google Maps Platform API usage and quotas:</p>
        <div class="api-links" style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
            <a href="https://console.cloud.google.com/apis/api/maps-javascript-api/quotas" target="_blank" class="btn btn-info">
                Maps JavaScript API Quotas
            </a>
            <a href="https://console.cloud.google.com/apis/api/geocoding-backend.googleapis.com/quotas" target="_blank" class="btn btn-info">
                Geocoding API Quotas
            </a>
            <a href="https://console.cloud.google.com/apis/api/routes.googleapis.com/quotas" target="_blank" class="btn btn-info">
                Routes API Quotas
            </a>
            <a href="https://console.cloud.google.com/apis/api/places-backend.googleapis.com/quotas" target="_blank" class="btn btn-info">
                Places API Quotas
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Monthly Free Credits</h3>
    </div>
    <div class="card-body">
        <p>Google Maps Platform provides <strong>$200 in free credits per month</strong> for the following services:</p>
        <ul>
            <li><strong>Maps JavaScript API:</strong> Free up to $200/month</li>
            <li><strong>Geocoding API:</strong> Free up to $200/month</li>
            <li><strong>Routes API:</strong> Free up to $200/month</li>
            <li><strong>Places API:</strong> Free up to $200/month</li>
        </ul>
        <p style="margin-top: 1rem;">
            <strong>Note:</strong> After the $200 free credit is exhausted, you'll be charged based on the 
            <a href="https://mapsplatform.google.com/pricing/" target="_blank">Google Maps Platform pricing</a>.
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Set Up Billing Alerts</h3>
    </div>
    <div class="card-body">
        <p>To avoid unexpected charges, set up billing alerts in Google Cloud Console:</p>
        <ol>
            <li>
                Go to <a href="https://console.cloud.google.com/billing/budgets" target="_blank">Budgets & Alerts</a>
            </li>
            <li>Click <strong>"Create Budget"</strong></li>
            <li>Set your budget amount (e.g., $50, $100, or $200)</li>
            <li>Configure alert thresholds (e.g., 50%, 90%, 100%)</li>
            <li>Add email recipients to receive alerts</li>
            <li>Save the budget</li>
        </ol>
        <p style="margin-top: 1rem;">
            <strong>Recommended:</strong> Set a budget alert at $150 to get notified before exhausting your free credits.
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Cost Optimization Tips</h3>
    </div>
    <div class="card-body">
        <ul>
            <li>
                <strong>Cache geocoding results:</strong> Store latitude/longitude in your database to avoid repeated geocoding requests for the same address.
            </li>
            <li>
                <strong>Use static maps when possible:</strong> For simple map displays, consider using Static Maps API which is more cost-effective.
            </li>
            <li>
                <strong>Limit map interactions:</strong> Reduce unnecessary map interactions and API calls in your application.
            </li>
            <li>
                <strong>Monitor usage regularly:</strong> Check your API usage dashboard weekly to identify any unusual spikes.
            </li>
            <li>
                <strong>Implement request throttling:</strong> Add rate limiting to prevent accidental excessive API calls.
            </li>
            <li>
                <strong>Use session storage:</strong> Cache autocomplete results in the browser to reduce Places API calls.
            </li>
        </ul>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Current Month Usage Summary</h3>
    </div>
    <div class="card-body">
        <p>To view your current month's usage and costs:</p>
        <ol>
            <li>Go to <a href="https://console.cloud.google.com/billing" target="_blank">Billing Dashboard</a></li>
            <li>Select your billing account</li>
            <li>Click on <strong>"Reports"</strong> or <strong>"Cost breakdown"</strong></li>
            <li>Filter by date range (current month)</li>
            <li>Filter by service to see Maps Platform usage</li>
        </ol>
        <p style="margin-top: 1rem;">
            <strong>Tip:</strong> You can also view real-time usage in the 
            <a href="https://console.cloud.google.com/apis/dashboard" target="_blank">API Dashboard</a>.
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>API Pricing Reference</h3>
    </div>
    <div class="card-body">
        <p>Current pricing (as of 2024, subject to change):</p>
        <table class="table" style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Pricing</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Maps JavaScript API</td>
                    <td>$7 per 1,000 requests</td>
                </tr>
                <tr>
                    <td>Geocoding API</td>
                    <td>$5 per 1,000 requests</td>
                </tr>
                <tr>
                    <td>Routes API (per request)</td>
                    <td>$5 per 1,000 requests</td>
                </tr>
                <tr>
                    <td>Places API (Autocomplete)</td>
                    <td>$2.83 per 1,000 requests</td>
                </tr>
                <tr>
                    <td>Places API (Place Details)</td>
                    <td>$17 per 1,000 requests</td>
                </tr>
            </tbody>
        </table>
        <p style="margin-top: 1rem;">
            <strong>Note:</strong> Pricing may vary. Always check the 
            <a href="https://mapsplatform.google.com/pricing/" target="_blank">official pricing page</a> for the most current rates.
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Need Help?</h3>
    </div>
    <div class="card-body">
        <p>If you have questions about billing or need assistance:</p>
        <ul>
            <li>
                <a href="https://support.google.com/cloud/contact/cloud_platform_billing" target="_blank">
                    Contact Google Cloud Billing Support
                </a>
            </li>
            <li>
                <a href="https://developers.google.com/maps/support" target="_blank">
                    Google Maps Platform Support
                </a>
            </li>
            <li>
                <a href="https://console.cloud.google.com/support" target="_blank">
                    Google Cloud Support Center
                </a>
            </li>
        </ul>
    </div>
</div>

<?php
endLayout();
?>

