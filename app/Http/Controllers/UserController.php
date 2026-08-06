<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(UserRole::class)],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id', Rule::unique('users', 'employee_id')],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => UserRole::from($data['role']),
            'employee_id' => $data['employee_id'] ?? null,
            'password' => $data['password'],
            'email_verified_at' => now(),
        ]);

        AuditLog::create($this->audit($request, 'created', $user->id, null, ['name' => $user->name, 'email' => $user->email, 'role' => $user->role->value]));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "User {$user->name} berhasil dibuat.",
        ]);

        return back();
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id', Rule::unique('users', 'employee_id')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => UserRole::from($data['role']),
            'employee_id' => $data['employee_id'] ?? null,
            'password' => ! empty($data['password']) ? $data['password'] : $user->password,
        ]);

        AuditLog::create($this->audit($request, 'updated', $user->id, $user->getOriginal(), ['name' => $user->name, 'email' => $user->email, 'role' => $user->role->value]));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "User {$user->name} diperbarui.",
        ]);

        return back();
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Anda tidak dapat menghapus akun sendiri.',
            ]);

            return back();
        }

        AuditLog::create($this->audit($request, 'deleted', $user->id, ['name' => $user->name, 'email' => $user->email], null));

        $user->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "User {$user->name} dihapus.",
        ]);

        return back();
    }

    private function audit(Request $request, string $action, int $id, ?array $before, ?array $after): array
    {
        return [
            'user_id' => $request->user()->id,
            'action' => "user.{$action}",
            'entity_type' => 'user',
            'entity_id' => $id,
            'before' => $before,
            'after' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }
}
