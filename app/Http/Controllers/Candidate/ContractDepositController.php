<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ContractDepositController extends Controller
{
    public function show(Contract $leaseContract): View
    {
        Gate::authorize('view', $leaseContract);
        $leaseContract->load('deposit');

        if ($leaseContract->deposit) {
            Gate::authorize('view', $leaseContract->deposit);
        }

        return view('candidate.contracts.deposit', compact('leaseContract'));
    }
}
