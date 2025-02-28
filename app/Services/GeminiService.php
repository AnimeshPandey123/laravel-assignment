<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function analyzeResume($resume, $jobDescription)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$this->apiKey}";

        $prompt = "
        You are an expert ATS analyzer. Compare the skills in the resume with those required in the job description.

        Resume:
        {$resume}

        Job Description:
        {$jobDescription}

        Provide a detailed analysis in JSON format with the following keys:
        1. \"matched_skills\": List of skills found in both the resume and job description
        2. \"missing_skills\": List of skills in the job description but missing from the resume
        3. \"suggested_skills\": List of additional relevant skills to consider adding
        4. \"score\": A numeric score from 0-100 indicating the skills match percentage
        5. \"explanation\": A brief explanation of your analysis

        Respond only with the JSON object:
        ";

        $response = Http::post($url, [
            'prompt' => ['text' => $prompt],
        ]);

        return $response->json();
    }
}
