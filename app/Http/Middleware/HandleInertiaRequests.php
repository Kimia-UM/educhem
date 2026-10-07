<?php

namespace App\Http\Middleware;

use App\Models\PasswordResetRequest;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => fn () => [
                'user' => $user ? [
                    ...$user->toArray(),
                    'roles' => $user->getRoleNames()->map(fn ($role) => ['name' => $role]),
                ] : null,
            ],
            'sidebarClasses' => function () use ($user) {
                if (! $user) {
                    return [];
                }

                if ($user->hasRole(['SISWA', 'siswa', 'Siswa'])) {
                    return $user->joinedClasses()
                        ->with([
                            'topics' => fn ($query) => $query->where('topics.is_published', true),
                            'topics.phases',
                        ])
                        ->get();
                }

                if ($user->hasRole(['GURU', 'guru', 'Guru'])) {
                    return $user->taughtClasses()
                        ->with('topics.phases')
                        ->get();
                }

                return [];
            },
            'pendingPasswordResetsCount' => fn () => $user?->hasRole('ADMIN')
                ? PasswordResetRequest::where('status', 'pending')->count()
                : 0,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'toast' => $request->session()->get('toast'),
            ],
        ];
    }
}
