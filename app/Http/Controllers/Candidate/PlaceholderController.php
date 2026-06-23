<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PlaceholderController extends Controller
{
    public function applications(): View
    {
        return view('candidate.placeholders.applications');
    }

    public function documents(): View
    {
        return view('candidate.placeholders.documents');
    }

    public function notifications(): View
    {
        return view('candidate.placeholders.notifications');
    }
}
