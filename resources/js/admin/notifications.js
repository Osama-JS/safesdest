/**
 * Admin Notification System
 * Handles fetching, displaying, and managing notifications in the navbar.
 */

$(function () {
  // Check if we are on a page with the notification dropdown and permission
  const notificationDropdown = $('.dropdown-notifications');
  if (notificationDropdown.length === 0) return;

  const notificationList = $('.dropdown-notifications-list .list-group');
  const badge = $('.badge-notifications');
  const headerBadge = $('.dropdown-header .badge');

  // Base URL for API
  const apiBase = '/admin/system-notifications';

  // Template for notification item
  function notificationTemplate(notification) {
    const isReadClass = notification.is_read ? 'marked-as-read' : '';
    const iconClass = notification.is_read ? 'ti-mail-opened' : 'ti-mail';
    const bgClass = notification.is_read ? 'bg-label-secondary' : 'bg-label-primary';

    return `
      <li class="list-group-item list-group-item-action dropdown-notifications-item ${isReadClass}" data-id="${notification.id}">
        <div class="d-flex">
          <div class="flex-shrink-0 me-3">
            <div class="avatar">
              <span class="avatar-initial rounded-circle ${bgClass}"><i class="ti ${iconClass}"></i></span>
            </div>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-1 small">${notification.title}</h6>
            <small class="mb-1 d-block text-body">${notification.message}</small>
            <small class="text-muted">${notification.created_at}</small>
          </div>
          <div class="flex-shrink-0 dropdown-notifications-actions">
            ${
              !notification.is_read
                ? `<a href="javascript:void(0)" class="dropdown-notifications-read" title="Mark as read"><span class="badge badge-dot"></span></a>`
                : ''
            }
          </div>
        </div>
      </li>
    `;
  }

  // Load notifications
  function loadNotifications() {
    $.ajax({
      url: apiBase,
      method: 'GET',
      success: function (response) {
        if (response.success) {
          renderNotifications(response.data);
          updateUnreadCount(); // Update count separately to ensure accuracy
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

          // Update navbar badge
          if (count > 0) {
            badge.text(count).show();
          } else {
            badge.hide();
          }

          // Update header badge inside dropdown
          headerBadge.text(`${count} New`);
        }
      }
    });
  }

  // Render list
  function renderNotifications(notifications) {
    notificationList.empty();

    if (notifications.length === 0) {
      notificationList.append('<li class="list-group-item text-center p-4">No notifications found</li>');
      return;
    }

    notifications.forEach(notification => {
      notificationList.append(notificationTemplate(notification));
    });
  }

  // Mark single notification as read
  $(document).on('click', '.dropdown-notifications-read', function (e) {
    e.stopPropagation(); // Prevent dropdown from closing
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
          item.find('.ti-mail').removeClass('ti-mail').addClass('ti-mail-opened');
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
          loadNotifications(); // Reload to update UI
        }
      }
    });
  });

  // Initial load
  loadNotifications();

  // Poll for new notifications every 60 seconds
  setInterval(loadNotifications, 60000);
});
