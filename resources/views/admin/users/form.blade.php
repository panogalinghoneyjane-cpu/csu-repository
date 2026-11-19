@extends('layouts.app')
@section('title', $user->exists ? 'Edit Account' : 'Add Account')

@section('content')
@php $isSelf = $user->exists && $user->id === auth()->id(); @endphp

<h1 class="text-2xl font-bold mb-1">{{ $user->exists ? 'Edit Account' : 'Add Account' }}</h1>
<p class="text-sm text-gray-500 mb-6">{{ $user->exists ? 'Update the details of '.$user->name.'.' : 'Create an account for a librarian, faculty member or student.' }}</p>

<form method="POST" class="bg-white rounded-lg border p-6 space-y-5 max-w-3xl"
      action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
    @csrf
    @if ($user->exists) @method('PUT') @endif

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Full Name <span class="text-red-600">*</span></label>
            <input name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">ID Number <span class="text-red-600">*</span></label>
            <input name="id_number" value="{{ old('id_number', $user->id_number) }}" required maxlength="50" placeholder="e.g. 2022-00123 or FAC-0002" class="w-full border rounded px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">Used to log in.</p>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Email <span class="text-red-600">*</span></label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" placeholder="name@csu.edu.ph" class="w-full border rounded px-3 py-2">
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Role <span class="text-red-600">*</span></label>
            <select name="role" id="role" class="w-full border rounded px-3 py-2" @disabled($isSelf) onchange="toggleCollege()">
                @foreach ($roles as $key => $label)
                    <option value="{{ $key }}" @selected(old('role', $user->role) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @if ($isSelf)
                <input type="hidden" name="role" value="admin">
                <p class="text-xs text-gray-500 mt-1">You cannot change your own role.</p>
            @endif
        </div>
    </div>

    {{-- College and Program (students and faculty only) --}}
    <div id="college-field" class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">College <span class="text-red-600">*</span></label>
            <select name="college_id" id="college_id" class="w-full border rounded px-3 py-2" onchange="filterPrograms(true)">
                <option value="">— Choose college —</option>
                @foreach ($colleges as $c)
                    <option value="{{ $c->id }}" @selected(old('college_id', $user->college_id) == $c->id)>{{ $c->code }} – {{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Program <span class="text-red-600">*</span></label>
            <select name="program_id" id="program_id" class="w-full border rounded px-3 py-2">
                <option value="">— Choose program —</option>
                @foreach ($programs as $p)
                    <option value="{{ $p->id }}" data-college="{{ $p->college_id }}" @selected(old('program_id', $user->program_id) == $p->id)>{{ $p->code }} – {{ $p->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">Only programs of the chosen college are listed.</p>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Password @unless ($user->exists)<span class="text-red-600">*</span>@endunless</label>
            <input type="password" name="password" @required(! $user->exists) autocomplete="new-password" class="w-full border rounded px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">{{ $user->exists ? 'Leave blank to keep the current password.' : 'At least 8 characters.' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" autocomplete="new-password" class="w-full border rounded px-3 py-2">
        </div>
    </div>

    @if ($isSelf)
        <input type="hidden" name="is_active" value="1">
    @else
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>
            Active (uncheck to block this account from logging in)
        </label>
    @endif

    <div class="flex gap-3">
        <button class="bg-csu-700 text-white px-5 py-2 rounded hover:bg-csu-800">{{ $user->exists ? 'Save Changes' : 'Create Account' }}</button>
        <a href="{{ route('admin.users.index') }}" class="px-5 py-2 rounded border hover:bg-gray-50">Cancel</a>
    </div>
</form>

<script>
    // Admins and librarians have no college or program
    function toggleCollege() {
        const role = document.getElementById('role').value;
        document.getElementById('college-field').classList.toggle('hidden', role === 'admin' || role === 'librarian');
    }

    // Show only the programs that belong to the chosen college
    function filterPrograms(clear) {
        const college = document.getElementById('college_id').value;
        const select = document.getElementById('program_id');
        [...select.options].forEach(o => {
            if (!o.value) return;
            const show = o.dataset.college === college;
            o.hidden = !show;
            o.disabled = !show;
        });
        if (clear && select.selectedOptions[0]?.disabled) select.value = '';
    }

    toggleCollege();
    filterPrograms(false);
</script>
@endsection
