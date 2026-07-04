<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\MeResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): MeResource
    {
        $data = $request->validated();
        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        if (! Auth::guard('web')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password tidak valid.'],
            ]);
        }

        $request->session()->regenerate();

        $user = $this->loadUser($request->user());
        $hasRequestedAccess = $data['accountType'] === 'organizer'
            ? $user->organizers->isNotEmpty()
            : $user->volunteerProfile !== null;

        if (! $hasRequestedAccess) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => [$data['accountType'] === 'organizer'
                    ? 'Akun ini tidak terdaftar sebagai organizer.'
                    : 'Akun ini tidak terdaftar sebagai relawan.'],
            ]);
        }

        return new MeResource($user);
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function me(Request $request): MeResource
    {
        return new MeResource($this->loadUser($request->user()));
    }

    private function loadUser(User $user): User
    {
        return $user->load(['volunteerProfile', 'organizers']);
    }
}
