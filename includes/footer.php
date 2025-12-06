<?php
// Folder: includes/
// File: footer.php
// Purpose: Enhanced footer with Chart.js global initialization and improved notification polling
?>
            </main>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/jquery-3.6.0.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        
        // Theme Management
        (function() {
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const currentTheme = localStorage.getItem('theme') || 'light';
            
            // Apply saved theme
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-theme');
                themeIcon.classList.remove('bi-moon-stars-fill');
                themeIcon.classList.add('bi-sun-fill');
            }
            
            // Toggle theme
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    document.body.classList.toggle('dark-theme');
                    
                    if (document.body.classList.contains('dark-theme')) {
                        themeIcon.classList.remove('bi-moon-stars-fill');
                        themeIcon.classList.add('bi-sun-fill');
                        localStorage.setItem('theme', 'dark');
                    } else {
                        themeIcon.classList.remove('bi-sun-fill');
                        themeIcon.classList.add('bi-moon-stars-fill');
                        localStorage.setItem('theme', 'light');
                    }
                });
            }
        })();
        
        // Notification System
        const NotificationManager = {
            count: 0,
            pollInterval: <?php echo NOTIFICATION_REFRESH_INTERVAL * 1000; ?>,
            
            init() {
                this.loadNotificationCount();
                this.setupEventListeners();
                this.startPolling();
            },
            
            setupEventListeners() {
                const dropdownBtn = document.getElementById('notificationDropdown');
                if (dropdownBtn) {
                    dropdownBtn.addEventListener('click', () => {
                        this.loadNotificationList();
                    });
                }
            },
            
            async loadNotificationCount() {
                try {
                    const response = await fetch(`${BASE_URL}/ajax/notification_count.php`);
                    const data = await response.json();
                    
                    const badge = document.getElementById('notificationCount');
                    if (badge) {
                        if (data.count > 0) {
                            badge.textContent = data.count > 99 ? '99+' : data.count;
                            badge.style.display = 'inline-block';
                            this.count = data.count;
                        } else {
                            badge.style.display = 'none';
                            this.count = 0;
                        }
                    }
                } catch (error) {
                    console.error('Error loading notification count:', error);
                }
            },
            
            async loadNotificationList() {
                const list = document.getElementById('notificationList');
                if (!list) return;
                
                try {
                    const response = await fetch(`${BASE_URL}/pages/notifications/get_notifications.php?limit=15`);
                    const data = await response.json();
                    
                    if (data.notifications && data.notifications.length > 0) {
                        list.innerHTML = data.notifications.map(notif => this.renderNotification(notif)).join('');
                    } else {
                        list.innerHTML = `
                            <div class="text-center p-4 text-muted">
                                <i class="bi bi-inbox fs-1"></i>
                                <p class="mt-2 mb-0">No notifications</p>
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('Error loading notification list:', error);
                    list.innerHTML = `
                        <div class="text-center p-4 text-danger">
                            <i class="bi bi-exclamation-triangle fs-1"></i>
                            <p class="mt-2 mb-0">Error loading notifications</p>
                        </div>
                    `;
                }
            },
            
            renderNotification(notif) {
                const iconClass = this.getNotificationIcon(notif.type);
                const unreadClass = notif.is_read ? '' : 'unread';
                
                return `
                    <a href="${BASE_URL}/pages/notifications/list_notifications.php?id=${notif.id}" 
                       class="notification-item d-flex text-decoration-none ${unreadClass}">
                        <div class="notification-icon ${notif.type.toLowerCase()} flex-shrink-0">
                            <i class="bi ${iconClass}"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="mb-1 fw-semibold">${this.escapeHtml(notif.title)}</h6>
                                <small class="text-muted ms-2">${notif.time_ago}</small>
                            </div>
                            <p class="mb-1 small text-muted">${this.escapeHtml(notif.message.substring(0, 80))}${notif.message.length > 80 ? '...' : ''}</p>
                            ${!notif.is_acknowledged ? `
                                <button class="btn btn-sm btn-outline-primary mt-1" 
                                        onclick="NotificationManager.acknowledgeNotification(${notif.id}, event)">
                                    <i class="bi bi-check2"></i> Acknowledge
                                </button>
                            ` : ''}
                        </div>
                    </a>
                `;
            },
            
            getNotificationIcon(type) {
                const icons = {
                    'Equipment': 'bi-pc-display-horizontal',
                    'Network': 'bi-hdd-network',
                    'Task': 'bi-check2-square',
                    'User': 'bi-person',
                    'Warranty': 'bi-shield-exclamation',
                    'System': 'bi-gear'
                };
                return icons[type] || 'bi-info-circle';
            },
            
            async acknowledgeNotification(id, event) {
                event.preventDefault();
                event.stopPropagation();
                
                try {
                    const response = await fetch(`${BASE_URL}/pages/notifications/acknowledge_notification.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${id}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.loadNotificationCount();
                        this.loadNotificationList();
                    } else {
                        console.error('Failed to acknowledge notification');
                    }
                } catch (error) {
                    console.error('Error acknowledging notification:', error);
                }
            },
            
            startPolling() {
                setInterval(() => {
                    this.loadNotificationCount();
                }, this.pollInterval);
            },
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        };
        
        // Initialize notification system when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            NotificationManager.init();
        });
        
        // Helper function for AJAX forms
        function showLoading(buttonElement) {
            buttonElement.disabled = true;
            const originalText = buttonElement.innerHTML;
            buttonElement.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
            return originalText;
        }
        
        function hideLoading(buttonElement, originalText) {
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalText;
        }
        
        // Confirm delete helper
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }
        
        // Auto-dismiss alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>