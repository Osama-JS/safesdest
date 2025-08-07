self.addEventListener('push', function (event) {
  const data = event.data.json();

  const title = data.title || 'إشعار جديد';
  const options = {
    body: data.body || '',
    icon: data.icon || '/icon.png',
    image: data.image || null,
    data: data.data || {}
  };

  event.waitUntil(self.registration.showNotification(title, options));
});
