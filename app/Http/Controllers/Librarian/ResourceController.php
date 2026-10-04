<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Recommendation;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Upload, classify, edit and archive repository resources (librarian only). */
class ResourceController extends Controller
{
    public function index(Request $request)
    {
        $resources = Resource::with('subject.program.college')
            ->withCount('accessLogs')
            ->when($request->q, fn ($q, $term) => $q->search($term))
            ->when($request->college, fn ($q, $c) => $q->whereHas('subject.program', fn ($p) => $p->where('college_id', $c)))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->boolean('outdated'), fn ($q) => $q->outdated())
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('librarian.resources.index', [
            'resources' => $resources,
            'colleges' => College::orderBy('code')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $recommendation = $request->recommendation
            ? Recommendation::where('status', 'approved')->find($request->recommendation)
            : null;

        $resource = new Resource;
        if ($recommendation) {
            $resource->fill($recommendation->only(['subject_id', 'title', 'authors', 'publication_year', 'resource_type']));
            $resource->recommendation_id = $recommendation->id;
        }

        return view('librarian.resources.form', [
            'resource' => $resource,
            'recommendation' => $recommendation,
            'colleges' => College::with('programs.subjects')->orderBy('code')->get(),
            'types' => Resource::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);

        // Saved in storage/app/private/resources - NOT publicly reachable
        $data['file_path'] = $request->file('file')->store('resources', 'local');
        $data['uploaded_by'] = $request->user()->id;
        unset($data['file']);

        $resource = Resource::create($data);

        if ($resource->recommendation_id) {
            Recommendation::whereKey($resource->recommendation_id)->update(['status' => 'fulfilled']);
        }

        return redirect()->route('librarian.resources.index')->with('success', 'Resource uploaded and classified.');
    }

    public function edit(Resource $resource)
    {
        return view('librarian.resources.form', [
            'resource' => $resource,
            'recommendation' => $resource->recommendation,
            'colleges' => College::with('programs.subjects')->orderBy('code')->get(),
            'types' => Resource::TYPES,
        ]);
    }

    public function update(Request $request, Resource $resource)
    {
        $data = $this->validated($request, false);

        if ($request->hasFile('file')) {
            Storage::disk('local')->delete($resource->file_path);
            $data['file_path'] = $request->file('file')->store('resources', 'local');
        }
        unset($data['file']);

        $resource->update($data);

        return redirect()->route('librarian.resources.index')->with('success', 'Resource updated.');
    }

    public function destroy(Resource $resource)
    {
        Storage::disk('local')->delete($resource->file_path);
        $resource->delete();

        return redirect()->route('librarian.resources.index')->with('success', 'Resource deleted.');
    }

    private function validated(Request $request, bool $fileRequired): array
    {
        return $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'recommendation_id' => ['nullable', 'exists:recommendations,id'],
            'title' => ['required', 'string', 'max:255'],
            'authors' => ['required', 'string', 'max:255'],
            'publication_year' => ['required', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'resource_type' => ['required', Rule::in(Resource::TYPES)],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:3000'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,archived'],
            'file' => [$fileRequired ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:51200'], // 50 MB
        ]);
    }
}
