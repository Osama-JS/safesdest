/**
 * WhatsApp Draggable Floating Chat Widget
 * Smooth Draggable FAB + Draggable Chat Window + Live Notifications
 */

'use strict';

$(function () {
  const container = $('#whatsapp-floating-container');
  if (container.length === 0) return;

  const fab = $('#whatsapp-floating-fab');
  const fabPulse = $('#whatsapp-fab-pulse');
  const fabBadge = $('#whatsapp-fab-badge');
  const fabToast = $('#whatsapp-fab-toast');
  const toastTitle = $('#whatsapp-toast-title');
  const toastBody = $('#whatsapp-toast-body');

  const win = $('#whatsapp-floating-window');
  const winHeader = $('#whatsapp-window-header');
  const winClose = $('#whatsapp-window-close');
  const winCompact = $('#whatsapp-window-compact');
  const winMaximize = $('#whatsapp-window-maximize');
  const winRefresh = $('#whatsapp-window-refresh');
  const winUnreadBadge = $('#whatsapp-window-unread-badge');

  const searchInput = $('#whatsapp-widget-search');
  const searchClear = $('#whatsapp-widget-search-clear');
  const convList = $('#whatsapp-widget-conversations-list');
  const convLoading = $('#whatsapp-conversations-loading');

  const chatPane = $('#whatsapp-widget-chat-pane');
  const emptyState = $('#whatsapp-chat-empty-state');
  const activeChatContainer = $('#whatsapp-chat-active-container');
  const activeAvatar = $('#whatsapp-active-avatar');
  const activeName = $('#whatsapp-active-name');
  const activePhone = $('#whatsapp-active-phone');
  const activeBadge = $('#whatsapp-active-badge');
  const windowStatusBadge = $('#whatsapp-window-status-badge');
  const messagesStream = $('#whatsapp-messages-stream');
  const messagesLoading = $('#whatsapp-messages-loading');
  const replyForm = $('#whatsapp-widget-reply-form');
  const replyInput = $('#whatsapp-widget-message-input');
  const sendBtn = $('#whatsapp-widget-send-btn');
  const attachBtn = $('#whatsapp-widget-attach-btn');
  const fileInput = $('#whatsapp-widget-file-input');
  const templatesDropdown = $('#whatsapp-templates-dropdown');
  const mobileBackBtn = $('#whatsapp-mobile-back');

  const baseUrl = (window.baseUrl || '/').replace(/\/?$/, '/');

  let activeConversationId = null;
  let activeLastMessageId = 0;
  let currentFilter = 'all';
  let isWindowOpen = false;
  let pollInterval = null;
  let lastUnreadCount = 0;
  let approvedTemplates = [];

  // =========================================================================
  // 1. DRAG & DROP ENGINE (PRECISION DRAGGING)
  // =========================================================================

  // --- A. FAB Draggable Logic ---
  makeDraggable(fab[0], {
    storageKey: 'safedest_whatsapp_fab_pos',
    handle: fab[0],
    isFab: true,
    onClick: function () {
      toggleChatWindow();
    }
  });

  // --- B. Window Draggable Logic ---
  makeDraggable(win[0], {
    storageKey: 'safedest_whatsapp_win_pos',
    handle: winHeader[0],
    isFab: false
  });

  function makeDraggable(el, opts) {
    if (!el) return;

    let isDragging = false;
    let startX = 0, startY = 0;
    let initialLeft = 0, initialTop = 0;
    let movedDistance = 0;
    const dragThreshold = 6; // px to distinguish click vs drag

    // Restore saved position
    const savedPos = localStorage.getItem(opts.storageKey);
    if (savedPos) {
      try {
        const pos = JSON.parse(savedPos);
        const maxL = Math.max(10, window.innerWidth - el.offsetWidth - 10);
        const maxT = Math.max(10, window.innerHeight - el.offsetHeight - 10);
        const clampedL = Math.min(Math.max(10, pos.left), maxL);
        const clampedT = Math.min(Math.max(10, pos.top), maxT);

        el.style.left = clampedL + 'px';
        el.style.top = clampedT + 'px';
        el.style.right = 'auto';
        el.style.bottom = 'auto';
      } catch (e) {}
    }

    const onPointerDown = function (e) {
      // Don't drag if clicked on button inside handle
      if ($(e.target).closest('button, a, input').length && e.target !== el) {
        return;
      }

      isDragging = true;
      movedDistance = 0;
      startX = e.clientX;
      startY = e.clientY;

      const rect = el.getBoundingClientRect();
      initialLeft = rect.left;
      initialTop = rect.top;

      if (opts.handle) {
        opts.handle.style.cursor = 'grabbing';
      }

      if (e.target.setPointerCapture && e.pointerId !== undefined) {
        try { e.target.setPointerCapture(e.pointerId); } catch (err) {}
      }

      document.addEventListener('pointermove', onPointerMove);
      document.addEventListener('pointerup', onPointerUp);
      document.addEventListener('pointercancel', onPointerUp);
    };

    const onPointerMove = function (e) {
      if (!isDragging) return;

      const dx = e.clientX - startX;
      const dy = e.clientY - startY;
      movedDistance = Math.hypot(dx, dy);

      if (movedDistance > dragThreshold) {
        // Compute clamped coordinates
        const newLeft = initialLeft + dx;
        const newTop = initialTop + dy;

        const maxLeft = Math.max(10, window.innerWidth - el.offsetWidth - 10);
        const maxTop = Math.max(10, window.innerHeight - el.offsetHeight - 10);

        const clampedLeft = Math.min(Math.max(10, newLeft), maxLeft);
        const clampedTop = Math.min(Math.max(10, newTop), maxTop);

        el.style.left = clampedLeft + 'px';
        el.style.top = clampedTop + 'px';
        el.style.right = 'auto';
        el.style.bottom = 'auto';
      }
    };

    const onPointerUp = function (e) {
      if (!isDragging) return;
      isDragging = false;

      if (opts.handle) {
        opts.handle.style.cursor = 'grab';
      }

      document.removeEventListener('pointermove', onPointerMove);
      document.removeEventListener('pointerup', onPointerUp);
      document.removeEventListener('pointercancel', onPointerUp);

      if (movedDistance > dragThreshold) {
        // Save final position
        const rect = el.getBoundingClientRect();
        localStorage.setItem(opts.storageKey, JSON.stringify({
          left: Math.round(rect.left),
          top: Math.round(rect.top)
        }));
      } else if (opts.onClick) {
        // Trigger click if within click threshold
        opts.onClick();
      }
    };

    const handle = opts.handle || el;
    handle.addEventListener('pointerdown', onPointerDown);
  }

  // =========================================================================
  // 2. WINDOW VISIBILITY & CONTROLS
  // =========================================================================

  function toggleChatWindow() {
    if (win.hasClass('d-none')) {
      openChatWindow();
    } else {
      closeChatWindow();
    }
  }

  function openChatWindow() {
    win.removeClass('d-none');
    isWindowOpen = true;
    fabToast.addClass('d-none');

    // Ensure window position is visible inside viewport
    clampElementInsideViewport(win[0]);

    loadSummaryData();
    restartPolling(4000);
  }

  function closeChatWindow() {
    win.addClass('d-none');
    isWindowOpen = false;
    restartPolling(12000);
  }

  winClose.on('click', function () {
    closeChatWindow();
  });

  // Restore Compact Mode state from localStorage if previously set
  if (localStorage.getItem('safedest_whatsapp_compact') === '1') {
    win.addClass('compact-mode');
    winCompact.addClass('active text-warning');
  }

  // Toggle Compact Mode (Mobile-like single-column view on desktop)
  winCompact.on('click', function () {
    if (win.hasClass('maximized')) {
      win.removeClass('maximized');
      winMaximize.find('i').attr('class', 'ti ti-arrows-maximize ti-xs');
    }
    win.toggleClass('compact-mode');
    const isCompact = win.hasClass('compact-mode');
    if (isCompact) {
      winCompact.addClass('active text-warning');
      localStorage.setItem('safedest_whatsapp_compact', '1');
    } else {
      winCompact.removeClass('active text-warning');
      localStorage.removeItem('safedest_whatsapp_compact');
    }
    clampElementInsideViewport(win[0]);
  });

  winMaximize.on('click', function () {
    if (win.hasClass('compact-mode')) {
      win.removeClass('compact-mode');
      winCompact.removeClass('active text-warning');
      localStorage.removeItem('safedest_whatsapp_compact');
    }
    win.toggleClass('maximized');
    const icon = win.hasClass('maximized') ? 'ti-arrows-minimize' : 'ti-arrows-maximize';
    winMaximize.find('i').attr('class', 'ti ' + icon + ' ti-xs');
  });

  winRefresh.on('click', function () {
    const icon = $(this).find('i');
    icon.addClass('ti-spin');
    loadSummaryData(function () {
      icon.removeClass('ti-spin');
    });
  });

  $('#whatsapp-toast-close').on('click', function (e) {
    e.stopPropagation();
    fabToast.addClass('d-none');
  });

  fabToast.on('click', function () {
    openChatWindow();
  });

  // Mobile & Compact Back Button (back to conversations list)
  mobileBackBtn.on('click', function () {
    $('.whatsapp-window-body').removeClass('chat-active');
  });

  // Re-clamp elements if window is resized (e.g. rotating phone, resizing browser)
  $(window).on('resize', function () {
    if (!win.hasClass('d-none')) {
      clampElementInsideViewport(win[0]);
    }
    clampElementInsideViewport(fab[0]);
  });

  function clampElementInsideViewport(el) {
    if (!el || el.classList.contains('maximized')) return;
    const rect = el.getBoundingClientRect();
    const maxL = Math.max(10, window.innerWidth - el.offsetWidth - 10);
    const maxT = Math.max(10, window.innerHeight - el.offsetHeight - 10);

    let l = rect.left;
    let t = rect.top;

    if (rect.right > window.innerWidth || rect.left < 10) {
      l = Math.min(Math.max(10, l), maxL);
      el.style.left = l + 'px';
      el.style.right = 'auto';
    }
    if (rect.bottom > window.innerHeight || rect.top < 10) {
      t = Math.min(Math.max(10, t), maxT);
      el.style.top = t + 'px';
      el.style.bottom = 'auto';
    }
  }

  // =========================================================================
  // 3. DATA FETCHING & LIVE SYNC
  // =========================================================================

  function loadSummaryData(callback) {
    const search = searchInput.val().trim();
    $.get(baseUrl + 'admin/whatsapp-chat/widget-summary', {
      search: search,
      filter: currentFilter
    })
      .done(function (res) {
        if (res.status === 'success') {
          updateUnreadBadge(res.unread_total);
          renderConversations(res.conversations);
          approvedTemplates = res.approved_templates || [];
          renderTemplatesDropdown(approvedTemplates);

          // Trigger alert if new message arrived
          if (res.unread_total > lastUnreadCount && lastUnreadCount !== 0) {
            playChimeSound();
            const latestConv = res.conversations.find(c => c.unread_count > 0) || res.conversations[0];
            if (latestConv && !isWindowOpen) {
              showToastNotification(latestConv.user_name || latestConv.phone_number, latestConv.last_message_preview);
            }
          }
          lastUnreadCount = res.unread_total;
        }
      })
      .always(function () {
        convLoading.addClass('d-none');
        if (typeof callback === 'function') callback();
      });
  }

  function updateUnreadBadge(count) {
    const total = parseInt(count, 10) || 0;
    if (total > 0) {
      fabBadge.text(total > 99 ? '99+' : total).removeClass('d-none');
      winUnreadBadge.text(total);
      fabPulse.show();
    } else {
      fabBadge.addClass('d-none');
      winUnreadBadge.text('0');
      fabPulse.hide();
    }
  }

  function renderConversations(conversations) {
    convLoading.addClass('d-none');
    if (!conversations || conversations.length === 0) {
      convList.html('<div class="text-center py-4 text-muted small">لا توجد محادثات متطابقة</div>');
      return;
    }

    let html = '';
    conversations.forEach(function (c) {
      const isActive = activeConversationId == c.id ? 'active' : '';
      const avatarHtml = c.avatar
        ? `<img src="${c.avatar}" alt="${c.user_name}">`
        : (c.user_type === 'driver' ? '🚗' : (c.user_type === 'customer' ? '👤' : '💬'));
      const badgeClass = c.user_type === 'driver' ? 'bg-label-warning' : (c.user_type === 'customer' ? 'bg-label-primary' : 'bg-label-secondary');
      const unreadBadgeHtml = c.unread_count > 0 ? `<span class="badge bg-danger rounded-pill px-1 small">${c.unread_count}</span>` : '';

      html += `
        <div class="whatsapp-conv-item ${isActive}" data-id="${c.id}" data-phone="${c.phone_number}">
          <div class="whatsapp-conv-avatar">${avatarHtml}</div>
          <div class="flex-grow-1 overflow-hidden">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <strong class="text-truncate small text-dark">${c.user_name || c.phone_number}</strong>
              <small class="text-muted text-nowrap" style="font-size: 10px;">${c.last_message_time || ''}</small>
            </div>
            <div class="d-flex align-items-center justify-content-between">
              <span class="text-muted small text-truncate" style="max-width: 160px; font-size: 11px;">
                ${c.last_message_preview || 'محادثة واتساب'}
              </span>
              <div class="d-flex align-items-center gap-1">
                <span class="badge ${badgeClass} rounded-pill px-1" style="font-size: 9px;">${c.user_type_label || ''}</span>
                ${unreadBadgeHtml}
              </div>
            </div>
          </div>
        </div>
      `;
    });

    convList.html(html);
  }

  function renderTemplatesDropdown(templates) {
    if (!templates || templates.length === 0) {
      templatesDropdown.html('<li class="dropdown-header text-uppercase small">القوالب المعتمدة</li><li><span class="dropdown-item text-muted small">لا توجد قوالب معتمدة</span></li>');
      return;
    }

    let items = '<li class="dropdown-header text-uppercase small fw-bold">اختر قالباً لإرساله:</li>';
    templates.forEach(function (t) {
      items += `
        <li>
          <a class="dropdown-item small text-truncate send-template-item" href="javascript:void(0);" data-id="${t.id}" data-name="${t.name}">
            <i class="ti ti-message-dots text-success ti-xs me-1"></i>
            ${t.name}
          </a>
        </li>
      `;
    });

    templatesDropdown.html(items);
  }

  // =========================================================================
  // 4. ACTIVE CONVERSATION & CHAT MESSAGES
  // =========================================================================

  $(document).on('click', '.whatsapp-conv-item', function () {
    const convId = $(this).data('id');
    selectConversation(convId);
  });

  function selectConversation(id) {
    activeConversationId = id;
    $('.whatsapp-conv-item').removeClass('active');
    $(`.whatsapp-conv-item[data-id="${id}"]`).addClass('active');

    // Switch mobile view to chat pane
    $('.whatsapp-window-body').addClass('chat-active');

    emptyState.addClass('d-none');
    activeChatContainer.removeClass('d-none').addClass('d-flex');
    messagesStream.html('<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-success" role="status"></div></div>');

    $.get(baseUrl + `admin/whatsapp-chat/${id}/messages`)
      .done(function (res) {
        if (res.status === 'success') {
          const conv = res.conversation;
          const user = res.user_info;

          // Header
          activeName.text(user.name || conv.phone_number);
          activePhone.text(conv.phone_number);
          activeBadge.text(user.type_label);
          if (user.avatar) {
            activeAvatar.html(`<img src="${user.avatar}" class="rounded-circle w-100 h-100 object-fit-cover">`);
          } else {
            activeAvatar.html(`<span class="avatar-initials bg-label-success rounded-circle">👤</span>`);
          }

          // 24H Window status
          if (conv.is_window_open) {
            windowStatusBadge.removeClass('bg-label-warning').addClass('bg-label-success')
              .html(`<i class="ti ti-clock-check ti-xs me-1"></i>مفتوحة (${conv.window_remaining_hours}h)`);
          } else {
            windowStatusBadge.removeClass('bg-label-success').addClass('bg-label-warning')
              .html(`<i class="ti ti-clock-pause ti-xs me-1"></i>نافذة مغلقة`);
          }

          // Render messages
          renderMessages(res.messages);

          // Update unread count immediately
          if (res.unread_stats) {
            updateUnreadBadge(res.unread_stats.unread_messages);
          }

          // Mark as read on server & notify Saei
          $.post(baseUrl + `admin/whatsapp-chat/${id}/mark-read`, {
            _token: $('meta[name="csrf-token"]').attr('content')
          }).done(function (markRes) {
            if (markRes && markRes.unread_total !== undefined) {
              updateUnreadBadge(markRes.unread_total);
            }
          });
        }
      });
  }

  function renderMessages(messages) {
    if (!messages || messages.length === 0) {
      messagesStream.html('<div class="text-center py-4 text-muted small">لا توجد رسائل سابقة. ابدأ المحادثة الآن!</div>');
      activeLastMessageId = 0;
      return;
    }

    let html = '';
    messages.forEach(function (m) {
      html += buildMessageBubble(m);
      if (m.id > activeLastMessageId) {
        activeLastMessageId = m.id;
      }
    });

    messagesStream.html(html);
    scrollToBottom();
  }

  function buildMessageBubble(m) {
    const isOut = m.direction === 'outbound';
    const bubbleClass = isOut ? 'outbound' : 'inbound';
    const statusIcon = isOut ? (m.status === 'read' ? 'ti-checks text-primary' : (m.status === 'delivered' ? 'ti-checks text-muted' : (m.status === 'failed' ? 'ti-alert-circle text-danger' : 'ti-check text-muted'))) : '';

    let mediaHtml = '';
    if (m.media_url) {
      const isImg = m.message_type === 'image' || /\.(jpg|jpeg|png|webp|gif)$/i.test(m.media_url);
      const isVid = m.message_type === 'video' || /\.(mp4|mov|webm)$/i.test(m.media_url);
      const isAud = m.message_type === 'audio' || /\.(mp3|ogg|wav|m4a)$/i.test(m.media_url);

      if (isImg) {
        mediaHtml = `
          <div class="whatsapp-media-preview mb-1">
            <img src="${m.media_url}" alt="صورة مرفقة" onclick="window.open('${m.media_url}', '_blank')">
          </div>
        `;
      } else if (isVid) {
        mediaHtml = `
          <div class="whatsapp-media-preview mb-1">
            <video src="${m.media_url}" controls class="w-100 rounded" style="max-height: 180px;"></video>
          </div>
        `;
      } else if (isAud) {
        mediaHtml = `
          <div class="mb-1">
            <audio src="${m.media_url}" controls class="w-100" style="height: 36px;"></audio>
          </div>
        `;
      } else {
        const fname = m.media_filename || 'مستند مرفق';
        mediaHtml = `
          <a href="${m.media_url}" target="_blank" class="whatsapp-doc-card">
            <i class="ti ti-file-text fs-4 text-primary"></i>
            <span class="text-truncate small flex-grow-1" style="max-width: 140px;">${escapeHtml(fname)}</span>
            <i class="ti ti-download text-muted"></i>
          </a>
        `;
      }
    }

    let errorHtml = '';
    if (m.status === 'failed' && m.error_code) {
      errorHtml = `<div class="text-danger small mt-1" style="font-size: 10px;"><i class="ti ti-alert-triangle ti-xs me-1"></i>${escapeHtml(m.error_code)}</div>`;
    }

    const contentHtml = (m.content && m.content !== m.media_filename)
      ? `<div class="whatsapp-bubble-content">${escapeHtml(m.content)}</div>`
      : '';

    return `
      <div class="whatsapp-bubble ${bubbleClass}" data-id="${m.id}">
        ${mediaHtml}
        ${contentHtml}
        ${errorHtml}
        <div class="whatsapp-bubble-time">
          <span>${m.time || ''}</span>
          ${isOut ? `<i class="ti ${statusIcon} ti-xs ms-1"></i>` : ''}
        </div>
      </div>
    `;
  }

  function scrollToBottom() {
    setTimeout(() => {
      messagesStream.scrollTop(messagesStream[0].scrollHeight);
    }, 50);
  }

  // Reply Form Submit
  replyForm.on('submit', function (e) {
    e.preventDefault();
    if (!activeConversationId) return;

    const message = replyInput.val().trim();
    if (!message) return;

    // Optimistic UI Append
    const tempId = 'temp_' + Date.now();
    const tempBubble = `
      <div class="whatsapp-bubble outbound" id="${tempId}">
        <div class="whatsapp-bubble-content">${escapeHtml(message)}</div>
        <div class="whatsapp-bubble-time">
          <small class="text-muted">جاري الإرسال...</small>
          <i class="ti ti-clock ti-xs ms-1 text-muted"></i>
        </div>
      </div>
    `;
    messagesStream.append(tempBubble);
    scrollToBottom();

    replyInput.val('');
    sendBtn.prop('disabled', true);

    $.post(baseUrl + `admin/whatsapp-chat/${activeConversationId}/send`, {
      message: message,
      _token: $('meta[name="csrf-token"]').attr('content')
    })
      .done(function (res) {
        if (res.status === 'success' && res.message) {
          const m = res.message;
          $(`#${tempId}`).replaceWith(buildMessageBubble({
            id: m.id || Date.now(),
            direction: 'outbound',
            content: message,
            status: 'sent',
            time: res.time || 'الآن'
          }));
          if (m.id) {
            activeLastMessageId = Math.max(activeLastMessageId, m.id);
          }
        }
      })
      .fail(function (xhr) {
        const errMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'فشل الإرسال';
        $(`#${tempId} .whatsapp-bubble-time`).html(`<span class="text-danger small">${escapeHtml(errMsg)}</span> <i class="ti ti-alert-triangle text-danger ti-xs"></i>`);
      })
      .always(function () {
        sendBtn.prop('disabled', false);
        replyInput.focus();
      });
  });

  // Attach File Trigger & Upload
  attachBtn.on('click', function () {
    if (!activeConversationId) return;
    fileInput.trigger('click');
  });

  fileInput.on('change', function () {
    const file = this.files[0];
    if (!file || !activeConversationId) return;

    const tempId = 'temp_file_' + Date.now();
    const tempBubble = `
      <div class="whatsapp-bubble outbound" id="${tempId}">
        <div class="d-flex align-items-center gap-2">
          <div class="spinner-border spinner-border-sm text-success" role="status"></div>
          <span class="small">جاري رفع وإرسال ${escapeHtml(file.name)}...</span>
        </div>
        <div class="whatsapp-bubble-time">
          <small class="text-muted">جاري الإرسال...</small>
        </div>
      </div>
    `;
    messagesStream.append(tempBubble);
    scrollToBottom();

    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

    $.ajax({
      url: baseUrl + `admin/whatsapp-chat/${activeConversationId}/send-media`,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (res) {
        if (res.status === 'success') {
          $(`#${tempId}`).replaceWith(buildMessageBubble({
            id: Date.now(),
            direction: 'outbound',
            message_type: res.media_type || 'document',
            media_url: res.media_url,
            media_filename: res.media_filename,
            content: res.caption || res.media_filename,
            status: 'sent',
            time: res.time || 'الآن'
          }));
        } else {
          $(`#${tempId} .whatsapp-bubble-time`).html(`<span class="text-danger small">${res.message || 'فشل الإرسال'}</span>`);
        }
      },
      error: function (xhr) {
        const errMsg = xhr.responseJSON ? xhr.responseJSON.message : 'فشل رفع الملف';
        $(`#${tempId} .whatsapp-bubble-time`).html(`<span class="text-danger small">${errMsg}</span>`);
      },
      complete: function () {
        fileInput.val('');
      }
    });
  });

  // Send Template Click
  $(document).on('click', '.send-template-item', function () {
    if (!activeConversationId) return;
    const templateId = $(this).data('id');
    const templateName = $(this).data('name');

    if (!confirm(`هل أنت متأكد من رغبتك في إرسال قالب (${templateName}) لهذا المحادثة؟`)) {
      return;
    }

    $.post(baseUrl + `admin/whatsapp-chat/${activeConversationId}/send-template`, {
      template_id: templateId,
      _token: $('meta[name="csrf-token"]').attr('content')
    })
      .done(function (res) {
        if (res.status === 'success') {
          selectConversation(activeConversationId);
        }
      });
  });

  // Search & Filter Events
  searchInput.on('input', function () {
    const val = $(this).val().trim();
    searchClear.toggleClass('d-none', val === '');
    debounce(function () {
      loadSummaryData();
    }, 300)();
  });

  searchClear.on('click', function () {
    searchInput.val('');
    searchClear.addClass('d-none');
    loadSummaryData();
  });

  $('.filter-btn').on('click', function () {
    $('.filter-btn').removeClass('btn-primary active').addClass('btn-outline-secondary');
    $(this).removeClass('btn-outline-secondary').addClass('btn-primary active');
    currentFilter = $(this).data('filter');
    loadSummaryData();
  });

  // =========================================================================
  // 5. NOTIFICATION ALERTS & SOUND
  // =========================================================================

  function showToastNotification(sender, message) {
    toastTitle.text(sender);
    toastBody.text(message || 'وصلتك رسالة جديدة على الواتساب');
    fabToast.removeClass('d-none');

    // Auto dismiss after 7 seconds
    setTimeout(() => {
      fabToast.addClass('d-none');
    }, 7000);
  }

  function playChimeSound() {
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      const ctx = new AudioCtx();
      const now = ctx.currentTime;

      const osc = ctx.createOscillator();
      const gain = ctx.createGain();

      osc.type = 'sine';
      osc.frequency.setValueAtTime(587.33, now); // D5
      osc.frequency.exponentialRampToValueAtTime(880, now + 0.15); // A5

      gain.gain.setValueAtTime(0.3, now);
      gain.gain.exponentialRampToValueAtTime(0.01, now + 0.4);

      osc.connect(gain);
      gain.connect(ctx.destination);

      osc.start(now);
      osc.stop(now + 0.4);
    } catch (e) {}
  }

  // =========================================================================
  // 6. POLLING & INITIALIZATION
  // =========================================================================

  function restartPolling(ms) {
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(function () {
      loadSummaryData();

      // Poll active thread if open
      if (isWindowOpen && activeConversationId) {
        $.get(baseUrl + `admin/whatsapp-chat/${activeConversationId}/poll`, {
          after_id: activeLastMessageId
        }).done(function (res) {
          if (res.status === 'success' && res.messages && res.messages.length > 0) {
            res.messages.forEach(function (m) {
              if (m.id > activeLastMessageId) {
                messagesStream.append(buildMessageBubble(m));
                activeLastMessageId = m.id;
              }
            });
            scrollToBottom();
          }
        });
      }
    }, ms);
  }

  function escapeHtml(str) {
    return $('<div>').text(str).html();
  }

  let debounceTimer = null;
  function debounce(func, delay) {
    return function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => func.apply(this, arguments), delay);
    };
  }

  // Initial load & start background polling
  loadSummaryData();
  restartPolling(12000);
});
