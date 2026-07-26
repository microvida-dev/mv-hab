<?php

namespace App\Http\Controllers\Backoffice\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Platform\GrantPlatformOperatorRequest;
use App\Http\Requests\Backoffice\Platform\RevokePlatformOperatorRequest;
use App\Models\AuditEvent;
use App\Models\PlatformOperatorAssignment;
use App\Models\User;
use App\Services\Platform\PlatformOperatorManagementService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class PlatformOperatorController extends Controller
{
    private const AUDIT_EVENTS = [
        'platform_operator_bootstrapped',
        'platform_operator_granted',
        'platform_operator_revoked',
    ];

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', PlatformOperatorAssignment::class);

        return view('backoffice.platform.operators.index', [
            'assignments' => PlatformOperatorAssignment::query()
                ->with([
                    'user:id,name,status,municipality_id',
                    'grantedBy:id,name',
                    'revokedBy:id,name',
                ])
                ->latest('granted_at')
                ->paginate(25),
            'availableUsers' => User::query()
                ->select(['id', 'name'])
                ->where('status', 'active')
                ->whereNull('municipality_id')
                ->whereDoesntHave('platformOperatorAssignment')
                ->whereDoesntHave('roles', fn ($query) => $query
                    ->where('roles.name', 'candidate')
                    ->where('roles.is_active', true))
                ->whereHas('roles', fn ($query) => $query
                    ->where('roles.is_active', true)
                    ->whereHas('permissions', fn ($permissions) => $permissions
                        ->whereIn('permissions.name', [
                            '*',
                            'platform_operators.view',
                            'platform_operators.*',
                            '*.view',
                        ])))
                ->whereHas('mfaDevices', fn ($query) => $query
                    ->whereNotNull('confirmed_at')
                    ->whereNull('disabled_at'))
                ->orderBy('name')
                ->limit(100)
                ->get(),
        ]);
    }

    public function show(
        Request $request,
        PlatformOperatorAssignment $platformOperatorAssignment,
    ): View {
        Gate::authorize('view', $platformOperatorAssignment);

        return view('backoffice.platform.operators.show', [
            'assignment' => $platformOperatorAssignment->load([
                'user:id,name,status,municipality_id',
                'grantedBy:id,name',
                'revokedBy:id,name',
            ]),
        ]);
    }

    public function store(
        GrantPlatformOperatorRequest $request,
        PlatformOperatorManagementService $operators,
    ): RedirectResponse {
        try {
            $assignment = $operators->grant(
                $this->authenticatedUser($request),
                User::query()->findOrFail($request->integer('user_id')),
                (string) $request->validated('justification'),
            );
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['platform_operator' => $exception->getMessage()]);
        }

        return redirect()
            ->route('backoffice.platform.operators.show', $assignment)
            ->with('status', 'Operador de plataforma concedido com auditoria.');
    }

    public function revoke(
        RevokePlatformOperatorRequest $request,
        PlatformOperatorAssignment $platformOperatorAssignment,
        PlatformOperatorManagementService $operators,
    ): RedirectResponse {
        try {
            $operators->revoke(
                $this->authenticatedUser($request),
                $platformOperatorAssignment,
                (string) $request->validated('justification'),
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['platform_operator' => $exception->getMessage()]);
        }

        return back()->with('status', 'Operador de plataforma revogado com auditoria.');
    }

    public function audit(Request $request): View
    {
        Gate::authorize('auditAny', PlatformOperatorAssignment::class);

        return view('backoffice.platform.operators.audit', [
            'events' => AuditEvent::query()
                ->with(['user:id,name', 'subjectUser:id,name'])
                ->whereIn('event_code', self::AUDIT_EVENTS)
                ->latest('occurred_at')
                ->paginate(25),
        ]);
    }
}
