<?php

namespace App\Http\Controllers\Faculty;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\CourseClass;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Faculty: create and manage their classes, and choose which repository resources go in each. */
class ClassController extends Controller
{
    public function create(Request $request)
    {
        return view('faculty.classes.form', [
            'class' => new CourseClass([
                'school_year' => now()->month >= 8 ? now()->year.'-'.(now()->year + 1) : (now()->year - 1).'-'.now()->year,
                'is_open' => true,
            ]),
            'colleges' => College::with('programs.subjects')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($duplicate = $this->findDuplicate($request, $data)) {
            return $this->duplicateError($duplicate);
        }
        $data['faculty_id'] = $request->user()->id;
        $data['is_open'] = true;

        $class = CourseClass::create($data);

        return redirect()->route('classes.show', $class)
            ->with('success', 'Class created. Add resources below, then share the class link with your students.');
    }

    public function edit(Request $request, CourseClass $courseClass)
    {
        $this->authorizeOwner($request, $courseClass);

        return view('faculty.classes.form', [
            'class' => $courseClass,
            'colleges' => College::with('programs.subjects')->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, CourseClass $courseClass)
    {
        $this->authorizeOwner($request, $courseClass);

        $data = $this->validated($request);
        if ($duplicate = $this->findDuplicate($request, $data, $courseClass)) {
            return $this->duplicateError($duplicate);
        }
        $data['is_open'] = $request->boolean('is_open');
        $courseClass->update($data);

        return redirect()->route('classes.show', $courseClass)->with('success', 'Class updated.');
    }

    public function archive(Request $request, CourseClass $courseClass)
{
    $this->authorizeOwner($request, $courseClass);

    $courseClass->archived_at = now();
    $courseClass->save();

    return redirect()->route('classes.index')
        ->with('success', 'Class archived successfully.');
}

public function restore(Request $request, CourseClass $courseClass)
{
    $this->authorizeOwner($request, $courseClass);

    $courseClass->archived_at = null;
    $courseClass->save();

    return redirect()->route('classes.index')
        ->with('success', 'Class restored successfully.');
}

    public function destroy(Request $request, CourseClass $courseClass)
    {
        $this->authorizeOwner($request, $courseClass);
        $courseClass->delete();

        return redirect()->route('classes.index')->with('success', 'Class deleted. (The resources stay in the repository.)');
    }

    public function addResource(Request $request, CourseClass $courseClass)
    {
        $this->authorizeOwner($request, $courseClass);

        $data = $request->validate([
            'resource_id' => ['required', Rule::exists('resources', 'id')->where('status', 'active')],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $changes = $courseClass->resources()->syncWithoutDetaching([$data['resource_id'] => ['note' => $data['note'] ?? null]]);

        // Tell the students of this class (only when the resource is new to the class, not when just the note changed)
        if (! empty($changes['attached'])) {
            $resource = Resource::find($data['resource_id']);
            \Illuminate\Support\Facades\Notification::send($courseClass->students, new \App\Notifications\SystemNotice(
                'class',
                'New resource in '.$courseClass->title.': “'.\Illuminate\Support\Str::limit($resource->title, 60).'”.',
                route('classes.show', $courseClass)
            ));
        }

        return back()->with('success', 'Resource added to '.$courseClass->title.'.');
    }

        /** The teacher removes a resource from the class (the resource stays in the repository). */
    public function removeResource(Request $request, CourseClass $courseClass, Resource $resource)
    {
        $this->authorizeOwner($request, $courseClass);
        $courseClass->resources()->detach($resource->id);

        return back()->with('success', 'Resource removed from the class.');
    }
    
    /** The teacher removes a student from the class. */
    public function removeStudent(Request $request, CourseClass $courseClass, User $student)
    {
        $this->authorizeOwner($request, $courseClass);
        $courseClass->students()->detach($student->id);

        return back()->with('success', $student->name.' was removed from the class.');
    }
    /** Find a class of this teacher with the same subject, section, semester, and school year. */
    private function findDuplicate(Request $request, array $data, ?CourseClass $except = null): ?CourseClass
    {
        return CourseClass::with('subject')
            ->where('faculty_id', $request->user()->id)
            ->where('subject_id', $data['subject_id'])
            ->whereRaw('LOWER(TRIM(section)) = ?', [mb_strtolower(trim($data['section']))])
            ->where('semester', $data['semester'] ?? null)
            ->where('school_year', $data['school_year'] ?? null)
            ->when($except, fn ($q) => $q->whereKeyNot($except->id))
            ->first();
    }

    /** Go back to the form, keep what was typed, and explain which class already exists. */
    private function duplicateError(CourseClass $duplicate)
    {
        $term = trim(($duplicate->semester ?? '').' '.($duplicate->school_year ?? ''));

        return back()->withInput()->withErrors([
            'section' => 'You already have this class: '.$duplicate->title.($term ? ' ('.$term.')' : '')
                .'. Open it from My Classes instead of creating a new one.',
        ]);
    }  
    
       private function validated(Request $request): array
    {
        return $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'section' => ['required', 'string', 'max:50'],
            'school_year' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', Rule::in(CourseClass::SEMESTERS)],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function authorizeOwner(Request $request, CourseClass $courseClass): void
    {
        abort_unless($courseClass->isOwnedBy($request->user()), 403, 'You can only manage your own classes.');
    }
}
