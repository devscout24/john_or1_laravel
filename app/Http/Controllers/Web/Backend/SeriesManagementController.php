<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SeriesManagementController extends Controller
{
    public function index()
    {
        $series = Content::query()
            ->where('type', 'series')
            ->withCount('episodes')
            ->latest()
            ->get();

        return view('backend.layouts.series.index', compact('series'));
    }

    public function create()
    {
        return view('backend.layouts.series.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('series.create')
                ->with('error', $validator->errors()->first())
                ->withInput();
        }

        $thumbnail = $this->uploadImageToPublic($request->file('thumbnail'), null, 'uploads/content/thumbnail');
        $banner = $this->uploadImageToPublic($request->file('banner'), null, 'uploads/content/banner');

        $content = Content::create([
            'title' => $request->title,
            'description' => $request->description,
            'type' => 'series',
            'thumbnail' => $thumbnail,
            'banner' => $banner,
            'access_type' => 'free',
            'coins_required' => 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Auto-add to "new_releases" section
        $newReleasesSection = Section::where('slug', 'new_releases')->first();
        if ($newReleasesSection) {
            $content->sections()->attach($newReleasesSection->id, ['order' => 0]);
        }

        return redirect()
            ->route('series.index')
            ->with('success', 'Series created successfully.');
    }

    public function edit(Content $content)
    {
        $this->ensureSeries($content);

        $content->load(['episodes' => function ($query) {
            $query->orderBy('episode_number')->orderByDesc('id');
        }]);

        return view('backend.layouts.series.edit', compact('content'));
    }

    public function update(Request $request, Content $content)
    {
        $this->ensureSeries($content);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('series.edit', $content->id)
                ->with('error', $validator->errors()->first())
                ->withInput();
        }

        $thumbnail = $this->uploadImageToPublic($request->file('thumbnail'), $content->thumbnail, 'uploads/content/thumbnail');
        $banner = $this->uploadImageToPublic($request->file('banner'), $content->banner, 'uploads/content/banner');

        $content->update([
            'title' => $request->title,
            'description' => $request->description,
            'thumbnail' => $thumbnail,
            'banner' => $banner,
            'access_type' => 'free',
            'coins_required' => 0,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()
            ->route('series.edit', $content->id)
            ->with('success', 'Series updated successfully.');
    }

    public function toggleStatus(Request $request, Content $content)
    {
        $this->ensureSeries($content);

        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);

        $content->is_active = (bool) $validated['status'];
        $content->save();

        return back()->with('success', 'Series status updated successfully.');
    }

    public function destroy(Request $request, Content $content)
    {
        $this->ensureSeries($content);

        $validated = $request->validate([
            'current_password' => 'required|string',
        ]);

        if (!Hash::check($validated['current_password'], Auth::user()->password)) {
            return back()->with('error', 'Current password is incorrect. Series was not deleted.');
        }

        $this->deleteIfExists($content->thumbnail);
        $this->deleteIfExists($content->banner);

        $content->delete();

        return redirect()
            ->route('series.index')
            ->with('success', 'Series deleted successfully.');
    }

    private function ensureSeries(Content $content): void
    {
        abort_if($content->type !== 'series', 404);
    }

    private function uploadImageToPublic($file, ?string $oldPath, string $folder): ?string
    {
        if (!$file) {
            return $oldPath;
        }

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
}
