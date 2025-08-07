<!-- BEGIN: Vendor JS-->



@vite(['resources/js/translator.js', 'resources/assets/vendor/libs/jquery/jquery.js', 'resources/assets/vendor/libs/popper/popper.js', 'resources/assets/vendor/js/bootstrap.js', 'resources/assets/vendor/libs/node-waves/node-waves.js', 'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js', 'resources/assets/vendor/libs/hammer/hammer.js', 'resources/assets/vendor/libs/typeahead-js/typeahead.js', 'resources/assets/vendor/js/menu.js', 'resources/assets/vendor/libs/block-ui/block-ui.js', 'resources/assets/vendor/libs/toastr/toastr.js'])

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

<script>
    async function subscribeForPush() {
        const register = await navigator.serviceWorker.register("{{ url('/sw.js') }}");
        const vapidKey = "{{ env('VAPID_PUBLIC_KEY') }}";
        const convertedVapidKey = urlBase64ToUint8Array(vapidKey);

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
        // alert('تم الاشتراك في الإشعارات بنجاح');
    }

    if ('serviceWorker' in navigator && 'PushManager' in window) {
        subscribeForPush();
    } else {
        console.warn('Push messaging غير مدعوم');
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');

        const rawData = window.atob(base64);
        return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
    }
</script>

@stack('modals')
@livewireScripts
