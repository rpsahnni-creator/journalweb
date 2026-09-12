@if (session('status'))
    <x-alert type="success">{{ session('status') }}</x-alert>
@endif

@if (session('error'))
    <x-alert type="error">{{ session('error') }}</x-alert>
@endif

@if ($errors->any())
    <x-alert type="error">Please correct the highlighted fields.</x-alert>
@endif
