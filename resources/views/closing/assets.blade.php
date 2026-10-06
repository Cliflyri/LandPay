@once
<link rel="stylesheet" href="{{ asset('css/closing.css') }}?v={{ filemtime(public_path('css/closing.css')) }}">
<script src="{{ asset('js/closing.js') }}?v={{ filemtime(public_path('js/closing.js')) }}" defer></script>
@endonce
