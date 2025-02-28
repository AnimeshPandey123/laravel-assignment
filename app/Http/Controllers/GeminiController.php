<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiService;

class GeminiController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'resume' => 'required|string',
            'job_description' => 'required|string',
        ]);

        $response = $this->geminiService->analyzeResume($request->input('resume'), $request->input('job_description'));

        return response()->json($response);
    }
}
