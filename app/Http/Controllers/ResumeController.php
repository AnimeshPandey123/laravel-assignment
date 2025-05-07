<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Models\Experience;
use App\Models\Education;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ResumeController extends Controller
{
    /**
     * Constructor to ensure authenticated users only
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Create a new resume for the authenticated user
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string',
            'summary' => 'nullable|string',
            'experiences' => 'array',
            'experiences.*.title' => 'required|string',
            'experiences.*.company' => 'required|string',
            'experiences.*.location' => 'nullable|string',
            'experiences.*.start_date' => 'required|date',
            'experiences.*.end_date' => 'nullable|date|after_or_equal:experiences.*.start_date',
            'experiences.*.description' => 'nullable|string',
            'education' => 'array',
            'education.*.institution' => 'required|string',
            'education.*.degree' => 'required|string',
            'education.*.field_of_study' => 'nullable|string',
            'education.*.start_date' => 'required|date',
            'education.*.end_date' => 'nullable|date|after_or_equal:education.*.start_date',
            'education.*.grade' => 'nullable|string',
            'education.*.description' => 'nullable|string',
            'skills' => 'array',
            'skills.*.name' => 'required|string',
            'skills.*.proficiency' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create resume with authenticated user's ID
        $resume = new Resume($request->only(['title', 'summary']));
        $resume->user_id = $user->id;
        $resume->save();

        if ($request->has('experiences')) {
            $resume->experiences()->createMany($request->experiences);
        }

        if ($request->has('education')) {
            $resume->education()->createMany($request->education);
        }

        if ($request->has('skills')) {
            foreach ($request->skills as $skillData) {
                $skill = Skill::firstOrCreate(
                    ['name' => $skillData['name']],
                    ['proficiency' => $skillData['proficiency'] ?? null]
                );
                $resume->skills()->attach($skill->id);
            }
        }

        return response()->json([
            'message' => 'Resume created successfully',
            'resume' => $resume->load('experiences', 'education', 'skills')
        ], 201);
    }

    /**
     * Get a specific resume with details (only if it belongs to the authenticated user)
     */
    public function show($id)
    {
        $user = Auth::user();
        $resume = Resume::with(['experiences', 'education', 'skills'])->find($id);

        if (!$resume) {
            return response()->json(['message' => 'Resume not found'], 404);
        }

        // Check if the resume belongs to the authenticated user
        if ($resume->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        return response()->json($resume);
    }

    /**
     * Get all resumes for the authenticated user
     */
    public function index()
    {
        $user = Auth::user();
        $resumes = Resume::where('user_id', $user->id)
            ->with(['experiences', 'education', 'skills'])
            ->get();

        return response()->json($resumes);
    }

    /**
     * Update a resume and its related data (only if it belongs to the authenticated user)
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $resume = Resume::find($id);
        
        if (!$resume) {
            return response()->json(['message' => 'Resume not found'], 404);
        }
        
        // Check if the resume belongs to the authenticated user
        if ($resume->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }
    
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string',
            'summary' => 'nullable|string',
            'experiences' => 'array',
            'experiences.*.id' => 'nullable|exists:experiences,id',
            'experiences.*.title' => 'required|string',
            'experiences.*.company' => 'required|string',
            'experiences.*.location' => 'nullable|string',
            'experiences.*.start_date' => 'required|date',
            'experiences.*.end_date' => 'nullable|date|after_or_equal:experiences.*.start_date',
            'experiences.*.description' => 'nullable|string',
            'education' => 'array',
            'education.*.id' => 'nullable|exists:education,id',
            'education.*.institution' => 'required|string',
            'education.*.degree' => 'required|string',
            'education.*.field_of_study' => 'nullable|string',
            'education.*.start_date' => 'required|date',
            'education.*.end_date' => 'nullable|date|after_or_equal:education.*.start_date',
            'education.*.grade' => 'nullable|string',
            'education.*.description' => 'nullable|string',
            'skills' => 'array',
            'skills.*.name' => 'required|string',
            'skills.*.proficiency' => 'nullable|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $resume->update($request->only(['title', 'summary']));
    
        if ($request->has('experiences')) {
            foreach ($request->experiences as $exp) {
                $exp['start_date'] = Carbon::parse($exp['start_date'])->format('Y-m-d');
                $exp['end_date'] = isset($exp['end_date']) ? Carbon::parse($exp['end_date'])->format('Y-m-d') : null;
    
                if (isset($exp['id'])) {
                    // Verify that the experience belongs to this resume
                    $experience = Experience::where('id', $exp['id'])->where('resume_id', $resume->id)->first();
                    if ($experience) {
                        $experience->update($exp);
                    }
                } else {
                    $resume->experiences()->create($exp);
                }
            }
        }
    
        if ($request->has('education')) {
            foreach ($request->education as $edu) {
                $edu['start_date'] = Carbon::parse($edu['start_date'])->format('Y-m-d');
                $edu['end_date'] = isset($edu['end_date']) ? Carbon::parse($edu['end_date'])->format('Y-m-d') : null;
    
                if (isset($edu['id'])) {
                    // Verify that the education record belongs to this resume
                    $education = Education::where('id', $edu['id'])->where('resume_id', $resume->id)->first();
                    if ($education) {
                        $education->update($edu);
                    }
                } else {
                    $resume->education()->create($edu);
                }
            }
        }
    
        if ($request->has('skills')) {
            $resume->skills()->detach();
            foreach ($request->skills as $skillData) {
                $skill = Skill::firstOrCreate(
                    ['name' => $skillData['name']],
                    ['proficiency' => $skillData['proficiency'] ?? null]
                );
                $resume->skills()->attach($skill->id);
            }
        }
    
        return response()->json([
            'message' => 'Resume updated successfully',
            'resume' => $resume->load('experiences', 'education', 'skills')
        ]);
    }
    
    /**
     * Delete a resume (only if it belongs to the authenticated user)
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $resume = Resume::find($id);
        
        if (!$resume) {
            return response()->json(['message' => 'Resume not found'], 404);
        }
        
        // Check if the resume belongs to the authenticated user
        if ($resume->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        $resume->delete();

        return response()->json(['message' => 'Resume deleted successfully']);
    }
}