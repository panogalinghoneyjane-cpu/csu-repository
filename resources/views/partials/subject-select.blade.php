{{-- Usage: @include('partials.subject-select', ['colleges' => $colleges, 'selected' => $subjectId]) --}}
@php
    $tree = $colleges->map(fn ($c) => [
        'id' => $c->id, 'code' => $c->code,
        'programs' => $c->programs->map(fn ($p) => [
            'id' => $p->id, 'code' => $p->code,
            'subjects' => $p->subjects->map(fn ($s) => ['id' => $s->id, 'label' => $s->label])->values(),
        ])->values(),
    ])->values();
@endphp
<div class="grid sm:grid-cols-3 gap-3" id="subject-select">
    <div>
        <label class="block text-sm font-medium mb-1">College</label>
        <select id="ss-college" class="w-full border rounded px-3 py-2"></select>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Program</label>
        <select id="ss-program" class="w-full border rounded px-3 py-2"></select>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Subject <span class="text-red-600">*</span></label>
        <select id="ss-subject" name="subject_id" required class="w-full border rounded px-3 py-2"></select>
    </div>
</div>
<script>
(function () {
    const tree = @json($tree);
    const selected = {{ (int) ($selected ?? 0) }};
    const c = document.getElementById('ss-college'), p = document.getElementById('ss-program'), s = document.getElementById('ss-subject');
    const fill = (el, items, text, val) => { el.innerHTML = items.map(i => `<option value="${i.id}">${text(i)}</option>`).join(''); if (val) el.value = val; };

    let sc = tree[0]?.id, sp = null;
    tree.forEach(col => col.programs.forEach(pr => pr.subjects.forEach(sub => { if (sub.id === selected) { sc = col.id; sp = pr.id; } })));

    const onProgram = () => { const pr = tree.find(x => x.id == c.value)?.programs.find(x => x.id == p.value); fill(s, pr ? pr.subjects : [], i => i.label, selected); };
    const onCollege = (keep) => { const col = tree.find(x => x.id == c.value); fill(p, col ? col.programs : [], i => i.code, keep ? sp : null); onProgram(); };

    fill(c, tree, i => i.code, sc);
    onCollege(true);
    c.addEventListener('change', () => onCollege(false));
    p.addEventListener('change', onProgram);
})();
</script>
