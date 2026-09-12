<?php

namespace App\Support;

use Illuminate\Validation\Rules\File;

class ManuscriptUpload
{
    /**
     * Validate PDF/DOCX uploads by sniffed MIME type, not the client filename extension alone.
     *
     * `mimes` guesses the type from file content; `mimetypes` checks the sniffed MIME;
     * `File::types()` applies the same Laravel sniff with an explicit extension allow-list.
     *
     * @return list<mixed>
     */
    public static function rules(): array
    {
        $config = config('submissions.manuscript');
        $extensions = ['pdf', 'docx'];

        return [
            'file',
            'mimes:'.implode(',', $extensions),
            'mimetypes:'.implode(',', $config['mimetypes']),
            File::types($extensions)
                ->extensions($extensions)
                ->max((int) $config['max_kilobytes']),
        ];
    }
}
