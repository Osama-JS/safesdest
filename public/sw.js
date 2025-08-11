// self.addEventListener('push', function (event) {
//   const data = event.data.json();

//   const title = data.title || 'إشعار جديد';
//   const options = {
//     body: data.body || '',
//     icon: data.icon || '/icon.png',
//     image: data.image || null,
//     data: data.data || {}
//   };

//   event.waitUntil(self.registration.showNotification(title, options));
// });


self.addEventListener('push', function(event) {
    const data = event.data.json();

    const options = {
        body: data.body,
        icon: data.icon || '/icon.png',
        image: data.image,
        data: {
            url: data.url || '/',
        }
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    const targetUrl = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            // إذا كان هناك نافذة مفتوحة بالفعل، نعيد استخدامها
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            // إذا لم تكن هناك نافذة مفتوحة، نفتح واحدة جديدة
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
