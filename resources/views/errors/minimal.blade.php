<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title')</title>


    <link type="text/css" rel="stylesheet" href="{{ url(asset('/assets/errors/css/style.css')) }}" />


</head>

<body>

    <div id="notfound">
        <div class="notfound">
            <div class="notfound-404"></div>
            <h1>@yield('code')</h1>
            <h2> @yield('message')</h2>
            <p>@yield('desc')</p>
            @yield('content')
            <a href="{{ url('/') }}">Go To Home Page</a>
        </div>
    </div>

</body>

</html>
