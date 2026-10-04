<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Program;
use App\Models\Resource;
use App\Models\Subject;

class BrowseController extends Controller
{
    public function colleges()
    {
        return view('browse.colleges', [
            'colleges' => College::withCount('programs')->orderBy('code')->get(),
        ]);
    }

    public function college(College $college)
    {
        $college->load(['programs' => fn ($q) => $q->withCount('subjects')->orderBy('code')]);

        return view('browse.college', compact('college'));
    }

    public function program(Program $program)
    {
        $program->load(['college', 'subjects' => fn ($q) => $q->withCount('activeResources')->orderBy('year_level')->orderBy('code')]);

        return view('browse.program', compact('program'));
    }

    public function subject(Subject $subject)
    {
        $subject->load('program.college');

        $resources = Resource::active()
            ->where('subject_id', $subject->id)
            ->withCount('accessLogs')
            ->orderByDesc('publication_year')
            ->paginate(15);

        return view('browse.subject', compact('subject', 'resources'));
    }
}
