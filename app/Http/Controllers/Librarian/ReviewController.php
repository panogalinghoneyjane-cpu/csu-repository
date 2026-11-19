<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use App\Models\Recommendation;
use App\Models\Resource;
use Illuminate\Http\Request;

/** Workflow Approval Module: approve, reject, or return faculty recommendations for revision. */
class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');

        $recommendations = Recommendation::with(['faculty', 'subject.program.college'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = Recommendation::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('librarian.reviews.index', compact('recommendations', 'status', 'counts'));
    }

    public function show(Recommendation $recommendation)
    {
        $recommendation->load(['faculty', 'subject.program.college', 'reviewer', 'resource']);

        // Help the librarian spot duplicates already in the repository
        $similar = Resource::search($recommendation->title)->with('subject')->take(5)->get();

        return view('librarian.reviews.show', compact('recommendation', 'similar'));
    }

    public function decide(Request $request, Recommendation $recommendation)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected,needs_revision'],
            'librarian_remarks' => ['nullable', 'string', 'max:2000', 'required_if:decision,rejected,needs_revision'],
        ]);

        $recommendation->update([
            'status' => $data['decision'],
            'librarian_remarks' => $data['librarian_remarks'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($data['decision'] === 'approved') {
            // Go straight to the upload form, pre-filled from the recommendation
            return redirect()->route('librarian.resources.create', ['recommendation' => $recommendation->id])
                ->with('success', 'Recommendation approved. Upload the acquired resource below.');
        }

        return redirect()->route('librarian.reviews.index')->with('success', 'Decision saved. The faculty member can see it on their dashboard.');
    }
}
