<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AdminUser;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:admin_users,email'],
            'password' => ['required', 'string', 'min:8'],
            'account_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $account = Account::create(['name' => $data['account_name'] ?? $data['name']."'s Team"]);

            return AdminUser::create([
                'account_id' => $account->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'owner',
            ]);
        });

        return $this->tokenResponse($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = AdminUser::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        AuditLog::record('auth.login', $user);

        return $this->tokenResponse($user);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->item(['user' => $request->user()->load('account')]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    private function tokenResponse(AdminUser $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken('dashboard')->plainTextToken;

        return $this->item([
            'token' => $token,
            'user' => $user->load('account'),
        ], $status);
    }
}
