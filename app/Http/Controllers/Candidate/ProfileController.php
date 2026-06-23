<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return to_route('profile.edit');
    }
}
