<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EpisodeManagementController extends Controller
{
    public function create(Content $content)
    {
        $this->ensureSeries($content);

        $nextEpisodeNumber = $this->nextEpisodeNumber($content);

        return view('backend.layouts.episodes.create', compact('content', 'nextEpisodeNumber'));
    }

    public function store(Request $request, Content $content)
    {
        $this->ensureSeries($content);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'access_type' => 'required|in:free,coins,ads',
            'video_type' => 'required|in:uploaded,external',
            'video_url' => 'nullable|string|max:2000',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm|max:307200',
            'duration' => 'nullable|integer|min:0',
            'coins_required' => 'nullable|integer|min:0',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->access_type === 'coins' && blank($request->coins_required)) {
                $validator->errors()->add('coins_required', 'Coins is required when access type is coins.');
            }

            if ($request->video_type === 'external' && blank($request->video_url)) {
                $validator->errors()->add('video_url', 'Video URL is required for external video type.');
            }

            if ($request->video_type === 'uploaded' && !$request->hasFile('video_file')) {
                $validator->errors()->add('video_file', 'Video file is required for uploaded video type.');
            }
        });

        if ($validator->fails()) {
            return redirect()
                ->route('episodes.create', $content->id)
                ->with('error', $validator->errors()->first())
                ->withInput();
        }

        $videoPath = null;
        $videoUrl = null;

        if ($request->video_type === 'uploaded' && $request->hasFile('video_file')) {
            $videoPath = $this->uploadVideoToPublic($request->file('video_file'), null);
            // Set video_url to asset URL for uploaded files
            $videoUrl = asset($videoPath);
        }

        if ($request->video_type === 'external') {
            $videoUrl = $request->video_url;
        }

        $nextEpisodeNumber = $this->nextEpisodeNumber($content);

        Episode::create([
            'content_id' => $content->id,
            'title' => $request->title,
            'episode_number' => $nextEpisodeNumber,
            'access_type' => $request->access_type,
            'video_type' => $request->video_type,
            'video_url' => $videoUrl,
            'storage_path' => $videoPath,
            'duration' => $request->filled('duration') ? (int) $request->duration : null,
            'coins_required' => $request->access_type === 'coins' ? (int) ($request->coins_required ?? 0) : 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('series.edit', $content->id)
            ->with('success', 'Episode created successfully.');
    }

    public function edit(Content $content, Episode $episode)
    {
        $this->ensureSeries($content);
        $this->ensureEpisodeBelongsToContent($content, $episode);

        return view('backend.layouts.episodes.edit', compact('content', 'episode'));
    }

    public function update(Request $request, Content $content, Episode $episode)
    {
        $this->ensureSeries($content);
        $this->ensureEpisodeBelongsToContent($content, $episode);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'access_type' => 'required|in:free,coins,ads',
            'video_type' => 'required|in:uploaded,external',
            'video_url' => 'nullable|string|max:2000',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm|max:307200',
            'duration' => 'nullable|integer|min:0',
            'coins_required' => 'nullable|integer|min:0',
        ]);

        $validator->after(function ($validator) use ($request, $episode) {
            if ($request->access_type === 'coins' && blank($request->coins_required)) {
                $validator->errors()->add('coins_required', 'Coins is required when access type is coins.');
            }

            if ($request->video_type === 'external' && blank($request->video_url)) {
                $validator->errors()->add('video_url', 'Video URL is required for external video type.');
            }

            if ($request->video_type === 'uploaded' && !$request->hasFile('video_file') && blank($episode->storage_path)) {
                $validator->errors()->add('video_file', 'Video file is required for uploaded video type.');
            }
        });

        if ($validator->fails()) {
            return redirect()
                ->route('episodes.edit', [$content->id, $episode->id])
                ->with('error', $validator->errors()->first())
                ->withInput();
        }

        $videoPath = $episode->storage_path;
        $videoUrl = $episode->video_url;

        if ($request->video_type === 'uploaded') {
            $videoUrl = null;

            if ($request->hasFile('video_file')) {
                $videoPath = $this->uploadVideoToPublic($request->file('video_file'), $episode->storage_path);
            }
        }

        if ($request->video_type === 'external') {
            $this->deleteIfExists($episode->storage_path);
            $videoPath = null;
            $videoUrl = $request->video_url;
        }

        $episode->update([
            'title' => $request->title,
            'access_type' => $request->access_type,
            'video_type' => $request->video_type,
            'video_url' => $videoUrl,
            'storage_path' => $videoPath,
            'duration' => $request->filled('duration') ? (int) $request->duration : null,
            'coins_required' => $request->access_type === 'coins' ? (int) ($request->coins_required ?? 0) : 0,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()
            ->route('series.edit', $content->id)
            ->with('success', 'Episode updated successfully.');
    }

    public function toggleStatus(Request $request, Content $content, Episode $episode)
    {
        $this->ensureSeries($content);
        $this->ensureEpisodeBelongsToContent($content, $episode);

        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);

        $episode->is_active = (bool) $validated['status'];
        $episode->save();

        return back()->with('success', 'Episode status updated successfully.');
    }

    public function destroy(Request $request, Content $content, Episode $episode)
    {
        $this->ensureSeries($content);
        $this->ensureEpisodeBelongsToContent($content, $episode);

        $validated = $request->validate([
            'current_password' => 'required|string',
        ]);

        if (!Hash::check($validated['current_password'], Auth::user()->password)) {
            return back()->with('error', 'Current password is incorrect. Episode was not deleted.');
        }

        $this->deleteIfExists($episode->storage_path);

        $episode->delete();

        return back()->with('success', 'Episode deleted successfully.');
    }

    private function ensureSeries(Content $content): void
    {
        abort_if($content->type !== 'series', 404);
    }

    private function ensureEpisodeBelongsToContent(Content $content, Episode $episode): void
    {
        abort_if((int) $episode->content_id !== (int) $content->id, 404);
    }

    private function uploadVideoToPublic($file, ?string $oldPath): ?string
    {
        $folder = 'uploads/episodes/videos';

        if (!File::exists(public_path($folder))) {
            File::makeDirectory(public_path($folder), 0755, true);
        }

        $this->deleteIfExists($oldPath);

        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $relativePath = $folder . '/' . $fileName;
        $file->move(public_path($folder), $fileName);

        return $relativePath;
    }

    private function deleteIfExists(?string $path): void
    {
        if ($path && File::exists(public_path($path))) {
            File::delete(public_path($path));
        }
    }

    private function nextEpisodeNumber(Content $content): int
    {
        $maxEpisodeNumber = (int) Episode::query()
            ->where('content_id', $content->id)
            ->max('episode_number');

        return $maxEpisodeNumber + 1;
    }
}
