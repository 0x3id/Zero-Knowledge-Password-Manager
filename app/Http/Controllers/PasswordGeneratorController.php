<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordGeneratorController extends Controller
{
    /**
     * Display the standalone Password Generator page.
     *
     * @param  Request  $request  The incoming request.
     * @return View The generator view.
     */
    public function index(Request $request): View
    {
        return view('generator.index');
    }

    /**
     * Record a password-generation event for the landing-page statistics.
     *
     * Fired fire-and-forget from the generator client; only a timestamped
     * audit row is written — never the generated secret itself.
     *
     * @param  Request  $request  The incoming request.
     * @return JsonResponse An empty OK payload.
     */
    public function logGeneration(Request $request): JsonResponse
    {
        app(AuditLogger::class)->log($request->user(), 'password_generated', $request);

        return response()->json(['ok' => true]);
    }
}
