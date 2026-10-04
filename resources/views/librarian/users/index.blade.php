@extends('layouts.app')
@section('title', 'User Accounts')

@section('content')
<h1 class="text-2xl font-bold mb-1">User Accounts</h1>
<p class="text-sm text-gray-500 mb-6">Only registered CSU users can access the repository. Guests cannot register themselves.</p>

<form method="POST" action="{{ route('librarian.users.store') }}" class="bg-white rounded-lg border p-4 mb-6 grid sm:grid-cols-3 lg:grid-cols-6 gap-3 text-sm">
    @csrf
    <input name="name" value="{{ old('name') }}" placeholder="Full name" required class="border rounded px-3 py-2">
    <input name="id_number" value="{{ old('id_number') }}" placeholder="ID number" required class="border rounded px-3 py-2">
    <input name="email" type="email" value="{{ old('email') }}" placeholder="Email" required class="border rounded px-3 py-2">
    <select name="role" class="border rounded px-3 py-2">
        <option value="student">Student</option><option value="faculty">Faculty</option><option value="librarian">Librarian</option>
    </select>
    <select name="college_id" class="border rounded px-3 py-2">
        <option value="">College (optional)</option>
        @foreach ($colleges as $c)<option value="{{ $c->id }}">{{ $c->code }}</option>@endforeach
    </select>
    <div class="flex gap-2">
        <input name="password" type="password" placeholder="Password (8+)" required class="border rounded px-3 py-2 w-full">
        <button class="bg-csu-700 text-white px-3 rounded hover:bg-csu-800">Add</button>
    </div>
</form>

<form class="flex flex-wrap gap-2 mb-4 text-sm">
    <input name="q" value="{{ request('q') }}" placeholder="Search name or ID" class="border rounded px-3 py-2">
    <select name="role" class="border rounded px-3 py-2">
        <option value="">All roles</option>
        @foreach (['librarian', 'faculty', 'student'] as $r)<option value="{{ $r }}" @selected(request('role') === $r)>{{ ucfirst($r) }}</option>@endforeach
    </select>
    <button class="bg-gray-800 text-white px-3 py-2 rounded">Filter</button>
</form>

<div class="bg-white rounded-lg border overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-600"><tr><th class="px-4 py-3">Name</th><th>ID Number</th><th>Email</th><th>Role</th><th>College</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($users as $u)
            <tr class="border-t">
                <td class="px-4 py-2">{{ $u->name }}</td>
                <td>{{ $u->id_number }}</td>
                <td>{{ $u->email }}</td>
                <td class="capitalize">{{ $u->role }}</td>
                <td>{{ $u->college->code ?? '—' }}</td>
                <td>{{ $u->is_active ? 'Active' : 'Deactivated' }}</td>
                <td class="pr-4 text-right">
                    @if ($u->id !== auth()->id())
                        <form method="POST" action="{{ route('librarian.users.toggle', $u) }}">
                            @csrf @method('PATCH')
                            <button class="text-xs {{ $u->is_active ? 'text-red-600' : 'text-csu-700' }} hover:underline">{{ $u->is_active ? 'Deactivate' : 'Activate' }}</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
