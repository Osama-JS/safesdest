<!-- BEGIN: Vendor JS-->



@vite(['resources/js/translator.js', 'resources/assets/vendor/libs/jquery/jquery.js', 'resources/assets/vendor/libs/popper/popper.js', 'resources/assets/vendor/js/bootstrap.js', 'resources/assets/vendor/libs/node-waves/node-waves.js', 'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js', 'resources/assets/vendor/libs/hammer/hammer.js', 'resources/assets/vendor/libs/typeahead-js/typeahead.js', 'resources/assets/vendor/js/menu.js', 'resources/assets/vendor/libs/block-ui/block-ui.js', 'resources/assets/vendor/libs/toastr/toastr.js'])

<!-- Notes JS -->

@vite(['resources/js/notes.js'])


@yield('vendor-script')
<!-- END: Page Vendor JS-->
<!-- BEGIN: Theme JS-->
@vite(['resources/assets/js/main.js'])

<!-- END: Theme JS-->
<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->
<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->

@if(auth()->check() && empty(auth()->user()->investor))
    @vite(['resources/js/admin/notifications.js'])
    @if(auth()->user()->can('view_whatsapp_chat'))
        @vite(['resources/js/admin/whatsapp-floating-chat.js'])
    @endif

<script>
    async function subscribeForPush() {
        try {
            const vapidKey = @json(config('webpush.vapid.public_key') ?: env('VAPID_PUBLIC_KEY'));
            if (!vapidKey || typeof vapidKey !== 'string' || vapidKey.trim() === '') {
                console.warn('VAPID public key غير متوفر لتسجيل الإشعارات');
                return;
            }

            const register = await navigator.serviceWorker.register("{{ url('/sw.js') }}");
            const convertedVapidKey = urlBase64ToUint8Array(vapidKey.trim());
            if (!convertedVapidKey || convertedVapidKey.length === 0) {
                console.warn('VAPID key غير صالح');
                return;
            }

            const subscription = await register.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: convertedVapidKey
            });

            await fetch("{{ route('notifications.subscribe') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(subscription)
            });

            console.log('تم الاشتراك في الإشعارات بنجاح');
        } catch (err) {
            console.warn('تم تخطي أو تعذر الاشتراك في إشعارات Push:', err.message || err);
        }
    }

    if ('serviceWorker' in navigator && 'PushManager' in window) {
        subscribeForPush().catch(err => console.warn('Push error:', err));
    } else {
        console.warn('Push messaging غير مدعوم');
    }

    function urlBase64ToUint8Array(base64String) {
        try {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');

            const rawData = window.atob(base64);
            return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
        } catch (e) {
            console.error('خطأ في تحويل مفتاح VAPID:', e);
            return new Uint8Array();
        }
    }
</script>
@endif

@stack('modals')
@livewireScripts
