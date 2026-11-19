{{-- Courses with the Most Resources (shown in the Monitoring Resources tab) --}}
@php
    $topSubjects = $subjects->sortByDesc('resources')->values()->take(10);
    $max = max($topSubjects->max('resources') ?? 0, 1);
@endphp

<div class="bg-white rounded-lg border p-5 mb-6">
    <h3 class="font-semibold mb-1">Courses with the Most Resources</h3>
    <p class="text-xs text-gray-500 mb-3">Top 10 courses ranked by the number of resources in the repository.</p>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-gray-500">
                <tr>
                    <th class="py-2 w-12">Rank</th>
                    <th>Course</th>
                    <th>Program</th>
                    <th>College</th>
                    <th class="w-1/4">Resources</th>
                    <th class="text-right"></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($topSubjects as $i => $s)
                <tr class="border-t">
                    <td class="py-2 font-semibold text-gray-500">{{ $i + 1 }}</td>
                    <td>{{ $s['subject'] }}</td>
                    <td>{{ $s['program'] }}</td>
                    <td>{{ $s['college'] }}</td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="h-2 bg-gray-100 rounded flex-1">
                                <div class="h-2 bg-csu-700 rounded" style="width: {{ round($s['resources'] / $max * 100) }}%"></div>
                            </div>
                            <span class="font-semibold w-6 text-right">{{ $s['resources'] }}</span>
                        </div>
                    </td>
                    <td class="text-right whitespace-nowrap pl-4">
                        <a href="{{ route('librarian.resources.index', ['subject' => $s['id']]) }}"
                           class="text-csu-700 font-medium hover:underline">View the resources of this course →</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-3 text-gray-500">No courses yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>