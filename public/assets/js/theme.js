// =============================================================
// assets/js/theme.js - إدارة المظهر (وضع داكن/فاتح) + إشعارات Toast
// =============================================================

(function() {
    'use strict';

    // ========== Dark Mode ==========
    const ThemeManager = {
        STORAGE_KEY: 'attendance_theme',

        init() {
            const saved = localStorage.getItem(this.STORAGE_KEY);
            if (saved === 'dark') {
                document.documentElement.classList.add('dark');
            } else if (saved === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                // Auto-detect from system
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            }

            // Listen for system changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem(this.STORAGE_KEY)) {
                    document.documentElement.classList.toggle('dark', e.matches);
                }
            });
        },

        toggle() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem(this.STORAGE_KEY, isDark ? 'dark' : 'light');

            // Save to server if logged in
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) {
                fetch(window.SITE_URL + '/api/preferences', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ key: 'dark_mode', value: isDark ? '1' : '0' })
                }).catch(() => {});
            }

            return isDark;
        },

        isDark() {
            return document.documentElement.classList.contains('dark');
        }
    };

    // ========== Toast Notifications ==========
    const Toast = {
        container: null,

        init() {
            if (this.container) return;
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        },

        show(type, title, message, duration = 5000) {
            this.init();

            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <span class="toast-icon">${icons[type] || 'ℹ️'}</span>
                <div class="toast-body">
                    <div class="toast-title">${this.escapeHtml(title)}</div>
                    ${message ? `<div class="toast-message">${this.escapeHtml(message)}</div>` : ''}
                </div>
                <button class="toast-close" onclick="this.closest('.toast').remove()">×</button>
            `;

            this.container.appendChild(toast);

            if (duration > 0) {
                setTimeout(() => {
                    toast.classList.add('toast-exit');
                    setTimeout(() => toast.remove(), 300);
                }, duration);
            }

            return toast;
        },

        success(title, message, duration) { return this.show('success', title, message, duration); },
        error(title, message, duration)   { return this.show('error', title, message, duration); },
        warning(title, message, duration) { return this.show('warning', title, message, duration); },
        info(title, message, duration)    { return this.show('info', title, message, duration); },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // ========== Session Timeout Warning ==========
    const SessionManager = {
        warningElement: null,
        timeoutMinutes: 30,
        warningMinutes: 5,
        checkInterval: null,

        init(timeoutMinutes = 30) {
            this.timeoutMinutes = timeoutMinutes;
            this.warningMinutes = Math.min(5, Math.floor(timeoutMinutes / 3));

            // Create warning element
            this.warningElement = document.createElement('div');
            this.warningElement.className = 'session-warning';
            this.warningElement.innerHTML = `
                <span>⏰</span>
                <span class="session-warning-text"></span>
                <button onclick="SessionManager.extend()" style="background:#D4A841;color:#0F172A;border:none;padding:6px 16px;border-radius:6px;cursor:pointer;font-weight:bold;margin-right:8px">
                    تمديد الجلسة
                </button>
            `;
            document.body.appendChild(this.warningElement);

            // Track activity
            this.lastActivity = Date.now();
            ['mousemove', 'keypress', 'click', 'scroll', 'touchstart'].forEach(event => {
                document.addEventListener(event, () => {
                    this.lastActivity = Date.now();
                }, { passive: true });
            });

            // Check every 30 seconds
            this.checkInterval = setInterval(() => this.check(), 30000);
        },

        check() {
            const elapsed = (Date.now() - this.lastActivity) / 1000 / 60;
            const remaining = this.timeoutMinutes - elapsed;

            if (remaining <= 0) {
                clearInterval(this.checkInterval);
                window.location.href = (window.SITE_URL || '') + '/admin/login?expired=1';
                return;
            }

            if (remaining <= this.warningMinutes) {
                const mins = Math.floor(remaining);
                const secs = Math.floor((remaining - mins) * 60);
                const text = document.documentElement.lang === 'en'
                    ? `Session expires in ${mins}:${String(secs).padStart(2, '0')}`
                    : `ستنتهي الجلسة خلال ${mins}:${String(secs).padStart(2, '0')}`;

                this.warningElement.querySelector('.session-warning-text').textContent = text;
                this.warningElement.style.display = 'flex';
            } else {
                this.warningElement.style.display = 'none';
            }
        },

        extend() {
            // Ping server to refresh session
            fetch((window.SITE_URL || '') + '/api/health', { credentials: 'same-origin' })
                .then(() => {
                    this.lastActivity = Date.now();
                    this.warningElement.style.display = 'none';
                    Toast.success('تم التمديد', 'تم تمديد جلسة العمل بنجاح');
                })
                .catch(() => {
                    Toast.error('خطأ', 'فشل في تمديد الجلسة');
                });
        }
    };

    // ========== Global Exports ==========
    window.ThemeManager = ThemeManager;
    window.Toast = Toast;
    window.SessionManager = SessionManager;

    // Auto-initialize
    ThemeManager.init();

})();
