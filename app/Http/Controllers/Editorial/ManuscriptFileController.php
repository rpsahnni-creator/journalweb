<?php

namespace App\Http\Controllers\Editorial;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Support\ManuscriptFileStore;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManuscriptFileController extends Controller
{
    public function download(Article $manuscript, ArticleFile $file, ManuscriptFileStore $files): StreamedResponse
    {
        abort_unless($file->article_id === $manuscript->id, 404);
        $this->authorize('viewEditorial', $manuscript);
        $this->authorize('download', $file);

        return $files->download($file);
    }
}
