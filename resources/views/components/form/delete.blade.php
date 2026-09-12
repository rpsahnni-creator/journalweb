@props([
    'action',
    'title' => 'Delete this record?',
    'text' => 'This action cannot be undone.',
    'confirm' => 'Delete',
])

<form
    method="POST"
    action="{{ $action }}"
    {{ $attributes }}
    @submit.prevent="Swal.fire({
        title: @js($title),
        text: @js($text),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#b91c1c',
        confirmButtonText: @js($confirm)
    }).then((result) => { if (result.isConfirmed) $el.submit() })"
>
    @csrf
    @method('DELETE')
    <button type="submit" class="inline-flex items-center gap-1 text-sm font-medium text-rose-600 hover:text-rose-800 hover:underline transition-colors cursor-pointer">
        {{ $slot }}
    </button>

</form>
