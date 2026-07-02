<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\Agenda\AgendaView;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineStatus;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Http\Controllers\Controller;
use App\Services\Agenda\AgendaService;
use App\Services\Agenda\Filters\AgendaFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function __construct(
        private readonly AgendaService $agenda,
    ) {}

    public function index(Request $request): View
    {
        $view = AgendaView::tryFrom((string) $request->query('view', AgendaView::Day->value)) ?? AgendaView::Day;

        $filters = new AgendaFilters(
            view: $view,
            workspace: $this->enumFromQuery(TimelineWorkspace::class, $request->query('workspace')),
            priority: $this->enumFromQuery(TimelinePriority::class, $request->query('priority')),
            status: $this->enumFromQuery(TimelineStatus::class, $request->query('status')),
            type: $this->enumFromQuery(TimelineType::class, $request->query('type')),
            technicianId: $request->integer('technician') ?: null,
            from: $request->query('date') ? now()->parse((string) $request->query('date')) : now(),
        );

        return view('backoffice.agenda.index', [
            'agenda' => $this->agenda->build($request->user(), $filters)->toArray(),
            'filters' => [
                'view' => $view->value,
                'date' => $filters->from?->toDateString(),
                'workspace' => $filters->workspace?->value,
                'priority' => $filters->priority?->value,
                'status' => $filters->status?->value,
                'type' => $filters->type?->value,
                'technician' => $filters->technicianId,
            ],
            'options' => [
                'views' => AgendaView::cases(),
                'workspaces' => TimelineWorkspace::cases(),
                'priorities' => TimelinePriority::cases(),
                'statuses' => TimelineStatus::cases(),
                'types' => TimelineType::cases(),
            ],
        ]);
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return T|null
     */
    private function enumFromQuery(string $enum, mixed $value): ?\BackedEnum
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $enum::tryFrom($value);
    }
}
