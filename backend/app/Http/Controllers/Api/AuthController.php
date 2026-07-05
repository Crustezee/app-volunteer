<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\MeResource;
use App\Models\Organizer;
use App\Models\OrganizerMember;
use App\Models\User;
use App\Models\VolunteerProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): MeResource
    {
        $data = $request->validated();
        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        $this->ensureSessionAvailable($request);

        if (! Auth::guard('web')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password tidak valid.'],
            ]);
        }

        $request->session()->regenerate();

        $user = $this->loadUser($request->user());

        if (isset($data['accountType'])) {
            $hasRequestedAccess = match ($data['accountType']) {
                'admin' => $user->role === 'admin',
                'organizer' => $user->organizers->isNotEmpty(),
                default => $user->volunteerProfile !== null,
            };

            if (! $hasRequestedAccess) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => [match ($data['accountType']) {
                        'admin' => 'Akun ini tidak terdaftar sebagai admin.',
                        'organizer' => 'Akun ini tidak terdaftar sebagai organizer.',
                        default => 'Akun ini tidak terdaftar sebagai relawan.',
                    }],
                ]);
            }
        }

        return new MeResource($user);
    }

    public function register(RegisterRequest $request): MeResource
    {
        $data = $request->validated();
        $this->ensureSessionAvailable($request);

        $user = DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data['role'] === 'organizer' ? $data['organizationName'] : $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'status' => 'Active',
                'city' => $data['city'],
                'avatar_initials' => $this->initials($data['role'] === 'organizer' ? $data['organizationName'] : $data['name']),
                'email_verified_at' => now(),
            ]);

            if ($data['role'] === 'organizer') {
                $organizer = Organizer::query()->create([
                    'id' => $this->uniqueStringId(Organizer::class, 'org-', $data['organizationName']),
                    'name' => $data['organizationName'],
                    'type' => $data['organizationType'],
                    'city' => $data['city'],
                    'verified' => false,
                    'logo_initial' => $this->initials($data['organizationName']),
                    'rating' => 0,
                    'total_events' => 0,
                    'response_time' => 'Belum ada data',
                ]);

                OrganizerMember::query()->create([
                    'id' => $this->uniqueStringId(OrganizerMember::class, 'mem-', $organizer->id.'-'.$user->id),
                    'organizer_id' => $organizer->id,
                    'user_id' => $user->id,
                    'role' => 'Owner',
                ]);
            } else {
                VolunteerProfile::query()->create([
                    'id' => $this->uniqueStringId(VolunteerProfile::class, 'usr-', $data['name']),
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'university' => $data['university'],
                    'major' => $data['major'] ?? 'Umum',
                    'city' => $data['city'],
                    'avatar_initials' => $this->initials($data['name']),
                    'interests' => $data['interests'] ?? ['Pendidikan'],
                ]);
            }

            return $user;
        });

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return new MeResource($this->loadUser($user));
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->noContent();
    }

    public function me(Request $request): MeResource
    {
        return new MeResource($this->loadUser($request->user()));
    }

    private function ensureSessionAvailable(Request $request): void
    {
        if (! $request->hasSession()) {
            throw new HttpException(419, 'Session SPA belum tersedia. Panggil /sanctum/csrf-cookie dari frontend terlebih dahulu.');
        }
    }

    /**
     * @param  class-string  $model
     */
    private function uniqueStringId(string $model, string $prefix, string $source): string
    {
        $base = $prefix.Str::slug($source);
        $id = $base;
        $suffix = 2;

        while ($model::query()->whereKey($id)->exists()) {
            $id = $base.'-'.$suffix;
            $suffix++;
        }

        return $id;
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_substr($part, 0, 1))
            ->implode('') ?: 'U';
    }

    private function loadUser(User $user): User
    {
        return $user->load(['volunteerProfile', 'organizers']);
    }
}
