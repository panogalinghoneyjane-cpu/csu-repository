<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Recommendation;
use App\Models\Resource;
use App\Models\Subject;
use App\Services\PdfTextExtractor;
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
            ->when($request->subject, fn ($q, $s) => $q->where('subject_id', $s))
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
            $resource->external_url = $recommendation->source_link;   // faculty's link, if any (librarian can switch to PDF)
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

        if ($data['source_type'] === 'file') {
            // Saved in storage/app/private/resources - NOT publicly reachable
            $data['file_path'] = $request->file('file')->store('resources', 'local');
            $data['external_url'] = null;
            $data['content_text'] = $this->readText($data['file_path']);   // full-text search
        } else {
            $data['file_path'] = null;
            $data['content_text'] = null;
        }
        $data['uploaded_by'] = $request->user()->id;
        $data['keywords'] = Resource::autoKeywords($data['title'], Subject::find($data['subject_id']));
        unset($data['file'], $data['source_type']);

        $resource = Resource::create($data);

        if ($resource->recommendation_id) {
                    Recommendation::find($resource->recommendation_id)?->update(['status' => 'fulfilled']);
        }

        return redirect()->route('librarian.resources.index')
            ->with('success', 'Resource uploaded and classified.'.$this->textNote($resource));
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
        $data = $this->validated($request, false, $resource);

        if ($data['source_type'] === 'file') {
            if ($request->hasFile('file')) {
                $this->deleteFile($resource);
                $data['file_path'] = $request->file('file')->store('resources', 'local');
                $data['content_text'] = $this->readText($data['file_path']);   // re-read the new file
            }
            $data['external_url'] = null;
        } else {
            // Switched to an online link: remove the old PDF, if there was one
            $this->deleteFile($resource);
            $data['file_path'] = null;
            $data['content_text'] = null;
        }
        $data['keywords'] = Resource::autoKeywords($data['title'], Subject::find($data['subject_id']));
        unset($data['file'], $data['source_type']);

        $resource->update($data);

        return redirect()->route('librarian.resources.index')
            ->with('success', 'Resource updated.'.($request->hasFile('file') ? $this->textNote($resource) : ''));
    }

    public function destroy(Resource $resource)
    {
        $this->deleteFile($resource);
        $resource->delete();

        return redirect()->route('librarian.resources.index')->with('success', 'Resource deleted.');
    }

    private function validated(Request $request, bool $creating, ?Resource $resource = null): array
    {
        // A new PDF is required when creating a PDF resource, or when an existing LINK resource is switched to PDF
        $needsFile = $request->input('source_type') === 'file' && ($creating || ! $resource?->file_path);

        $data = $request->validate([
            'has_physical_copy' => ['nullable', 'boolean'],
            'call_number' => ['nullable', 'string', 'max:100'],
            'shelf_location' => ['nullable', 'string', 'max:150'],
            'source_type' => ['required', 'in:file,link'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'recommendation_id' => ['nullable', 'exists:recommendations,id'],
            'title' => ['required', 'string', 'max:255'],
            'authors' => ['required', 'string', 'max:255'],
            'publication_year' => ['required', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'resource_type' => ['required', Rule::in(Resource::TYPES)],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', 'in:active,archived'],
            'file' => [$needsFile ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:51200'], // 50 MB
            'external_url' => ['nullable', 'required_if:source_type,link', 'url:http,https', 'max:500'],
        ], [
            'file.required' => 'Please choose a PDF file to upload.',
            'external_url.required_if' => 'Please enter the link (URL) of the online resource.',
            'external_url.url' => 'Please enter a valid link that starts with http:// or https://.',
        ]);

        // Physical copy: when not checked, clear the call number and location
        $data['has_physical_copy'] = $request->boolean('has_physical_copy');
        if (! $data['has_physical_copy']) {
            $data['call_number'] = null;
            $data['shelf_location'] = null;
        }

        return $data;
    }

    private function readText(string $path): ?string
    {
        return app(PdfTextExtractor::class)->extract(Storage::disk('local')->path($path));
    }

    /** Tell the librarian when a PDF had no readable text (e.g. a scanned document). */
    private function textNote(Resource $resource): string
    {
        if (! $resource->file_path) {
            return '';
        }

        return $resource->content_text
            ? ' The text inside the PDF is now searchable.'
            : ' Note: no readable text was found in this PDF (it may be a scanned image), so only its details are searchable.';
    }

    private function deleteFile(Resource $resource): void
    {
        if ($resource->file_path) {
            Storage::disk('local')->delete($resource->file_path);
        }
    }
}
