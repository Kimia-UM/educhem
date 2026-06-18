<?php

namespace App\Http\Middleware;

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
        $sidebarClasses = [];

        if ($user) {
            // Eager load class beserta topic dan phase-nya
            if ($user->hasRole(['SISWA', 'siswa', 'Siswa'])) {
                $sidebarClasses = $user->joinedClasses()
                                       ->with('topics.phases')
                                       ->get();
            } elseif ($user->hasRole(['GURU', 'guru', 'Guru'])) {
                $sidebarClasses = $user->taughtClasses()
                                       ->with('topics.phases')
                                       ->get();
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    ...$user->toArray(),
                    'roles' => $user->getRoleNames()->map(fn($role) => ['name' => $role])
                ] : null,
            ],
            'sidebarClasses' => $sidebarClasses,
        ];
    }
}
