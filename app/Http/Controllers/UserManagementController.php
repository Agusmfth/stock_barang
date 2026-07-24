<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        return view('users.index', ['users' => $query->latest()->paginate(12)->withQueryString()]);
    }

    public function store(Request $request)
    {
        $roles = $request->user()->hasRole('Super Admin') ? ['Super Admin', 'Admin'] : ['Admin'];
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'role' => ['required', Rule::in($roles)],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data + ['is_active' => true]);

        return back()->with('success', 'User baru berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');
        abort_if(!$isSuperAdmin && $user->hasRole('Super Admin'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'role' => ['required', Rule::in($isSuperAdmin ? ['Super Admin', 'Admin'] : ['Admin'])],
            'is_active' => $isSuperAdmin ? 'required|boolean' : 'nullable|boolean',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($request->user()->is($user)) {
            $data['role'] = $user->role;
            $data['is_active'] = true;
        }

        if (!$isSuperAdmin) {
            $data['role'] = $user->role === 'Super Admin' ? 'Super Admin' : 'Admin';
            $data['is_active'] = $user->is_active;
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return back()->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->hasRole('Super Admin') || $request->user()->is($user), 403);

        $user->delete();

        return back()->with('success', 'Akun user berhasil dihapus.');
    }
}
