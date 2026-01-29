<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $usersQuery = User::query()
            ->select('id', 'name', 'email', 'role', 'created_at')
            ->orderByDesc('created_at');

        if ($search !== '') {
            $usersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $usersQuery->paginate(10)->withQueryString(),
            'filters' => [
                'search' => $search,
            ],
            'roleLabels' => User::roleLabels(),
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->only(['id', 'name', 'email', 'role', 'created_at']),
            'roles' => User::roles(),
            'roleLabels' => User::roleLabels(),
        ]);
    }

    public function update(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $role = $request->validated('role');

        if ($user->is($request->user()) && $role !== User::ROLE_ADMIN) {
            $adminCount = User::query()->where('role', User::ROLE_ADMIN)->count();

            if ($adminCount <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'You are the only admin and cannot remove your own admin access.',
                ]);
            }
        }

        $user->update([
            'role' => $role,
        ]);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('success', 'User role updated.');
    }
}
