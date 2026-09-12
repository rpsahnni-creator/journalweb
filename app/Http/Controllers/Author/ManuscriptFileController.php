<?php

namespace App\Http\Controllers\Author;

use App\Enums\ArticleFileType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Author\StoreManuscriptFileRequest;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Support\Auditor;
use App\Support\ManuscriptFileStore;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManuscriptFileController extends Controller
{
    public function store(StoreManuscriptFileRequest $request, Article $manuscript, ManuscriptFileStore $files): RedirectResponse
    {
        $this->authorize('viewAsAuthor', $manuscript);
        $type = ArticleFileType::from($request->string('type')->toString());
        $file = $files->store($manuscript, $request->file('file'), $type, $request->user());

        Auditor::log('uploaded', $file, null, [
            'type' => $file->type->value,
            'original_filename' => $file->original_filename,
            'size_bytes' => $file->size_bytes,
        ], $manuscript->journal_id);

        return back()->with('status', $type->label().' uploaded.');
    }

    public function download(Article $manuscript, ArticleFile $file, ManuscriptFileStore $files): StreamedResponse
    {
        abort_unless($file->article_id === $manuscript->id, 404);
        $this->authorize('viewAsAuthor', $manuscript);
        $this->authorize('download', $file);

        return $files->download($file);
    }

    public function destroy(Article $manuscript, ArticleFile $file, ManuscriptFileStore $files): RedirectResponse
    {
        abort_unless($file->article_id === $manuscript->id, 404);
        $this->authorize('viewAsAuthor', $manuscript);
        $this->authorize('delete', $file);

        $old = $file->only(['type', 'original_filename']);
        $files->delete($file);

        Auditor::log('deleted', $file, $old, null, $manuscript->journal_id);

        return back()->with('status', 'File removed.');
    }
}
