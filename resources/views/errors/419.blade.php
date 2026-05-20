<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Page Expired') }} — {{ config('app.name', 'UPAS') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f9fafb; color: #374151; }
        .box { text-align: center; max-width: 28rem; padding: 2rem; }
        h1 { font-size: 1.5rem; font-weight: 600; margin: 0 0 0.5rem; color: #111827; }
        p { margin: 0 0 1.5rem; line-height: 1.5; font-size: 0.9375rem; }
        a { display: inline-block; margin: 0.25rem; padding: 0.625rem 1.25rem; border-radius: 0.375rem; text-decoration: none; font-size: 0.875rem; font-weight: 500; }
        .primary { background: #4f46e5; color: #fff; }
        .primary:hover { background: #4338ca; }
        .secondary { background: #fff; color: #374151; border: 1px solid #d1d5db; }
        .secondary:hover { background: #f3f4f6; }
        .code { font-size: 0.75rem; color: #9ca3af; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <div class="box">
        <h1>{{ __('Page Expired') }}</h1>
        <p>{{ __('Your session or security token expired. This often happens after leaving a tab open or signing in on another browser.') }}</p>
        <div>
            <a class="primary" href="{{ route('logout') }}">{{ __('Log out and sign in again') }}</a>
            <a class="secondary" href="{{ route('login') }}">{{ __('Go to login') }}</a>
        </div>
        <p class="code">419</p>
    </div>
</body>
</html>
