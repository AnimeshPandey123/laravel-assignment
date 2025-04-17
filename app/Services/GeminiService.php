<?php

namespace App\Services;

use App\Exceptions\InvalidGeminiResponseException;
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

        // Enhanced prompt for analyzing resume vs job description
        $prompt = <<<EOT
            You are a top-tier Applicant Tracking System (ATS) and resume analysis expert. Your role is to analyze and compare the provided resume with the job description, focusing on **semantic similarity**, **skill relevance**, and **role alignment**.

            Use natural language understanding to account for:
            - Synonyms and alternative phrasing of skills (e.g., "project leadership" vs. "project management")
            - Industry-specific jargon and context
            - Experience depth and recency
            - Education level and field
            - Certifications and their applicability
            - Use of key action verbs and impact-driven phrasing

            ### Resume:
            {$resume}

            ### Job Description:
            {$jobDescription}

            Generate a well-structured **JSON response only**, following **strict formatting** (no markdown, no explanations). Here's the required structure:

            {
            "matched_skills": [List of overlapping or semantically similar skills],
            "missing_skills": [Required skills from the job description missing in the resume],
            "suggested_skills": [Additional valuable skills that could strengthen the profile],
            "matched_keywords": [Important terms or phrases present in both documents],
            "missing_keywords": [Important terms in the job description that are not in the resume],
            "experience_match": {
                "percentage": 0-100,
                "details": "Summarize relevance of experience in bullet points or concise paragraphs"
            },
            "education_match": {
                "percentage": 0-100,
                "details": "Assess alignment of education field, level, and institution quality"
            },
            "certifications_match": {
                "percentage": 0-100,
                "details": "Evaluate relevance, recency, and industry value of certifications"
            },
            "job_fit_score": {
                "overall_score": 0-100,
                "breakdown": {
                "skills": 40,
                "experience": 30,
                "education": 20,
                "certifications": 10
                }
            },
            "strengths": [List of resume strengths with context],
            "weaknesses": [Areas that need improvement or lack of alignment],
            "career_trajectory": [Evaluation of the candidate's career growth or non-linear career path],
            "recommendations": [
                "Include measurable outcomes for past roles",
                "Add certifications such as XYZ",
                "Mention tools like ABC used in past projects"
            ]
            }

            Important Notes:
            - Use **semantic matching** when comparing.
            - Include only **valid JSON** — no comments or markdown.
            - Do not add introductory or closing statements.

            EOT;

        // Prepare the payload for Gemini API request
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

        // Make the API request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post($url, $payload);

        // Check if the request failed
        if ($response->failed()) {
            return ['error' => 'Failed to connect to Gemini API'];
        }

        // Extract the response content
        $data = $response->json();

        // Extract the raw text response
        $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Clean the response by removing markdown code block markers
        $cleanJson = trim($rawText);
        $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $cleanJson);

        // Decode the cleaned JSON
        $parsedData = json_decode($cleanJson, true);

        // Validate the parsed data
        if (!$this->isValidGeminiResponse($parsedData)) {
            throw new InvalidGeminiResponseException('Gemini response did not match expected format. The response was: ' . json_encode($parsedData, JSON_PRETTY_PRINT));
        }

        return $parsedData ?: ['error' => 'Failed to parse response'];
    }

    // Validate if the response from Gemini API matches the expected format
    protected function isValidGeminiResponse($data): bool
    {
        // List of required keys in the response
        $requiredKeys = [
            'matched_skills', 'missing_skills', 'suggested_skills',
            'matched_keywords', 'missing_keywords',
            'experience_match', 'education_match', 'certifications_match',
            'job_fit_score', 'strengths', 'weaknesses', 'recommendations', 'career_trajectory'
        ];

        // Check if all required keys exist in the response
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                return false;
            }
        }

        // Ensure proper data types and structure
        if (!is_array($data['matched_skills']) || !is_array($data['missing_skills'])) return false;
        if (!is_array($data['job_fit_score']['breakdown'] ?? null)) return false;
        if (!isset($data['experience_match']['percentage'])) return false;

        return true;
    }
}
