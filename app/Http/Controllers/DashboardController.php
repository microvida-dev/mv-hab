<?php

namespace App\Http\Controllers;

use App\Enums\ContractStatus;
use App\Enums\MaintenanceRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\Citizen;
use App\Models\Contract;
use App\Models\Household;
use App\Models\HousingApplication;
use App\Models\HousingUnit;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($this->authenticatedUser($request)->hasRole('candidate')) {
            return to_route('candidate.dashboard');
        }

        $metrics = [
            [
                'label' => 'Munícipes',
                'value' => Citizen::count(),
                'description' => 'Total de munícipes registados.',
            ],
            [
                'label' => 'Agregados familiares',
                'value' => Household::count(),
                'description' => 'Agregados familiares associados.',
            ],
            [
                'label' => 'Habitações',
                'value' => HousingUnit::count(),
                'description' => 'Habitações registadas no parque habitacional.',
            ],
            [
                'label' => 'Candidaturas',
                'value' => HousingApplication::count(),
                'description' => 'Candidaturas submetidas e em curso.',
            ],
            [
                'label' => 'Contratos ativos',
                'value' => Contract::query()
                    ->where('status', ContractStatus::Active->value)
                    ->count(),
                'description' => 'Contratos atualmente ativos.',
            ],
            [
                'label' => 'Pagamentos recebidos',
                'value' => Payment::query()
                    ->where('status', PaymentStatus::Paid->value)
                    ->sum('amount'),
                'description' => 'Montante total recebido.',
                'currency' => true,
            ],
            [
                'label' => 'Pedidos de manutenção abertos',
                'value' => MaintenanceRequest::query()
                    ->where('status', MaintenanceRequestStatus::Open->value)
                    ->count(),
                'description' => 'Pedidos que aguardam tratamento.',
            ],
        ];

        return view('dashboard', compact('metrics'));
    }
}
