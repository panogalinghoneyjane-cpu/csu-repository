<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Librarian posts announcements (e.g. book fair) shown on faculty and student dashboards. */
class AnnouncementController extends Controller
{
    /** Posters are saved in public/uploads/announcements */
    private const FOLDER = 'uploads/announcements';

    public function index()
    {
        return view('librarian.announcements.index', [
            'announcements' => Announcement::with('author')->orderByDesc('is_pinned')->latest()->paginate(15),
        ]);
    }

    public function create()
    {
        return view('librarian.announcements.form', ['announcement' => new Announcement(['audience' => 'all'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->saveImage($request);
        }

        Announcement::create($data + ['user_id' => $request->user()->id]);

        return redirect()->route('librarian.announcements.index')->with('success', 'Announcement posted.');
    }

    public function edit(Announcement $announcement)
    {
        return view('librarian.announcements.form', ['announcement' => $announcement]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $announcement->deleteImage();               // replace the old poster
            $data['image_path'] = $this->saveImage($request);
        } elseif ($request->boolean('remove_image')) {
            $announcement->deleteImage();
            $data['image_path'] = null;
        }

        $announcement->update($data);

        return redirect()->route('librarian.announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->deleteImage();
        $announcement->delete();

        return redirect()->route('librarian.announcements.index')->with('success', 'Announcement deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:3000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],   // 5 MB
            'audience' => ['required', Rule::in(array_keys(Announcement::AUDIENCES))],
            'event_date' => ['nullable', 'date'],
            'show_until' => ['nullable', 'date'],
        ], [
            'image.image' => 'The poster must be a picture (JPG, PNG, WEBP or GIF).',
            'image.max' => 'The poster must be 5 MB or smaller.',
        ]);

        unset($data['image']);
        $data['is_pinned'] = $request->boolean('is_pinned');

        return $data;
    }

    private function saveImage(Request $request): string
    {
        $file = $request->file('image');
        $name = now()->format('YmdHis').'-'.Str::random(6).'.'.strtolower($file->getClientOriginalExtension());
        $file->move(public_path(self::FOLDER), $name);

        return self::FOLDER.'/'.$name;
    }
}
