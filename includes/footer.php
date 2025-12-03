<?php
// Folder: includes/
// File: footer.php
// Purpose: Common footer with scripts and notification polling
?>
            </main>
        </div>
    </div>
    
    <script src="/assets/js/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        if (currentTheme === 'dark') {
            document.body.classList.add('dark-theme');
            themeIcon.classList.remove('bi-moon-stars-fill');
            themeIcon.classList.add('bi-sun-fill');
        }
        
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
        
        // Notification polling
        function loadNotifications() {
            fetch('/ajax/notification_count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('notificationCount');
                    if (data.count > 0) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error loading notifications:', error));
        }
        
        function loadNotificationList() {
            fetch('/pages/notifications/get_notifications.php?limit=15')
                .then(response => response.json())
                .then(data => {
                    const list = document.getElementById('notificationList');
                    
                    if (data.notifications && data.notifications.length > 0) {
                        list.innerHTML = data.notifications.map(notif => `
                            <a href="/pages/notifications/list_notifications.php?id=${notif.id}" 
                               class="list-group-item list-group-item-action ${notif.is_read ? '' : 'fw-bold'}">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">${notif.title}</h6>
                                    <small>${notif.time_ago}</small>
                                </div>
                                <p class="mb-1 small">${notif.message}</p>
                                ${notif.is_acknowledged ? '' : `
                                    <button class="btn btn-sm btn-outline-primary mt-1" 
                                            onclick="acknowledgeNotification(${notif.id}, event)">
                                        Acknowledge
                                    </button>
                                `}
                            </a>
                        `).join('');
                    } else {
                        list.innerHTML = '<div class="text-center p-3 text-muted">No notifications</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading notification list:', error);
                    document.getElementById('notificationList').innerHTML = 
                        '<div class="text-center p-3 text-danger">Error loading notifications</div>';
                });
        }
        
        function acknowledgeNotification(id, event) {
            event.preventDefault();
            event.stopPropagation();
            
            fetch('/pages/notifications/acknowledge_notification.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                    loadNotificationList();
                }
            })
            .catch(error => console.error('Error acknowledging notification:', error));
        }
        
        // Load notifications on page load
        loadNotifications();
        
        // Reload notifications when dropdown is opened
        document.getElementById('notificationDropdown').addEventListener('click', function() {
            loadNotificationList();
        });
        
        // Poll for new notifications every 30 seconds
        setInterval(loadNotifications, <?php echo NOTIFICATION_REFRESH_INTERVAL * 1000; ?>);
    </script>
</body>
</html>