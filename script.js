/**
 *  EduLib Global JavaScript
 */

// 1. Theme Toggle Management
const themeToggle = document.getElementById('themeToggle');
const html = document.documentElement;

const currentTheme = localStorage.getItem('theme') || 'light';
setTheme(currentTheme);

themeToggle?.addEventListener('click', () => {
    const newTheme = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    setTheme(newTheme);
});

function setTheme(theme) {
    html.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    if (themeToggle) themeToggle.textContent = theme === 'light' ? '🌙' : '☀️';
}

// 2. Mobile Navigation Toggle
const hamburger = document.getElementById('hamburger');
const mobileNav = document.getElementById('mobileNav');

hamburger?.addEventListener('click', () => {
    mobileNav.classList.toggle('active');
    hamburger.classList.toggle('active');
});

// Close mobile nav when clicking outside
document.addEventListener('click', (e) => {
    if (mobileNav?.classList.contains('active') && !hamburger.contains(e.target) && !mobileNav.contains(e.target)) {
        mobileNav.classList.remove('active');
    }
});

// 3. Scroll Animations
const animateElements = document.querySelectorAll('.animate-in');
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.1 });

animateElements.forEach(el => observer.observe(el));

// Number Counters
const counters = document.querySelectorAll('[data-counter]');
counters.forEach(counter => {
    const target = +counter.getAttribute('data-counter');
    const duration = 1500;
    const increment = target / (duration / 16);
    let current = 0;

    const updateCounter = () => {
        current += increment;
        if (current < target) {
            counter.innerText = Math.ceil(current);
            requestAnimationFrame(updateCounter);
        } else {
            counter.innerText = target;
        }
    };
    
    // Only animate if target > 0
    if (target > 0) {
        observer.observe(counter.closest('.stat-card') || counter);
        // Start counter on intersection
        const cObserver = new IntersectionObserver(e => {
            if(e[0].isIntersecting) {
                updateCounter();
                cObserver.disconnect();
            }
        });
        cObserver.observe(counter);
    }
});

// 4. Modal System
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modals on generic overlay click or Esc key
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        closeModal(e.target.id);
    }
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => closeModal(m.id));
    }
});

// 5. Toast Notifications
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // Icon selection
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    const icon = icons[type] || icons.info;

    toast.innerHTML = `
        <span class="toast-icon">${icon}</span>
        <span class="toast-message">${message}</span>
    `;

    container.appendChild(toast);

    // Auto remove after 4 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// 6. Generic Form Validation Helper
function clearErrors(formElements) {
    formElements.forEach(el => {
        const errNode = document.getElementById(`err${el.id}`);
        if(errNode) errNode.textContent = '';
        el.style.borderColor = 'var(--border)';
    });
}
function showError(inputEl, message) {
    const errNode = document.getElementById(`err${inputEl.id}`);
    if(errNode) errNode.textContent = message;
    inputEl.style.borderColor = 'var(--danger)';
}
