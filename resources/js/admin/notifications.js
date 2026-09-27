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
  const inAppModal = $('#inAppNotificationModal');
  const inAppModalList = $('#inAppModalNotificationsList');

  // Base URL for API
  const apiBase = (typeof baseUrl !== 'undefined' ? baseUrl : '/') + 'admin/system-notifications';

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
  // 3. In-App Real-Time Modal Alert & Unshown Polling
  // --------------------------------------------------------------------------
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

          // 3. Render Modal Content
          let modalHtml = '';
          res.data.forEach(item => {
            modalHtml += `
              <div class="card border mb-3 shadow-none bg-label-secondary" data-id="${item.id}">
                <div class="card-body p-3">
                  <div class="d-flex align-items-start">
                    <div class="avatar avatar-sm me-3 flex-shrink-0">
                      <span class="avatar-initial rounded-circle bg-white shadow-sm">
                        <i class="ti ${item.icon || 'ti-bell'} fs-5"></i>
                      </span>
                    </div>
                    <div class="flex-grow-1">
                      <h6 class="fw-bold mb-1 text-heading">${item.title}</h6>
                      <p class="mb-2 text-body small" style="white-space: pre-line; line-height: 1.5;">${item.message}</p>
                      <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                        <small class="text-muted"><i class="ti ti-clock me-1"></i>${item.created_at}</small>
                        <div>
                          ${
                            item.action_url
                              ? `<a href="${item.action_url}" class="btn btn-xs btn-primary btn-modal-action me-1" data-id="${item.id}">
                                   <i class="ti ti-arrow-left me-1"></i> عرض التفاصيل
                                 </a>`
                              : ''
                          }
                          <button type="button" class="btn btn-xs btn-label-secondary btn-modal-mark-read" data-id="${item.id}">
                            <i class="ti ti-check me-1"></i> تم
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            `;

            // Also prepend directly to navbar dropdown list
            if (notificationList.find('.text-muted').length > 0) {
              notificationList.empty();
            }
            notificationList.prepend(notificationTemplate(item));
          });

          inAppModalList.html(modalHtml);

          // 4. Show Modal if not already open
          if (inAppModal.length > 0 && !inAppModal.hasClass('show')) {
            inAppModal.modal('show');
          }

          // 5. Update unread count
          updateUnreadCount();
        }
      }
    });
  }

  // Handle Action Button in Modal (mark read and let browser navigate)
  $(document).on('click', '.btn-modal-action', function () {
    const id = $(this).data('id');
    if (id) {
      $.post(`${apiBase}/${id}/read`, {
        _token: $('meta[name="csrf-token"]').attr('content')
      });
    }
  });

  // Handle Mark Read Button in Modal
  $(document).on('click', '.btn-modal-mark-read', function () {
    const btn = $(this);
    const id = btn.data('id');
    const card = btn.closest('.card');

    $.ajax({
      url: `${apiBase}/${id}/read`,
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (res) {
        if (res.success) {
          card.fadeOut(300, function () {
            $(this).remove();
            if (inAppModalList.children().length === 0) {
              inAppModal.modal('hide');
            }
          });
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
