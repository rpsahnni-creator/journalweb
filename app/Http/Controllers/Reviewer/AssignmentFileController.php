<?php

namespace App\Http\Controllers\Reviewer;

use App\Enums\ArticleFileType;
use App\Http\Controllers\Controller;
use App\Models\ArticleFile;
use App\Models\ReviewerAssignment;
use App\Support\ManuscriptFileStore;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentFileController extends Controller
{
    public function download(ReviewerAssignment $assignment, ArticleFile $file, ManuscriptFileStore $files): StreamedResponse
    {
        abort_unless($file->article_id === $assignment->article_id, 404);
        abort_unless(in_array($file->type, [ArticleFileType::Manuscript, ArticleFileType::Supplementary], true), 404);
        $this->authorize('download', $assignment);
        $this->authorize('download', $file);

        return $files->download($file);
    }
}
