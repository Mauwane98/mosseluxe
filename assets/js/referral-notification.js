/**
 * Referral Notification System
 * Shows a notification when user visits via referral link
 */

document.addEventListener('DOMContentLoaded', function() {
    // Check if there's a referral code in the URL
    const urlParams = new URLSearchParams(window.location.search);
    const refCode = urlParams.get('ref');
    
    if (refCode && window.Modal) {
        // Show welcome notification
        setTimeout(() => {
            Modal.toast(
                'Sign up and make your first purchase to get 10% off!',
                '🎁 You\'ve been referred!',
                'success',
                5000
            );
        }, 1000);
        
        // Clean URL (remove ref parameter) without page reload
        if (window.history && window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.delete('ref');
            window.history.replaceState({}, document.title, url.toString());
        }
    }
});
