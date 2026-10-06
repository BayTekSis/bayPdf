<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>BayPdf — {{ __('baypdf::designer.studio') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/baypdf/designer.css') }}">
</head>
<body>
    <div id="baypdf" data-base="{{ url(config('baypdf.path')) }}" data-locale="{{ app()->getLocale() }}" data-messages="{{ json_encode(__('baypdf::designer'), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) }}"></div>
    <noscript>{{ __('baypdf::designer.javascript') }}</noscript>
    <script type="module" src="{{ asset('vendor/baypdf/designer.js') }}"></script>
</body>
</html>
