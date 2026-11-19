{{-- Timeline of a recommendation: every step with its date and time, and every submitted version --}}
@php
    $steps = $recommendation->histories()->with('user')->oldest()->oldest('id')->get();
    $previous = null;   // the version submitted before, to mark what changed
@endphp

<div class="{{ $class ?? '' }}">
    <h2 class="font-semibold mb-3">History</h2>
    @if ($steps->isEmpty())
        <p class="text-sm text-gray-500">No history yet.</p>
    @else
        <ol class="relative border-l-2 border-gray-200 ml-2 space-y-4">
            @foreach ($steps as $step)
                <li class="ml-5">
                    <span class="absolute -left-[7px] mt-1.5 h-3 w-3 rounded-full ring-4 ring-white {{ $step->color() }}"></span>
                    <p class="text-sm font-semibold">{{ $step->label() }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $step->created_at->format('M d, Y · h:i A') }}
                        @if ($step->user) · by {{ $step->user->name }} @endif
                    </p>

                    @if ($step->remarks)
                        <p class="mt-1 text-sm text-gray-700 bg-gray-50 border rounded px-3 py-2">
                            <span class="block text-xs font-semibold text-gray-500">{{ $step->action === 'resubmitted' ? 'Revision comment' : 'Librarian remarks' }}</span>
                            “{{ $step->remarks }}”
                        </p>
                    @endif

                    {{-- What the faculty member submitted at this step (kept even after revising) --}}
                    @if ($step->details)
                        <details class="mt-1">
                            <summary class="text-xs text-csu-700 cursor-pointer">See what was submitted</summary>
                            <dl class="mt-2 text-xs bg-gray-50 border rounded px-3 py-2 space-y-1">
                                @foreach (\App\Models\RecommendationHistory::DETAIL_LABELS as $key => $name)
                                    @php
                                        $value = $step->details[$key] ?? null;
                                        $changed = $previous && ($previous[$key] ?? null) != $value;
                                    @endphp
                                    <div class="flex gap-2">
                                        <dt class="w-28 shrink-0 text-gray-500">{{ $name }}</dt>
                                        <dd class="min-w-0 break-words">
                                            @if ($key === 'source_link' && $value)
                                                <a href="{{ $value }}" target="_blank" rel="noopener" class="text-csu-700 underline break-all">{{ $value }}</a>
                                            @else
                                                {{ $value ?: '—' }}
                                            @endif
                                            @if ($changed)<span class="ml-1 px-1.5 rounded bg-yellow-100 text-yellow-800">changed</span>@endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        </details>
                        @php $previous = $step->details; @endphp
                    @endif
                </li>
            @endforeach
        </ol>
    @endif
</div>
