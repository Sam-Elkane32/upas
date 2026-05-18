<x-guest-layout>
    <!-- Password Reset Form Content -->
    <div class="space-y-8">
        <!-- Header -->
        <div class="text-left">
            <h1 class="text-3xl font-bold text-blue-900 mb-2" style="color: #0046ad;">Forgot Password?</h1>
            <p class="text-gray-600 text-base">No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <!-- Password Reset Form -->
        <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
            @csrf

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                    Email
                </label>
                <input id="email" 
                       type="email" 
                       name="email" 
                       value="{{ old('email') }}" 
                       required 
                       autofocus
                       class="uaps-form-input w-full"
                       placeholder="Enter your email address">
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <button type="submit" class="uaps-btn-primary w-full">
                Email Password Reset Link
            </button>

            <!-- Back to Login Link -->
            <div class="text-center pt-6 border-t border-gray-200">
                <p class="text-gray-600 text-sm">
                    Remember your password? 
                    <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 font-semibold transition-colors hover:underline">
                        Back to Sign In
                    </a>
                </p>
            </div>
        </form>
    </div>
</x-guest-layout>
