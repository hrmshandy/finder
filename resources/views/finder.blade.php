<!DOCTYPE html>
<html>
    <head>
        <meta name="_token" content="{!! csrf_token() !!}">

        <title>Finder</title>

        <link href="{{ asset('vendor/finder/css/finder.css') }}" rel="stylesheet" type="text/css">

        <script src="{{ asset('vendor/modernizr/modernizr.js') }}"></script>
    </head>
    <body>
        <file-manager csrf="{!! csrf_token() !!}" accept="image/*"></file-manager>

        <script>
            window.baseUrl = "{{ url('/') }}";
        </script>
        <script src="{{ asset('vendor/finder/js/finder.js') }}"></script>

    </body>
</html>
