<img src="{{ asset('images/psu_logo.png') }}" 
     alt="Pangasinan State University Logo" 
     {{ $attributes->merge(['class' => 'object-contain rounded-full']) }}
     style="object-fit: contain; {{ $attributes->get('style', '') }}">
