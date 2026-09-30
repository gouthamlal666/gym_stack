<?php

namespace App\Http\Controllers;

use App\Models\MemberDocument;
use App\Models\ProgressPhoto;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/** Streams private files after an authorisation check — they are never publicly addressable. */
class FileController extends Controller
{
    public function progressPhoto(ProgressPhoto $photo)
    {
        Gate::authorize('view-progress-photos', $photo->member);

        return Storage::disk('local')->response($photo->path, headers: ['Cache-Control' => 'private, max-age=3600']);
    }

    public function memberDocument(MemberDocument $document)
    {
        Gate::authorize('members.view');

        return Storage::disk('local')->download($document->file_path, $document->title.'.'.pathinfo($document->file_path, PATHINFO_EXTENSION));
    }
}
