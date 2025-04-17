<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidGeminiResponseException;
use App\Models\JobApplication;
use App\Models\Resume;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Services\GeminiService;
use Illuminate\Validation\ValidationException;
use Log;

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

    public function analyzeResumeAndJobApplication(Request $request)
    {

        try {
            $request->validate([
                'resume_id' => 'required|integer|exists:resumes,id',
                'job_application_id' => 'required|integer|exists:job_applications,id',
            ]);
            $resume = Resume::with(['experiences', 'education', 'skills'])->findOrFail($request->input('resume_id'));
            $jobApplication = JobApplication::findOrFail($request->input('job_application_id'));

            $resumeString = $this->formatResume($resume);
            $jobDescriptionString = $this->formatJobApplication($jobApplication);

            // dd($resumeString);
            // dd($jobDescriptionString);

            $response = $this->geminiService->analyzeResume($resumeString, $jobDescriptionString);

            return response()->json($response);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Resume or Job Description not found.'], 404);
        } catch (InvalidGeminiResponseException $e) {
            Log::error('Gemini LLM returned invalid response.', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'AI analysis failed due to unexpected response.'], 502);
        } catch (ValidationException $e) {
            Log::error('Unexpected error during resume analysis.', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        }catch (\Exception $e) {
            Log::error('Unexpected error during resume analysis.', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    protected function formatResume($resume)
    {
        $parts = [
            "Title: {$resume->title}",
            "Summary: {$resume->summary}",
            "Skills: " . $resume->skills->pluck('name')->join(', '),
            "Experiences:",
        ];

        foreach ($resume->experiences as $exp) {
            $parts[] = "- {$exp->title} at {$exp->company} ({$exp->start_date} to {$exp->end_date}): {$exp->description}";
        }

        $parts[] = "Education:";
        foreach ($resume->education as $edu) {
            $parts[] = "- {$edu->degree} in {$edu->field_of_study} at {$edu->institution} ({$edu->start_date} to {$edu->end_date})";
        }

        return implode("\n", $parts);
    }

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
