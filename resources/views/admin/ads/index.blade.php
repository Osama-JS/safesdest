@extends('layouts/layoutMaster')

@section('title', __('Tasks Ads'))

<!-- Vendor Styles -->
@section('vendor-style')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css"
        @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']) <style>
    /* تنسيق البطاقة */

    <style>
        .card-img-top {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-bottom: 1px solid #ddd;
        }

        /* تنسيق الصورة الدائرية */
        .avatar {
            width: 50px;
            height: 50px;
            display: inline-block;
            background-color: #ccc;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 10px;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* تنسيق عناوين البطاقة */
        .card-body {
            padding: 15px;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: #333;
        }

        .card-text {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        /* تنسيق عناوين الأسعار */
        .card-footer {
            background-color: transparent;
            border-top: 1px solid #ddd;

        }


        .card-footer .btn:hover {
            background-color: #0056b3;
        }

        /* تنسيق الرسالة عندما لا توجد بيانات */
        .alert {
            color: #6c757d;
            padding: 10px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }

        /* تنسيق الخريطة */
        .map-container {
            height: 150px;
            width: 100%;
            margin-bottom: 10px;
        }
    </style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/admin/ads.js'])

    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">{{ __('Tasks Ads') }}</h5>
            {{-- <p>Organize your Manager into logical groups to efficiently manage your field operations. You may group them on
                the basis of location, geography, type of service and so on and so forth.</p> --}}

        </div>
        <div class="row mb-3 p-3">
            <div class="col-md-12">

                <input type="text" id="search-team" class="form-control " placeholder="🔍 Search Team">

            </div>

        </div>

    </div>

    <div class="container mt-5">
        <div id="ads-container" class="row ">

        </div>

        <div class="d-flex justify-content-center">
            <ul class="pagination" id="pagination">

            </ul>
        </div>
    </div>



@endsection
