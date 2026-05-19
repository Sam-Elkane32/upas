<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'UPAS') }} - Login | Pangasinan State University</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    @include('partials.vite-production-assets')
    
    <style>
        body {
            font-family: 'Inter', 'Poppins', sans-serif;
        }
        [x-cloak] { display: none !important; }
        /* Neutral autofill (removes Chrome/Safari blue-yellow highlight) */
        .login-form input:-webkit-autofill,
        .login-form input:-webkit-autofill:hover,
        .login-form input:-webkit-autofill:focus,
        .login-form input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
            box-shadow: 0 0 0 1000px #ffffff inset !important;
            -webkit-text-fill-color: #111827 !important;
            caret-color: #111827;
            transition: background-color 99999s ease-out 0s;
        }
        .login-form input:autofill {
            box-shadow: 0 0 0 1000px #ffffff inset !important;
        }
    </style>
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Left Side - Hero Panel -->
        <div class="relative w-full md:w-1/2 min-h-screen flex flex-col items-center justify-center p-8 md:p-12 bg-cover bg-center bg-gray-800" style="background-image: url('{{ asset('images/psu_building.jpg') }}');">
            <!-- Blue Gradient Overlay - #0A4DFF at 65% opacity -->
            <div class="absolute inset-0 bg-[rgba(10,77,255,0.65)]"></div>
            
            <!-- Content Container -->
            <!-- Content Container: Logo, Welcome!, System name only — centered with even spacing -->
            <div class="relative z-10 flex flex-col items-center justify-center text-center w-full max-w-xl px-6 md:px-8 space-y-10 md:space-y-14">
                <!-- PSU Logo -->
                <div class="relative flex-shrink-0">
                    <div class="absolute inset-0 bg-blue-400 rounded-full blur-3xl opacity-20"></div>
                    <div class="relative">
                        @if(file_exists(public_path('images/psu_logo.png')))
                            <img src="{{ asset('images/psu_logo.png') }}" 
                                 alt="Pangasinan State University Seal" 
                                 class="w-36 h-36 md:w-44 md:h-44 object-contain drop-shadow-2xl mx-auto">
                        @else
                            <div class="w-36 h-36 md:w-44 md:h-44 bg-transparent flex items-center justify-center text-5xl font-bold text-white drop-shadow-2xl mx-auto">
                                PSU
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Welcome! -->
                <h1 class="text-5xl md:text-6xl font-bold text-white drop-shadow-lg tracking-tight">
                    Welcome!
                </h1>
                
                <!-- University Planning Accomplishment System -->
                <p class="text-2xl md:text-3xl text-white font-semibold drop-shadow-sm max-w-md mx-auto leading-snug">
                    University Planning Accomplishment System
                </p>
            </div>
        </div>
        
        <!-- Right Side - Login Card -->
        <div class="w-full md:w-1/2 flex items-center justify-center p-6 md:p-12 bg-gray-50">
            <div class="w-full max-w-lg ml-6 md:ml-8">
                <!-- Login Card with Glass Effect on Desktop -->
                <div class="bg-white md:bg-white/95 rounded-xl shadow-xl p-10 md:p-12 md:backdrop-blur-md md:border md:border-white/20">
                    <!-- Session Status -->
                    @if (session('status'))
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
                            {{ session('status') }}
                        </div>
                    @endif
                    
                    <!-- Login Form -->
                    <form method="POST" action="{{ route('login') }}" class="login-form space-y-7">
                        @csrf
                        
                        <!-- Email Address -->
                        <div>
                            <label for="email" class="block text-base font-semibold text-gray-700 mb-3">
                                Email Address
                            </label>
                            <input id="email" 
                                   type="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   required 
                                   autofocus 
                                   autocomplete="username"
                                   class="w-full px-5 py-4 text-base bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-300 focus:border-gray-400 transition-all duration-200 outline-none shadow-sm hover:shadow-md"
                                   placeholder="Enter your email address">
                            @error('email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Password -->
                        <div x-data="{ showPassword: false }">
                            <label for="password" class="block text-base font-semibold text-gray-700 mb-3">
                                Password
                            </label>
                            <div class="relative flex items-center">
                                <input id="password"
                                       :type="showPassword ? 'text' : 'password'"
                                       name="password"
                                       required
                                       autocomplete="current-password"
                                       class="w-full px-5 py-4 pr-12 text-base bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-300 focus:border-gray-400 transition-all duration-200 outline-none shadow-sm hover:shadow-md"
                                       placeholder="Enter your password">
                                <button type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 rounded-md text-gray-500 bg-transparent hover:text-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-300 focus-visible:ring-offset-0"
                                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                        :aria-pressed="showPassword">
                                    <span class="relative block w-5 h-5" aria-hidden="true">
                                        <svg x-show="!showPassword" class="absolute inset-0 w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <svg x-show="showPassword" x-cloak class="absolute inset-0 w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                        </svg>
                                    </span>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Remember Me -->
                        <div class="flex items-center pt-2">
                            <label for="remember_me" class="flex items-center cursor-pointer">
                                <input id="remember_me" 
                                       type="checkbox" 
                                       name="remember"
                                       class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-gray-300 focus:ring-2 cursor-pointer">
                                <span class="ml-3 text-base text-gray-700 font-medium">Remember me</span>
                            </label>
                        </div>
                        
                        <!-- Sign In Button -->
                        <button type="submit" 
                                class="w-full bg-[#0A4DFF] text-white font-semibold text-lg py-4 px-4 rounded-lg hover:bg-[#0839CC] hover:shadow-lg transition-all duration-200 shadow-md transform hover:-translate-y-0.5 mt-8">
                            Sign In
                        </button>
                        
                        <!-- Back to Homepage Link -->
                        <div class="pt-6 text-center">
                            <a href="{{ route('landing') }}" 
                               class="text-base text-gray-600 hover:text-gray-800 hover:underline transition-colors">
                                Back to Homepage
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
