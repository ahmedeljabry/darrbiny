<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;

class MediaController extends BaseController
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required','file','max:10240','mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime']
        ]);
        $disk = config('filesystems.default', 'public');
        $path = $request->file('file')->store('uploads', $disk);
        $upload = Upload::create([
            'disk' => $disk,
            'path' => $path,
            'mime' => $request->file('file')->getMimeType(),
            'size' => $request->file('file')->getSize(),
        ]);
        return back()->with('status', 'Uploaded')->with('upload_id', $upload->id);
    }

    public function destroy(string $id)
    {
        $u = Upload::findOrFail($id);
        Storage::disk($u->disk)->delete($u->path);
        $u->delete();
        return back()->with('status', 'Deleted');
    }
}

