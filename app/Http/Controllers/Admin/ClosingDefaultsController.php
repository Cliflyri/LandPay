<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\VestingGuideService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClosingDefaultsController extends Controller
{
    public function update(Request $request)
    {
        $request->validate(['document' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240']]);
        $file = $request->file('document');
        $path = $file->store(rtrim(VestingGuideService::PREFIX, '/'), 'local');
        try {
            AppSetting::putMany(['closing_vesting_guide' => json_encode([
                'path' => $path, 'name' => basename($file->getClientOriginalName()),
                'uploaded_at' => now()->toIso8601String(), 'uploaded_by' => $request->user()->id,
            ], JSON_THROW_ON_ERROR)]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return redirect()->route('admin.settings.index', ['section' => 'closing'])
            ->with('success', 'Default vesting guide saved. Existing closings keep their assigned version.');
    }

    public function download(Request $request, VestingGuideService $guides)
    {
        $guide = $guides->current();
        abort_unless($guide && Storage::disk('local')->exists($guide['path']), 404);

        return Storage::disk('local')->download($guide['path'], $guide['name'], [
            'Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
