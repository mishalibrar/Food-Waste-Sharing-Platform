document.addEventListener('DOMContentLoaded', () => {
    // Mobile Menu Toggle
    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.getElementById('navLinks');
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // Live Countdown Timer
    const countdowns = document.querySelectorAll('.countdown');
    if (countdowns.length > 0) {
        setInterval(() => {
            countdowns.forEach(el => {
                const expiry = new Date(el.dataset.expiry).getTime();
                const now = new Date().getTime();
                const diff = expiry - now;

                if (diff <= 0) {
                    el.closest('.card, .glass')?.style && (el.closest('.card, .glass').style.display = 'none');
                    return;
                }

                const hours = Math.floor(diff / (1000 * 60 * 60));
                const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const secs = Math.floor((diff % (1000 * 60)) / 1000);

                el.innerText = `${hours}h ${mins}m ${secs}s`;
                
                const urgentBadge = el.parentElement?.querySelector('.urgent-badge');
                const criticalBadge = el.parentElement?.querySelector('.critical-badge');

                // Critical: less than 30 minutes
                if (hours === 0 && mins < 30) {
                    if (urgentBadge) urgentBadge.style.display = 'none';
                    // Create critical badge if it doesn't exist
                    if (!criticalBadge) {
                        const badge = document.createElement('span');
                        badge.className = 'critical-badge';
                        badge.innerText = '⚠ Critical';
                        el.parentElement.insertBefore(badge, el);
                    } else {
                        criticalBadge.style.display = 'inline-block';
                    }
                }
                // Urgent: less than 2 hours
                else if (hours < 2) {
                    if (urgentBadge) urgentBadge.style.display = 'inline-block';
                    if (criticalBadge) criticalBadge.style.display = 'none';
                }
            });
        }, 1000);
    }

    // Close user dropdown on outside click
    document.addEventListener('click', (e) => {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown && !dropdown.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });
});

// Toast Notification System
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) {
        const div = document.createElement('div');
        div.id = 'toastContainer';
        div.className = 'toast-container';
        document.body.appendChild(div);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.getElementById('toastContainer').appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Modal System
function showModal(title, message, isConfirm = false) {
    return new Promise((resolve) => {
        const overlay = document.getElementById('modalOverlay');
        const titleEl = document.getElementById('modalTitle');
        const messageEl = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('modalConfirm');
        const cancelBtn = document.getElementById('modalCancel');

        titleEl.innerText = title;
        messageEl.innerText = message;
        cancelBtn.style.display = isConfirm ? 'block' : 'none';
        confirmBtn.innerText = isConfirm ? 'Confirm' : 'OK';

        overlay.classList.add('active');

        const close = (result) => {
            overlay.classList.remove('active');
            confirmBtn.onclick = null;
            cancelBtn.onclick = null;
            resolve(result);
        };

        confirmBtn.onclick = () => close(true);
        cancelBtn.onclick = () => close(false);
        overlay.onclick = (e) => { if (e.target === overlay) close(false); };
    });
}

// AJAX Utility Functions
async function apiCall(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data)
        });
        return await response.json();
    } catch (e) {
        return { success: false, error: 'Network error occurred.' };
    }
}

// Password Toggle Utility
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
