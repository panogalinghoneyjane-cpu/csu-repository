<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Program;
use App\Models\Subject;
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
            College::create($request->validate([
                'code' => ['required', 'string', 'max:20', 'unique:colleges,code'],
                'name' => ['required', 'string', 'max:255'],
            ]));
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
                'min_resources' => ['nullable', 'integer', 'min:1', 'max:100'],
            ]);
            $data['min_resources'] = $data['min_resources'] ?? 3;
            Subject::create($data);
        }

        return back()->with('success', ucfirst($type).' added.');
    }

    public function destroy(string $type, int $id)
    {
        $model = match ($type) {
            'college' => College::withCount('programs')->findOrFail($id),
            'program' => Program::withCount('subjects')->findOrFail($id),
            'subject' => Subject::withCount('resources')->findOrFail($id),
        };

        $children = $model->programs_count ?? $model->subjects_count ?? $model->resources_count ?? 0;
        if ($children > 0) {
            return back()->with('error', 'Cannot delete: this '.$type.' still has items under it. Remove those first.');
        }

        $model->delete();

        return back()->with('success', ucfirst($type).' deleted.');
    }
}
