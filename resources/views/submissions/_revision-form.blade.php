@php
    $maxKilobytes = (int) config('submissions.manuscript.max_kilobytes');
@endphp

<form
    method="POST"
    action="{{ route('submissions.revisions.store', $submission) }}"
    enctype="multipart/form-data"
    class="mt-4 space-y-4"
>
    @csrf
    <x-form.file
        name="manuscript"
        label="Revised manuscript"
        hint="PDF or DOCX only, maximum {{ number_format($maxKilobytes / 1024, 0) }} MB. Uploading starts a new review round."
        accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
        required
    />
    <x-form.button :full="false">Upload Revised Manuscript</x-form.button>
</form>
