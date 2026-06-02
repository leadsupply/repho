<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use Inertia\Inertia;
use Inertia\Response;

class InstructionsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('instructions', [
            'repositories' => Repository::all(['id', 'name', 'slug', 'auth_type']),
        ]);
    }
}
