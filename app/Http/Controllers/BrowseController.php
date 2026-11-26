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
        // Sorting choices shown in the "Sort by" dropdown
        $sorts = [
            'year'      => ['Year Level', 'year_level', 'asc'],
            'code'      => ['Course Code (by number)', 'code', 'asc'],
            'name'      => ['Course Name (A-Z)', 'name', 'asc'],
            'name_desc' => ['Course Name (Z-A)', 'name', 'desc'],
            'most'      => ['Most Resources', 'active_resources_count', 'desc'],
        ];
        $sort = array_key_exists(request('sort'), $sorts) ? request('sort') : 'year';
        [, $column, $direction] = $sorts[$sort];

        $program->load(['college', 'subjects' => fn ($q) => $q->withCount('activeResources')
            ->orderBy($column, $direction)->orderBy('code')]);

        return view('browse.program', compact('program', 'sorts', 'sort'));
    }

    public function subject(Subject $subject)
    {
        $subject->load('program.college');

        // Sorting choices shown in the "Sort by" dropdown
        $sorts = [
            'year_new'   => ['Year (newest first)', 'publication_year', 'desc'],
            'year_old'   => ['Year (oldest first)', 'publication_year', 'asc'],
            'title'      => ['Title (A–Z)', 'title', 'asc'],
            'title_desc' => ['Title (Z–A)', 'title', 'desc'],
            'author'     => ['Author (A–Z)', 'authors', 'asc'],
        ];
        $sort = array_key_exists(request('sort'), $sorts) ? request('sort') : 'year_new';
        [, $column, $direction] = $sorts[$sort];

        // The librarian also sees hidden resources here (marked "Hidden" on the card); faculty and students do not
        $resources = Resource::query()
            ->unless(request()->user()->isLibrarian(), fn ($q) => $q->active())
            ->where('subject_id', $subject->id)
            ->withCount('accessLogs')
            ->orderBy($column, $direction)
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();   // keep the chosen sorting when moving to page 2, 3...

        return view('browse.subject', compact('subject', 'resources', 'sorts', 'sort'));
    }
}