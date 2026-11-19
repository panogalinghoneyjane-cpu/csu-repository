<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Admin only: create, update and delete user accounts. */
class UserController extends Controller
{
    public const ROLES = ['admin' => 'Admin', 'librarian' => 'Librarian', 'faculty' => 'Faculty', 'student' => 'Student'];

    public function index(Request $request)
    {
        $users = User::with(['college', 'program'])
            ->when($request->role, fn ($q, $r) => $q->where('role', $r))
            ->when($request->q, fn ($q, $t) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%$t%")
                ->orWhere('id_number', 'like', "%$t%")
                ->orWhere('email', 'like', "%$t%")))
            ->orderByRaw("FIELD(role, 'admin', 'librarian', 'faculty', 'student')")
            ->orderBy('name')
            ->paginate(20)->withQueryString();

        $counts = User::selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total', 'role');

        return view('admin.users.index', [
            'users' => $users,
            'counts' => $counts,
            'roles' => self::ROLES,
        ]);
    }

    public function create()
    {
        return $this->form(new User(['role' => 'student', 'is_active' => true]));
    }

    public function store(Request $request)
    {
        User::create($this->validated($request));

        return redirect()->route('admin.users.index')->with('success', 'Account created.');
    }

    public function edit(User $user)
    {
        return $this->form($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        // You cannot remove your own admin access or deactivate yourself
        if ($user->id === $request->user()->id) {
            $data['role'] = 'admin';
            $data['is_active'] = true;
        }

        if (empty($data['password'])) {
            unset($data['password']);   // keep the old password
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', $user->name.'\'s account was updated.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['user' => 'You cannot delete the last admin account.']);
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', $name.'\'s account was deleted.');
    }

    private function form(User $user)
    {
        return view('admin.users.form', [
            'user' => $user,
            'roles' => self::ROLES,
            'colleges' => College::orderBy('code')->get(),
            'programs' => Program::orderBy('code')->get(['id', 'college_id', 'code', 'name']),
        ]);
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'max:50', Rule::unique('users', 'id_number')->ignore($user?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            // Students and faculty must belong to a college and a program
            'college_id' => [Rule::requiredIf(in_array($request->role, ['faculty', 'student'])), 'nullable', 'exists:colleges,id'],
            'program_id' => [Rule::requiredIf(in_array($request->role, ['faculty', 'student'])), 'nullable',
                Rule::exists('programs', 'id')->where('college_id', $request->college_id)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ], [
            'id_number.unique' => 'This ID number is already used by another account.',
            'email.unique' => 'This email is already used by another account.',
            'college_id.required' => 'Please choose the college of this student/faculty.',
            'program_id.required' => 'Please choose the program of this student/faculty.',
            'program_id.exists' => 'The program must belong to the chosen college.',
        ]);

        $data['id_number'] = strtoupper(trim($data['id_number']));
        $data['is_active'] = $request->boolean('is_active');

        // Admins and librarians do not belong to a college or program
        if (in_array($data['role'], ['admin', 'librarian'])) {
            $data['college_id'] = null;
            $data['program_id'] = null;
        }

        return $data;
    }
}
