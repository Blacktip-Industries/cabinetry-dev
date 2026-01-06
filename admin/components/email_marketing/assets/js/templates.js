/**
 * Email Marketing Component - Templates JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Template variable helper
    const templateVariables = {
        'name': 'Customer Name',
        'company': 'Company Name',
        'email': 'Email Address',
        'coupon_code': 'Coupon Code',
        'points_balance': 'Points Balance',
        'tier_name': 'Loyalty Tier Name',
        'expiry_date': 'Points Expiry Date'
    };
    
    // Add variable insertion buttons if textarea exists
    const bodyHtmlTextarea = document.querySelector('textarea[name="body_html"]');
    if (bodyHtmlTextarea) {
        const variablePanel = document.createElement('div');
        variablePanel.className = 'email-marketing-card';
        variablePanel.innerHTML = '<h3>Template Variables</h3><p>Click to insert:</p>';
        
        Object.keys(templateVariables).forEach(key => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'email-marketing-button';
            btn.style.margin = '5px';
            btn.textContent = '{{' + key + '}}';
            btn.title = templateVariables[key];
            btn.addEventListener('click', function() {
                const textarea = bodyHtmlTextarea;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = textarea.value;
                const before = text.substring(0, start);
                const after = text.substring(end, text.length);
                textarea.value = before + '{{' + key + '}}' + after;
                textarea.focus();
                textarea.setSelectionRange(start + key.length + 4, start + key.length + 4);
            });
            variablePanel.appendChild(btn);
        });
        
        bodyHtmlTextarea.parentNode.insertBefore(variablePanel, bodyHtmlTextarea);
    }
});

