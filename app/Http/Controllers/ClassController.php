<?php

namespace App\Http\Controllers;

use App\Models\CourseClass;
use App\Models\Resource;
use Illuminate\Http\Request;

/**
 * "My Classes" pages shared by faculty and students:
 * the class list, the class page, joining with a link/code, saving and leaving a class.
 */
class ClassController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = match (true) {
            $user->isFaculty() => $user->taughtClasses(),
            $user->isStudent() => $user->savedClasses(),
            default => CourseClass::query(),           // librarian: all classes (read-only overview)
        };

        $classes = $query->with(['subject.program', 'faculty'])
            ->withCount(['resources', 'students'])
            ->latest('course_classes.created_at')
            ->get();

        return view('classes.index', compact('classes'));
    }

    public function show(Request $request, CourseClass $courseClass)
    {
        $user = $request->user();
        abort_unless($courseClass->canBeViewedBy($user), 403, 'Save this class first using the link or code from your teacher.');

        $courseClass->load(['subject.program.college', 'faculty']);
        $isOwner = $courseClass->isOwnedBy($user);

        $resources = $courseClass->resources()
            ->with('subject')
            ->when(! $isOwner, fn ($q) => $q->where('status', 'active'))
            ->orderByPivot('created_at', 'desc')
            ->get();

        // Teacher only: resources that can be added (search, or same-subject suggestions)
        $q = trim((string) $request->query('q', ''));
        $candidates = collect();
        $students = collect();
        if ($isOwner) {
            $candidates = Resource::active()->with('subject')
                ->whereNotIn('id', $resources->pluck('id'))
                ->when($q !== '', fn ($query) => $query->search($q),
                    fn ($query) => $query->where('subject_id', $courseClass->subject_id))
                ->orderByDesc('publication_year')
                ->limit(15)
                ->get();
            $students = $courseClass->students()->orderBy('name')->get();
        }

        return view('classes.show', [
            'class' => $courseClass,
            'resources' => $resources,
            'isOwner' => $isOwner,
            'candidates' => $candidates,
            'students' => $students,
            'q' => $q,
        ]);
    }

    /** The page students land on when they open the class link. */
    public function join(Request $request, string $code)
    {
        $class = CourseClass::where('code', CourseClass::normalizeCode($code))
            ->with(['subject.program.college', 'faculty'])->withCount('resources')->firstOrFail();

        if ($class->canBeViewedBy($request->user())) {
            return redirect()->route('classes.show', $class);
        }

        $preview = $class->resources()->where('status', 'active')->limit(5)->get();

        return view('classes.join', compact('class', 'preview'));
    }

    /** Student clicks "Save to My Classes". */
    public function save(Request $request, string $code)
    {
        $class = CourseClass::where('code', CourseClass::normalizeCode($code))->firstOrFail();
        $user = $request->user();

        abort_unless($user->isStudent(), 403, 'Only students can save a class to My Classes.');
        if (! $class->is_open) {
            return back()->with('error', 'This class is closed. Please ask your teacher.');
        }

        $class->students()->syncWithoutDetaching([$user->id]);

        return redirect()->route('classes.show', $class)
            ->with('success', 'Class saved to your My Classes. You can now open all its resources anytime.');
    }

    /** Student types a class code instead of opening the link. */
    public function joinByCode(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);
        $code = CourseClass::normalizeCode($data['code']);

        if (! CourseClass::where('code', $code)->exists()) {
            return back()->withErrors(['code' => 'No class found with that code. Please check it with your teacher.'])->withInput();
        }

        return redirect()->route('classes.join', $code);
    }

    public function leave(Request $request, CourseClass $courseClass)
    {
        $courseClass->students()->detach($request->user()->id);

        return redirect()->route('classes.index')->with('success', 'You left '.$courseClass->title.'.');
    }
}
