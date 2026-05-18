<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'UPAS') }} - Pangasinan State University</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Assets: prefer built files from manifest; CDN fallback if unavailable -->
        @php
            $manifestPath = public_path('build/manifest.json');
            $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : null;
            $cssFile = $manifest['resources/css/app.css']['file'] ?? null;
            $jsFile = $manifest['resources/js/app.js']['file'] ?? null;
        @endphp
        @if($cssFile && $jsFile)
            <link rel="stylesheet" href="{{ asset('build/' . $cssFile) }}">
            <script type="module" src="{{ asset('build/' . $jsFile) }}"></script>
        @else
            <script src="https://cdn.tailwindcss.com"></script>
            <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
        @endif
        
        <style>
            body {
                font-family: 'Inter', 'Poppins', sans-serif;
            }
            
            .uaps-auth-bg {
                background: linear-gradient(135deg, #e6f0ff 0%, #ffffff 100%);
                min-height: 100vh;
            }
            
            .uaps-auth-card {
                background: white;
                border-radius: 25px;
                box-shadow: 0 20px 60px rgba(0, 70, 173, 0.1);
                overflow: hidden;
            }
            
            .uaps-illustration {
                background: linear-gradient(135deg, #0046ad 0%, #0052cc 50%, #FFD700 100%);
                position: relative;
                overflow: hidden;
            }
            
            .uaps-illustration::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 300'%3E%3Cdefs%3E%3ClinearGradient id='sky' x1='0%25' y1='0%25' x2='0%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%230046ad'/%3E%3Cstop offset='100%25' stop-color='%23b0c4de'/%3E%3C/linearGradient%3E%3ClinearGradient id='sun' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%23FFD700'/%3E%3Cstop offset='100%25' stop-color='%23FFF8DC'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='400' height='300' fill='url(%23sky)'/%3E%3Ccircle cx='320' cy='80' r='35' fill='url(%23sun)' opacity='0.9'/%3E%3Cpath d='M0 200 Q100 180 200 190 T400 200 L400 300 L0 300 Z' fill='%23b0c4de' opacity='0.6'/%3E%3Cpath d='M50 220 Q100 210 150 215 T250 220 L250 300 L50 300 Z' fill='%23ffffff' opacity='0.4'/%3E%3Cpath d='M80 240 L90 220 L100 240 L110 225 L120 240 L130 230 L140 245 L140 300 L80 300 Z' fill='%230046ad' opacity='0.3'/%3E%3Cpath d='M200 235 L210 215 L220 235 L230 220 L240 235 L250 225 L260 240 L260 300 L200 300 Z' fill='%230046ad' opacity='0.3'/%3E%3Cpath d='M300 245 L310 225 L320 245 L330 230 L340 245 L350 235 L360 250 L360 300 L300 300 Z' fill='%230046ad' opacity='0.3'/%3E%3C/svg%3E") center/cover no-repeat;
                opacity: 0.8;
            }
            
            .uaps-form-input {
                border: 2px solid #b0c4de;
                border-radius: 12px;
                padding: 14px 16px;
                transition: all 0.3s ease;
                background: white;
                color: #333;
            }
            
            .uaps-form-input:focus {
                border-color: #0046ad;
                box-shadow: 0 0 0 3px rgba(0, 70, 173, 0.1);
                outline: none;
            }
            
            .uaps-btn-primary {
                background: #0046ad;
                color: white;
                font-weight: 600;
                padding: 14px 24px;
                border-radius: 12px;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(0, 70, 173, 0.3);
            }
            
            .uaps-btn-primary:hover {
                background: #003d9a;
                box-shadow: 0 6px 20px rgba(0, 70, 173, 0.4);
                transform: translateY(-2px);
            }
            
            .uaps-social-btn {
                border: 2px solid #e5e7eb;
                border-radius: 12px;
                padding: 12px;
                transition: all 0.3s ease;
                background: white;
            }
            
            .uaps-social-btn:hover {
                border-color: #0046ad;
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(0, 70, 173, 0.1);
            }
            
            .uaps-divider {
                position: relative;
                text-align: center;
                margin: 24px 0;
            }
            
            .uaps-divider::before {
                content: '';
                position: absolute;
                top: 50%;
                left: 0;
                right: 0;
                height: 1px;
                background: #e5e7eb;
                transform: translateY(-50%);
            }
            
            .uaps-divider span {
                background: white;
                padding: 0 16px;
                color: #9ca3af;
                font-size: 14px;
            }
        </style>
    </head>
    <body class="uaps-auth-bg antialiased">
        <div class="min-h-screen flex items-center justify-center p-4">
            <!-- Main Auth Container -->
            <div class="w-full max-w-6xl">
                <div class="uaps-auth-card grid grid-cols-1 lg:grid-cols-2 min-h-[600px]">
                    <!-- Left Column - Login Form -->
                    <div class="p-12 flex flex-col justify-center">
                        {{ $slot }}
                    </div>
                    
                    <!-- Right Column - Illustration -->
                    <div class="uaps-illustration flex flex-col items-center justify-center relative">
                        <!-- Empty - just background illustration -->
                    </div>
                </div>
                
                <!-- Back to Landing Link -->
                <div class="mt-8 text-center">
                    <a href="{{ route('landing') }}" class="inline-flex items-center px-6 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-700 hover:border-blue-500 hover:text-blue-600 hover:bg-blue-50 transition-all duration-300 shadow-sm hover:shadow-md group">
                        <svg class="w-4 h-4 mr-2 transition-transform duration-300 group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span class="font-medium">Back to Homepage</span>
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>
