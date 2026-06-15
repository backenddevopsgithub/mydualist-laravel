<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ $subject ?? config('mydualist.name') }}</title>
    <style>
        :root {
            color-scheme: light dark;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #efefef;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #111111;
        }

        .wrapper {
            width: 100%;
            background-color: #efefef;
            padding: 24px 12px;
        }

        .container {
            max-width: 560px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 18px;
            padding: 28px 24px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.08);
        }

        .logo {
            display: block;
            max-width: 220px;
            height: auto;
            margin: 0 auto 20px;
        }

        h1 {
            color: #073400;
            font-size: 24px;
            line-height: 1.3;
            text-align: center;
            margin: 0 0 18px;
        }

        p, li {
            color: #111111;
            font-size: 16px;
            line-height: 1.6;
            margin: 0 0 14px;
        }

        ul {
            padding-left: 20px;
            margin: 0 0 16px;
        }

        .button {
            display: inline-block;
            margin: 8px 0 16px;
            padding: 14px 28px;
            border-radius: 999px;
            background-color: #87ea5c;
            color: #073400 !important;
            font-weight: 700;
            text-decoration: none;
        }

        .button-secondary {
            background-color: #073400;
            color: #87ea5c !important;
        }

        .panel {
            border: 1px solid #d9d9d9;
            border-radius: 10px;
            padding: 16px;
            margin: 16px 0;
            background-color: #fafafa;
        }

        .footer {
            border-top: 1px solid #d9d9d9;
            margin-top: 24px;
            padding-top: 16px;
            text-align: center;
        }

        .footer a {
            color: #4d4d4d;
            font-size: 13px;
            margin: 0 8px;
        }

        .muted {
            color: #666666;
            font-size: 14px;
        }

        @media (prefers-color-scheme: dark) {
            body, .wrapper {
                background-color: #1a1a1a !important;
            }

            .container {
                background-color: #242424 !important;
                box-shadow: none;
            }

            h1 {
                color: #87ea5c !important;
            }

            p, li, .muted {
                color: #f2f2f2 !important;
            }

            .panel {
                background-color: #2f2f2f !important;
                border-color: #444444 !important;
            }

            .footer {
                border-color: #444444 !important;
            }

            .footer a {
                color: #cccccc !important;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <a href="{{ config('app.url') }}">
                <img src="{{ $logoUrl ?? \App\Domains\Notifications\Support\EmailPresentation::brandLogoUrl() }}" alt="{{ config('mydualist.name') }}" class="logo">
            </a>

            @yield('content')

            <div class="footer">
                <p class="muted">&copy; {{ now()->year }} {{ config('mydualist.name') }}. All rights reserved.</p>
                <p>
                    <a href="{{ route('cms.show', \App\Support\CmsPageSlugs::PRIVACY_POLICY) }}">Privacy Policy</a>
                    <a href="{{ route('cms.show', \App\Support\CmsPageSlugs::TERMS_AND_CONDITIONS) }}">Terms of Service</a>
                    <a href="{{ route('cms.show', \App\Support\CmsPageSlugs::HELP_AND_SUPPORT) }}">Help Center</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
