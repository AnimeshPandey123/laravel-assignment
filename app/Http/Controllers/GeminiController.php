<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidGeminiResponseException;
use App\Models\JobApplication;
use App\Models\Resume;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class GeminiController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->middleware('auth');
        $this->geminiService = $geminiService;
    }

    /**
     * Analyze raw resume text and job description
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'resume' => 'required|string',
            'job_description' => 'required|string',
        ]);

        try {
            $response = $this->geminiService->analyzeResume(
                $request->input('resume'),
                $request->input('job_description')
            );
    
            return response()->json($response);
        } catch (InvalidGeminiResponseException $e) {
            Log::error('Gemini LLM returned invalid response.', [
                'message' => $e->getMessage(),
            ]);
    
            return response()->json([
                'error' => 'AI analysis failed due to unexpected response. Please try again.'
            ], 502);
        } catch (\Exception $e) {
            Log::error('Unexpected error during resume analysis.', [
                'message' => $e->getMessage(),
            ]);
    
            return response()->json([
                'error' => 'An unexpected error occurred.'
            ], 500);
        }
    }

    /**
     * Analyze resume and job application that belong to the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyzeResumeAndJobApplication(Request $request)
    {
        $user = Auth::user();

        try {
            $request->validate([
                'resume_id' => 'required|integer|exists:resumes,id',
                'job_application_id' => 'required|integer|exists:job_applications,id',
            ]);
            
            // Get the resume and verify ownership
            $resume = Resume::with(['experiences', 'education', 'skills'])->findOrFail($request->input('resume_id'));
            if ($resume->user_id !== $user->id) {
                return response()->json(['error' => 'You do not have permission to access this resume.'], 403);
            }
            
            // Get the job application
            $jobApplication = JobApplication::findOrFail($request->input('job_application_id'));
            
            // Verify that the job application is linked to a resume owned by the user
            $jobAppResume = Resume::findOrFail($jobApplication->resume_id);
            if ($jobAppResume->user_id !== $user->id) {
                return response()->json(['error' => 'You do not have permission to access this job application.'], 403);
            }

            $resumeString = $this->formatResume($resume);
            $jobDescriptionString = $this->formatJobApplication($jobApplication);

            $response = $this->geminiService->analyzeResume($resumeString, $jobDescriptionString);

            return response()->json($response);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Resume or Job Description not found.'], 404);
        } catch (InvalidGeminiResponseException $e) {
            Log::error('Gemini LLM returned invalid response.', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'AI analysis failed due to unexpected response.'], 502);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error during resume analysis.', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    /**
     * Format resume data as a string for AI processing
     * 
     * @param Resume $resume
     * @return string
     */
    protected function formatResume($resume)
    {
        $parts = [
            "Title: {$resume->title}",
            "Summary: {$resume->summary}",
            "Skills: " . $resume->skills->pluck('name')->join(', '),
            "Experiences:",
        ];

        foreach ($resume->experiences as $exp) {
            $endDate = $exp->end_date ?? 'Present';
            $parts[] = "- {$exp->title} at {$exp->company} ({$exp->start_date} to {$endDate}): {$exp->description}";
        }

        $parts[] = "Education:";
        foreach ($resume->education as $edu) {
            $endDate = $edu->end_date ?? 'Present';
            $parts[] = "- {$edu->degree} in {$edu->field_of_study} at {$edu->institution} ({$edu->start_date} to {$endDate})";
        }

        return implode("\n", $parts);
    }

    /**
     * Format job application data as a string for AI processing
     * 
     * @param JobApplication $jobApp
     * @return string
     */
    protected function formatJobApplication(JobApplication $jobApp)
    {
        return <<<TEXT
            Position: {$jobApp->position}
            Company: {$jobApp->company}
            Description: {$jobApp->description}

            Notes: {$jobApp->notes}
            Link: {$jobApp->link}
            TEXT;
    }
}