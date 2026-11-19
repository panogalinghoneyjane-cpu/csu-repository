<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Recommendation;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Faculty RECOMMEND academic resources. They do not upload files.
 * The librarian reviews each recommendation and, if approved, acquires and uploads the resource.
 */
class RecommendationController extends Controller
{
            public function index(Request $request)
    {
        // Optional filter from the dashboard boxes / tabs, e.g. ?status=needs_revision
        $status = array_key_exists($request->query('status'), Recommendation::STATUS_LABELS)
            ? $request->query('status') : null;

        $recommendations = $request->user()->recommendations()
            ->with(['subject.program', 'resource'])
            ->when($status, fn ($q) => $status === 'pending'
                ? $q->whereIn('status', ['pending', 'needs_revision'])   // Pending tab also shows Returned for Revision
                : $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('faculty.recommendations.index', compact('recommendations', 'status'));
    }   
    public function create()
    {
        return view('faculty.recommendations.form', [
            'recommendation' => new Recommendation,
            'colleges' => $this->collegeTree(),
            'types' => Resource::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['faculty_id'] = $request->user()->id;
        $data['status'] = 'pending';

        Recommendation::create($data);

        return redirect()->route('faculty.recommendations.index')
            ->with('success', 'Recommendation submitted. The librarian will review it.');
    }

    /** Faculty can only edit a recommendation that is still pending or was returned for revision. */
    public function edit(Request $request, Recommendation $recommendation)
    {
        $this->authorizeOwner($request, $recommendation);

        return view('faculty.recommendations.form', [
            'recommendation' => $recommendation,
            'colleges' => $this->collegeTree(),
            'types' => Resource::TYPES,
        ]);
    }

    public function update(Request $request, Recommendation $recommendation)
    {
        $this->authorizeOwner($request, $recommendation);

        $data = $this->validated($request);
        $data['status'] = 'pending'; // resubmitted -> back to the librarian's queue

                 // A returned recommendation needs a comment about what was revised
        $request->validate([
            'revision_note' => [$recommendation->status === 'needs_revision' ? 'required' : 'nullable', 'string', 'max:1000'],
        ], [
            'revision_note.required' => 'Please write a comment about what you revised.',
        ]);
        $recommendation->revisionNote = $request->revision_note;

        $recommendation->update($data);

        return redirect()->route('faculty.recommendations.index')
            ->with('success', 'Recommendation updated and resubmitted for review.');
    }

        /** Faculty deletes their own recommendation (only if not yet approved or added). */
    public function destroy(Request $request, Recommendation $recommendation)
    {
        abort_unless($recommendation->faculty_id === $request->user()->id, 403, 'You can only delete your own recommendations.');
        abort_unless(in_array($recommendation->status, ['pending', 'needs_revision', 'rejected']), 403,
            'Approved recommendations and resources already added cannot be deleted.');

        $recommendation->delete();

        return redirect()->route('faculty.recommendations.index')->with('success', 'Recommendation deleted.');
    } 

    private function validated(Request $request): array
    {
        return $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'authors' => ['required', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'edition' => ['nullable', 'string', 'max:50'],
            'publication_year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'resource_type' => ['required', Rule::in(Resource::TYPES)],
            'source_link' => ['nullable', 'url', 'max:500'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
    }

    private function authorizeOwner(Request $request, Recommendation $recommendation): void
    {
        abort_unless($recommendation->faculty_id === $request->user()->id, 403);
        abort_unless(in_array($recommendation->status, ['pending', 'needs_revision']), 403,
            'This recommendation has already been reviewed and can no longer be edited.');
    }

    private function collegeTree()
    {
        return College::with('programs.subjects')->orderBy('code')->get();
    }
}
