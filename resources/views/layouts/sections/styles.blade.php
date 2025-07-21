<!-- BEGIN: Theme CSS-->
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;700;800;900&display=swap"
    rel="stylesheet">


@vite(['resources/assets/vendor/fonts/tabler-icons.scss', 'resources/assets/vendor/fonts/fontawesome.scss', 'resources/assets/vendor/fonts/flag-icons.scss', 'resources/assets/vendor/libs/node-waves/node-waves.scss'])
<!-- Core CSS -->
@vite(['resources/assets/vendor/scss' . $configData['rtlSupport'] . '/core' . ($configData['style'] !== 'light' ? '-' . $configData['style'] : '') . '.scss', 'resources/assets/vendor/scss' . $configData['rtlSupport'] . '/' . $configData['theme'] . ($configData['style'] !== 'light' ? '-' . $configData['style'] : '') . '.scss', 'resources/assets/css/demo.css'])
{{-- 
<style>
    /* تطبيق الخط على جميع عناصر Bootstrap */
    .card,
    .card-header,
    .card-body,
    .card-footer,
    .btn,
    .btn-primary,
    .btn-secondary,
    .btn-success,
    .btn-danger,
    .btn-warning,
    .btn-info,
    .btn-light,
    .btn-dark,
    .form-control,
    .form-select,
    .form-label,
    .form-text,
    .form-check-label,
    .table,
    .table th,
    .table td,
    .nav,
    .nav-link,
    .navbar,
    .navbar-brand,
    .navbar-nav,
    .dropdown-menu,
    .dropdown-item,
    .modal,
    .modal-header,
    .modal-body,
    .modal-footer,
    .modal-title,
    .alert,
    .alert-heading,
    .badge,
    .breadcrumb,
    .breadcrumb-item,
    .pagination,
    .page-link,
    .list-group,
    .list-group-item,
    .accordion,
    .accordion-header,
    .accordion-body,
    .toast,
    .toast-header,
    .toast-body,
    .offcanvas,
    .offcanvas-header,
    .offcanvas-body,
    .tooltip,
    .popover,
    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    p,
    span,
    div,
    a,
    label,
    input,
    textarea,
    select,
    option,
    .text-muted,
    .text-primary,
    .text-secondary,
    .text-success,
    .text-danger,
    .text-warning,
    .text-info,
    .sidebar,
    .sidebar-menu,
    .sidebar-item,
    .content-wrapper,
    .main-content,
    .dataTables_wrapper,
    .dataTables_info,
    .dataTables_paginate,
    .select2-container,
    .select2-selection,
    .select2-results {
        font-family: 'Tajawal', sans-serif !important;
    }

    /* تطبيق الخط على عناصر DataTables */
    .dataTables_wrapper * {
        font-family: 'Tajawal', sans-serif !important;
    }

    /* تطبيق الخط على عناصر Select2 */
    .select2-container * {
        font-family: 'Tajawal', sans-serif !important;
    }

    /* تطبيق الخط على عناصر Toastr */
    .toast-container * {
        font-family: 'Tajawal', sans-serif !important;
    }

    /* تحسين عرض النص العربي */
    body[dir="rtl"] {
        font-family: 'Tajawal', sans-serif !important;
        text-align: right;
    }

    /* تحسين عرض الأرقام العربية */
    .arabic-numbers {
        font-feature-settings: "lnum";
    }
</style> --}}

<!-- Vendor Styles -->
@vite(['resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss', 'resources/assets/vendor/libs/typeahead-js/typeahead.scss', 'resources/assets/vendor/libs/spinkit/spinkit.scss', 'resources/assets/vendor/libs/toastr/toastr.scss'])
@yield('vendor-style')

<!-- Page Styles -->
@yield('page-style')

@livewireStyles
