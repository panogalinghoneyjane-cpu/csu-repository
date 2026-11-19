<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Program;
use App\Models\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Subject;
use App\Support\CollegeLogo;
use Illuminate\Http\Request;

/** Manage the classification structure: College -> Program -> Subject. */
class ClassificationController extends Controller
{
    public function index()
    {
        $colleges = College::with(['programs' => fn ($q) => $q->orderBy('code'),
            'programs.subjects' => fn ($q) => $q->withCount('resources')->orderBy('code')])
            ->orderBy('code')->get();

        return view('librarian.classifications.index', compact('colleges'));
    }

    public function store(Request $request, string $type)
    {
        if ($type === 'college') {
            $data = $request->validate([
                'code' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:colleges,code'],
                'name' => ['required', 'string', 'max:255'],
                'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],   // 4 MB
            ]);
            $college = College::create(['code' => strtoupper($data['code']), 'name' => $data['name']]);
            if ($request->hasFile('logo')) {
                CollegeLogo::save($college->code, $request->file('logo'));
            }
        } elseif ($type === 'program') {
            Program::create($request->validate([
                'college_id' => ['required', 'exists:colleges,id'],
                'code' => ['required', 'string', 'max:20'],
                'name' => ['required', 'string', 'max:255'],
            ]));
        } else {
            $data = $request->validate([
                'program_id' => ['required', 'exists:programs,id'],
                'code' => ['required', 'string', 'max:20'],
                'name' => ['required', 'string', 'max:255'],
                'year_level' => ['nullable', 'integer', 'between:1,5'],
            ]);
            $data['min_resources'] = Subject::MIN_RESOURCES;   // always 5, set by the system
            Subject::create($data);
        }

        return back()->with('success', ($type === 'subject' ? 'Course' : ucfirst($type)).' added.');
    }

    /** Upload or replace a college's logo. */
    public function logo(Request $request, College $college)
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ]);

        CollegeLogo::save($college->code, $request->file('logo'));

        return back()->with('success', 'Logo updated for '.$college->code.'.');
    }
    public function destroy(string $type, int $id)
    {
        $model = match ($type) {
            'college' => College::findOrFail($id),
            'program' => Program::findOrFail($id),
            'subject' => Subject::findOrFail($id),
        };

        // Every course under what is being deleted
        $subjectIds = match ($type) {
            'college' => Subject::whereIn('program_id', $model->programs()->pluck('id'))->pluck('id'),
            'program' => $model->subjects()->pluck('id'),
            'subject' => collect([$model->id]),
        };
        $resources = Resource::whereIn('subject_id', $subjectIds)->get();

        DB::transaction(function () use ($type, $model, $subjectIds, $resources) {
            // 1. Resources and their PDF files
            foreach ($resources as $resource) {
                if ($resource->file_path) {
                    Storage::disk('local')->delete($resource->file_path);
                }
            }
            Resource::whereIn('id', $resources->pluck('id'))->delete();

            // 2. Courses, 3. Programs
            Subject::whereIn('id', $subjectIds)->delete();
            if ($type === 'college') {
                $model->programs()->delete();
            }

            // 4. The college / program / course itself
            if ($type !== 'subject') {
                $model->delete();
            }
        });

        if ($type === 'college') {
            CollegeLogo::delete($model->code);
        }

        $label = ['college' => 'College', 'program' => 'Program', 'subject' => 'Course'][$type];

        return back()->with('success', $label.' "'.$model->code.'" and everything under it were deleted ('
            .$subjectIds->count().' course(s), '.$resources->count().' resource(s)).');
    }
}
