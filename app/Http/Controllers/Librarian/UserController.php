<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/** Only registered users can access the repository; accounts are created by the librarian. */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('college')
            ->when($request->role, fn ($q, $r) => $q->where('role', $r))
            ->when($request->q, fn ($q, $t) => $q->where(fn ($w) => $w->where('name', 'like', "%$t%")->orWhere('id_number', 'like', "%$t%")))
            ->orderBy('role')->orderBy('name')
            ->paginate(20)->withQueryString();

        return view('librarian.users.index', [
            'users' => $users,
            'colleges' => College::orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'max:50', 'unique:users,id_number'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:librarian,faculty,student'],
            'college_id' => ['nullable', 'exists:colleges,id'],
            'password' => ['required', Password::min(8)],
        ]);

        User::create($data);

        return back()->with('success', 'Account created.');
    }

    public function toggle(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 403, 'You cannot deactivate your own account.');
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->name.' is now '.($user->is_active ? 'active' : 'deactivated').'.');
    }
}
