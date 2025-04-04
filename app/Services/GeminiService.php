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
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->apiKey}";

        $prompt = "
        You are an expert ATS analyzer. Compare the skills in the resume with those required in the job description.

        Resume:
        {$resume}

        Job Description:
        {$jobDescription}
         Generate a JSON report with the following structure:
        {
            \"matched_skills\": [List of skills found in both the resume and job description],
            \"missing_skills\": [List of required skills absent in the resume],
            \"suggested_skills\": [Additional skills that could strengthen the candidate's profile],
            \"matched_keywords\": [Key phrases present in both documents],
            \"missing_keywords\": [Key phrases present in the job description but not in the resume],
            \"experience_match\": {\"percentage\": %, \"details\": 'Experience relevance breakdown with confidence level'},
            \"education_match\": {\"percentage\": %, \"details\": 'Education relevance breakdown'},
            \"certifications_match\": {\"percentage\": %, \"details\": 'Certification relevance breakdown'},
            \"job_fit_score\": {\"overall_score\": %, \"breakdown\": { \"skills\": 40%, \"experience\": 30%, \"education\": 20%, \"certifications\": 10% }},
            \"strengths\": [Key strengths found in the resume],
            \"weaknesses\": [Areas that need improvement],
            \"recommendations\": [Personalized improvement tips such as skills to learn, keywords to add, and relevant certifications or courses to take],
        }
        
        Ensure semantic matching is used to recognize related skills even if phrased differently. Respond only with the JSON output, without any additional text.
        ";

        $payload = [
            "contents" => [
                [
                    "role" => "user",
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 1,
                "topK" => 40,
                "topP" => 0.95,
                "maxOutputTokens" => 8192,
                "responseMimeType" => "text/plain"
            ]
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post($url, $payload);

        if ($response->failed()) {
            return ['error' => 'Failed to connect to Gemini API'];
        }

        $data = $response->json();

        // Extract the JSON text inside the response
        $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Remove markdown code block markers (```json ... ```)
        $cleanJson = preg_replace('/^```json\n|\n```$/', '', trim($rawText));

        // Decode the cleaned JSON string
        $parsedData = json_decode($cleanJson, true);

        return $parsedData ?: ['error' => 'Failed to parse response'];
    }
}
