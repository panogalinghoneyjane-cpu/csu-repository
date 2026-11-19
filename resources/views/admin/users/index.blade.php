@extends('layouts.app')
@section('title', 'User Accounts')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">User Accounts</h1>
        <p class="text-sm text-gray-500">Create, update and delete the accounts of librarians, faculty and students.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="bg-csu-700 text-white px-4 py-2 rounded hover:bg-csu-800">+ Add Account</a>
</div>

{{-- Count per role (click to filter) --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    @foreach ($roles as $key => $label)
        <a href="{{ route('admin.users.index', ['role' => $key]) }}"
           class="bg-white rounded-lg border p-4 hover:border-csu-700 {{ request('role') === $key ? 'border-csu-700 ring-1 ring-csu-700' : '' }}">
            <p class="text-sm text-gray-500">{{ $label }}{{ $key === 'faculty' ? '' : 's' }}</p>
            <p class="text-2xl font-bold text-csu-800">{{ $counts[$key] ?? 0 }}</p>
        </a>
    @endforeach
</div>

{{-- Search and filter --}}
<form class="flex flex-wrap gap-2 mb-4 text-sm">
    <input name="q" value="{{ request('q') }}" placeholder="Search name, ID number or email" class="border rounded px-3 py-2 flex-1 min-w-[14rem]">
    <select name="role" onchange="this.form.submit()" class="border rounded px-3 py-2">
        <option value="">All roles</option>
        @foreach ($roles as $key => $label)
            <option value="{{ $key }}" @selected(request('role') === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="bg-csu-700 text-white px-4 py-2 rounded hover:bg-csu-800">Search</button>
    @if (request('q') || request('role'))
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded border hover:bg-gray-50">Clear</a>
    @endif
</form>

<div class="bg-white rounded-lg border overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-600">
            <tr><th class="px-4 py-3">Name</th><th>ID Number</th><th>Email</th><th>Role</th><th>College / Program</th><th>Status</th><th class="pr-4"></th></tr>
        </thead>
        <tbody>
        @forelse ($users as $u)
            <tr class="border-t">
                <td class="px-4 py-2 font-medium">{{ $u->name }} @if ($u->id === auth()->id())<span class="text-xs text-gray-400">(you)</span>@endif</td>
                <td>{{ $u->id_number }}</td>
                <td class="text-gray-600">{{ $u->email }}</td>
                <td>{{ $roles[$u->role] ?? ucfirst($u->role) }}</td>
                <td>{{ $u->college->code ?? '—' }}{{ $u->program ? ' · '.$u->program->code : '' }}</td>
                <td>
                    <span class="text-xs px-2 py-1 rounded {{ $u->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                        {{ $u->is_active ? 'Active' : 'Deactivated' }}
                    </span>
                </td>
                <td class="pr-4 py-2 text-right whitespace-nowrap">
                    <a href="{{ route('admin.users.edit', $u) }}" class="text-csu-700 hover:underline">Edit</a>
                    @if ($u->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="inline ml-3"
                              data-confirm="{{ $u->name }}'s account will be deleted. {{ $u->isFaculty() ? 'Their recommendations and classes will also be removed. ' : '' }}This cannot be undone. To only block the account, use Edit and uncheck Active."
                              data-confirm-title="Delete this account?" data-confirm-button="Delete" data-confirm-danger
                              data-undo="Account deleted." data-undo-hide="tr">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline">Delete</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">No accounts found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
