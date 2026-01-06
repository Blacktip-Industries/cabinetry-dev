/**
 * Email Marketing Component - Campaigns JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Campaign form validation
    const campaignForm = document.querySelector('form');
    if (campaignForm) {
        campaignForm.addEventListener('submit', function(e) {
            const campaignName = document.querySelector('input[name="campaign_name"]');
            const subject = document.querySelector('input[name="subject"]');
            
            if (!campaignName || !campaignName.value.trim()) {
                e.preventDefault();
                alert('Campaign name is required');
                return false;
            }
            
            if (!subject || !subject.value.trim()) {
                e.preventDefault();
                alert('Subject is required');
                return false;
            }
        });
    }
});

