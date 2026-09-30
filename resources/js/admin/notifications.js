/**
 * Admin Notification System
 * Handles fetching, displaying, live audio alerts, interactive modals, and navbar dropdown.
 */

$(function () {
  const notificationDropdown = $('.dropdown-notifications');
  if (notificationDropdown.length === 0) return;

  const notificationList = $('.dropdown-notifications-list .list-group');
  const badge = $('.badge-notifications');
  const headerBadge = $('.dropdown-header .badge');
  const apiBase = (typeof baseUrl !== 'undefined' ? baseUrl : '/') + 'admin/system-notifications';

  // Helper to detect Arabic / RTL direction
  function isRtl() {
    return (
      document.documentElement.dir === 'rtl' ||
      document.dir === 'rtl' ||
      $('html').attr('dir') === 'rtl' ||
      (document.documentElement.lang && document.documentElement.lang.startsWith('ar'))
    );
  }

  // --------------------------------------------------------------------------
  // 1. Audio Chime System (Web Audio API)
  // --------------------------------------------------------------------------
  let audioCtx = null;

  function initAudioContext() {
    if (!audioCtx) {
      const AudioContextClass = window.AudioContext || window.webkitAudioContext;
      if (AudioContextClass) {
        audioCtx = new AudioContextClass();
      }
    }
    if (audioCtx && audioCtx.state === 'suspended') {
      audioCtx.resume();
    }
  }

  // Unlock audio context on user's first interaction
  $(document).one('click keydown', function () {
    initAudioContext();
  });

  function playNotificationChime() {
    if (localStorage.getItem('admin_notifications_muted') === 'true') {
      return;
    }

    try {
      initAudioContext();
      if (!audioCtx) return;

      const now = audioCtx.currentTime;

      // Note 1: First Harmonic Ping (D5 -> A5)
      const osc1 = audioCtx.createOscillator();
      const gain1 = audioCtx.createGain();
      osc1.type = 'sine';
      osc1.frequency.setValueAtTime(587.33, now);
      osc1.frequency.exponentialRampToValueAtTime(880, now + 0.08);
      gain1.gain.setValueAtTime(0.35, now);
      gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.45);
      osc1.connect(gain1);
      gain1.connect(audioCtx.destination);
      osc1.start(now);
      osc1.stop(now + 0.45);

      // Note 2: Crisp Bright Bell Ding (C6 -> E6)
      const osc2 = audioCtx.createOscillator();
      const gain2 = audioCtx.createGain();
      osc2.type = 'triangle';
      osc2.frequency.setValueAtTime(1046.5, now + 0.1);
      osc2.frequency.exponentialRampToValueAtTime(1318.51, now + 0.2);
      gain2.gain.setValueAtTime(0.001, now);
      gain2.gain.setValueAtTime(0.45, now + 0.1);
      gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.65);
      osc2.connect(gain2);
      gain2.connect(audioCtx.destination);
      osc2.start(now + 0.1);
      osc2.stop(now + 0.65);
    } catch (e) {
      console.warn('Audio chime playback error:', e);
    }
  }

  // Sync mute button UI from localStorage
  function syncMuteButtonUI() {
    const isMuted = localStorage.getItem('admin_notifications_muted') === 'true';
    const icon = $('.dropdown-notifications-sound-toggle .sound-icon');
    if (isMuted) {
      icon.removeClass('ti-volume').addClass('ti-volume-off text-muted');
    } else {
      icon.removeClass('ti-volume-off text-muted').addClass('ti-volume text-heading');
    }
  }
  syncMuteButtonUI();

  // Mute / Unmute toggle click
  $(document).on('click', '.dropdown-notifications-sound-toggle', function (e) {
    e.stopPropagation();
    const isMuted = localStorage.getItem('admin_notifications_muted') === 'true';
    const newMuted = !isMuted;
    localStorage.setItem('admin_notifications_muted', newMuted);
    syncMuteButtonUI();

    if (!newMuted) {
      playNotificationChime();
    }
  });

  // --------------------------------------------------------------------------
  // 2. Navbar Template & Render
  // --------------------------------------------------------------------------
  function notificationTemplate(notification) {
    const isReadClass = notification.is_read ? 'marked-as-read' : '';
    const icon = notification.icon || (notification.is_read ? 'ti-mail-opened' : 'ti-mail');
    const bgClass = notification.is_read ? 'bg-label-secondary' : 'bg-label-primary';
    const actionUrlAttr = notification.action_url ? `data-url="${notification.action_url}" style="cursor: pointer;"` : '';

    return `
      <li class="list-group-item list-group-item-action dropdown-notifications-item ${isReadClass}" data-id="${notification.id}" ${actionUrlAttr}>
        <div class="d-flex">
          <div class="flex-shrink-0 me-3">
            <div class="avatar">
              <span class="avatar-initial rounded-circle ${bgClass}"><i class="ti ${icon}"></i></span>
            </div>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-1 small fw-bold">${notification.title}</h6>
            <small class="mb-1 d-block text-body" style="white-space: pre-line; line-height: 1.4;">${notification.message}</small>
            <small class="text-muted"><i class="ti ti-clock me-1"></i>${notification.created_at}</small>
          </div>
          <div class="flex-shrink-0 dropdown-notifications-actions">
            ${
              !notification.is_read
                ? `<a href="javascript:void(0)" class="dropdown-notifications-read" title="تحديد كمقروء"><span class="badge badge-dot"></span></a>`
                : ''
            }
          </div>
        </div>
      </li>
    `;
  }

  // Load notifications for dropdown
  function loadNotifications() {
    $.ajax({
      url: apiBase,
      method: 'GET',
      success: function (response) {
        if (response.success) {
          renderNotifications(response.data);
          updateUnreadCount();
        }
      },
      error: function (xhr) {
        console.error('Failed to load notifications', xhr);
      }
    });
  }

  // Update unread count
  function updateUnreadCount() {
    $.ajax({
      url: apiBase + '/unread-count',
      method: 'GET',
      success: function (response) {
        if (response.success) {
          const count = response.count;
          if (count > 0) {
            badge.text(count).show();
          } else {
            badge.hide();
          }
          headerBadge.text(`${count} New`);
        }
      }
    });
  }

  // Render list
  function renderNotifications(notifications) {
    notificationList.empty();

    if (!notifications || notifications.length === 0) {
      notificationList.append('<li class="list-group-item text-center p-4 text-muted">لا توجد إشعارات حالياً</li>');
      return;
    }

    notifications.forEach(notification => {
      notificationList.append(notificationTemplate(notification));
    });
  }

  // --------------------------------------------------------------------------
  // 3. In-App Real-Time Toast Alerts (Bottom-Left for AR, Bottom-Right for EN)
  // --------------------------------------------------------------------------
  function getToastContainer() {
    let container = document.getElementById('adminToastContainer');
    const rtl = isRtl();

    if (!container) {
      container = document.createElement('div');
      container.id = 'adminToastContainer';
      container.className = 'admin-toast-container';
      document.body.appendChild(container);
    }

    if (rtl) {
      container.style.setProperty('left', '24px', 'important');
      container.style.setProperty('right', 'auto', 'important');
    } else {
      container.style.setProperty('right', '24px', 'important');
      container.style.setProperty('left', 'auto', 'important');
    }
    container.style.setProperty('bottom', '24px', 'important');

    return container;
  }

  function showNotificationToast(item) {
    const rtl = isRtl();
    const container = getToastContainer();

    const toastId = 'toast-notif-' + item.id + '-' + Math.random().toString(36).substring(2, 7);
    const actionText = rtl ? 'عرض التفاصيل' : 'View Details';
    const markReadText = rtl ? 'تم' : 'Done';
    const arrowIcon = rtl ? 'ti-arrow-left' : 'ti-arrow-right';

    const toastHtml = `
      <div class="app-notification-toast" id="${toastId}" data-id="${item.id}" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="p-3">
          <div class="d-flex align-items-start gap-2 mb-2">
            <div class="avatar avatar-sm flex-shrink-0">
              <span class="avatar-initial rounded-circle bg-label-primary shadow-sm">
                <i class="ti ${item.icon || 'ti-bell'} fs-5"></i>
              </span>
            </div>
            <div class="flex-grow-1 overflow-hidden">
              <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-heading fs-6 text-truncate" title="${item.title}">${item.title}</h6>
                <button type="button" class="btn-close toast-close-btn p-1 ms-2" aria-label="Close"></button>
              </div>
              <small class="text-muted"><i class="ti ti-clock me-1"></i>${item.created_at}</small>
            </div>
          </div>
          <p class="mb-2 text-body small" style="white-space: pre-line; line-height: 1.45; max-height: 90px; overflow-y: auto;">${item.message}</p>
          <div class="d-flex justify-content-end align-items-center gap-2 pt-2 border-top">
            ${
              item.action_url
                ? `<a href="${item.action_url}" class="btn btn-xs btn-primary btn-toast-action" data-id="${item.id}">
                     <i class="ti ${arrowIcon} me-1"></i> ${actionText}
                   </a>`
                : ''
            }
            <button type="button" class="btn btn-xs btn-label-secondary btn-toast-mark-read" data-id="${item.id}">
              <i class="ti ti-check me-1"></i> ${markReadText}
            </button>
          </div>
        </div>
        <div class="toast-progress"></div>
      </div>
    `;

    const $toast = $(toastHtml);
    $(container).append($toast);

    // Smooth entrance animation
    requestAnimationFrame(() => {
      $toast.addClass('toast-show');
    });

    let remainingTime = 6000;
    let startTime = Date.now();
    let dismissTimer = null;
    let isPaused = false;

    function startTimer(duration) {
      startTime = Date.now();
      remainingTime = duration;
      dismissTimer = setTimeout(() => {
        dismissToast($toast);
      }, duration);
    }

    function pauseTimer() {
      if (!isPaused) {
        isPaused = true;
        clearTimeout(dismissTimer);
        const elapsed = Date.now() - startTime;
        remainingTime = Math.max(800, remainingTime - elapsed);
        $toast.find('.toast-progress').css('animation-play-state', 'paused');
      }
    }

    function resumeTimer() {
      if (isPaused) {
        isPaused = false;
        $toast.find('.toast-progress').css('animation-play-state', 'running');
        startTimer(remainingTime);
      }
    }

    // 6-second timer
    startTimer(6000);

    // Pause on hover, resume on leave
    $toast.on('mouseenter', pauseTimer);
    $toast.on('mouseleave', resumeTimer);

    // Close button
    $toast.find('.toast-close-btn').on('click', function (e) {
      e.stopPropagation();
      dismissToast($toast);
    });
  }

  function dismissToast($toast) {
    if (!$toast || !$toast.length || $toast.hasClass('toast-hide')) return;
    $toast.removeClass('toast-show').addClass('toast-hide');
    setTimeout(() => {
      $toast.remove();
    }, 350);
  }

  function checkLatestUnshown() {
    $.ajax({
      url: apiBase + '/latest-unshown',
      method: 'GET',
      success: function (res) {
        if (res.success && res.has_new && res.data && res.data.length > 0) {
          // 1. Play Chime
          playNotificationChime();

          // 2. Animate Navbar Bell
          $('.dropdown-notifications .ti-bell').addClass('animate__animated animate__tada');
          setTimeout(() => {
            $('.dropdown-notifications .ti-bell').removeClass('animate__animated animate__tada');
          }, 1500);

          // 3. Show Toaster for each incoming notification
          res.data.forEach((item, index) => {
            setTimeout(() => {
              showNotificationToast(item);
            }, index * 250);

            // Also prepend directly to navbar dropdown list
            if (notificationList.find('.text-muted').length > 0) {
              notificationList.empty();
            }
            notificationList.prepend(notificationTemplate(item));
          });

          // 4. Update unread count
          updateUnreadCount();
        }
      }
    });
  }

  // Handle Action Button in Toast (mark as read and navigate)
  $(document).on('click', '.btn-toast-action, .btn-modal-action', function () {
    const id = $(this).data('id');
    if (id) {
      $.post(`${apiBase}/${id}/read`, {
        _token: $('meta[name="csrf-token"]').attr('content')
      });
    }
  });

  // Handle Mark Read Button in Toast
  $(document).on('click', '.btn-toast-mark-read, .btn-modal-mark-read', function (e) {
    e.stopPropagation();
    const btn = $(this);
    const id = btn.data('id');
    const toast = btn.closest('.app-notification-toast');

    if (id) {
      $.ajax({
        url: `${apiBase}/${id}/read`,
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (res) {
          if (res.success) {
            // Update navbar item as read
            const navItem = notificationList.find(`[data-id="${id}"]`);
            if (navItem.length) {
              navItem.addClass('marked-as-read');
              navItem.find('.dropdown-notifications-read').remove();
            }
            updateUnreadCount();
          }
        }
      });
    }

    if (toast.length) {
      dismissToast(toast);
    }
  });

  // --------------------------------------------------------------------------
  // 4. Click Notification in Dropdown (Navigate + Mark Read)
  // --------------------------------------------------------------------------
  $(document).on('click', '.dropdown-notifications-item', function (e) {
    // Don't trigger if clicked on the mark-read dot
    if ($(e.target).closest('.dropdown-notifications-read').length) return;

    const item = $(this);
    const id = item.data('id');
    const url = item.data('url');

    if (id) {
      $.post(`${apiBase}/${id}/read`, {
        _token: $('meta[name="csrf-token"]').attr('content')
      });
    }

    if (url) {
      window.location.href = url;
    }
  });

  // Mark single notification as read from dot
  $(document).on('click', '.dropdown-notifications-read', function (e) {
    e.stopPropagation();
    const item = $(this).closest('.dropdown-notifications-item');
    const id = item.data('id');

    $.ajax({
      url: `${apiBase}/${id}/read`,
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success) {
          item.addClass('marked-as-read');
          item.find('.dropdown-notifications-read').remove();
          item.find('.avatar-initial').removeClass('bg-label-primary').addClass('bg-label-secondary');
          updateUnreadCount();
        }
      }
    });
  });

  // Mark all as read
  $('.dropdown-notifications-all').on('click', function (e) {
    e.stopPropagation();

    $.ajax({
      url: `${apiBase}/mark-all-read`,
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success) {
          loadNotifications();
        }
      }
    });
  });

  // --------------------------------------------------------------------------
  // 5. Initial Execution & Polling Timers
  // --------------------------------------------------------------------------
  loadNotifications();
  checkLatestUnshown();

  // Poll for new unshown notifications every 15 seconds (lightweight & responsive)
  setInterval(checkLatestUnshown, 15000);

  // Refresh entire dropdown list every 60 seconds
  setInterval(loadNotifications, 60000);
});
