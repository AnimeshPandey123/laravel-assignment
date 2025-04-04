<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Models\Experience;
use App\Models\Education;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ResumeController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
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

        $resume = Resume::create($request->only(['user_id', 'title', 'summary']));

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
     * Get a specific resume with details.
     */
    public function show($id)
    {
        $resume = Resume::with(['experiences', 'education', 'skills'])->find($id);

        if (!$resume) {
            return response()->json(['message' => 'Resume not found'], 404);
        }

        return response()->json($resume);
    }

    /**
     * Get all resumes for a specific user.
     */
    public function index(Request $request)
    {
        $resumes = Resume::where('user_id', $request->user_id)
            ->with(['experiences', 'education', 'skills'])
            ->get();

        return response()->json($resumes);
    }

    /**
     * Update a resume and its related data.
     */

    public function update(Request $request, $id)
    {
        $resume = Resume::find($id);
        if (!$resume) {
            return response()->json(['message' => 'Resume not found'], 404);
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
                    Experience::where('id', $exp['id'])->update($exp);
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
                    Education::where('id', $edu['id'])->update($edu);
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
    

    public function destroy($id)
    {
        $resume = Resume::find($id);
        if (!$resume) {
            return response()->json(['message' => 'Resume not found'], 404);
        }

        $resume->delete();

        return response()->json(['message' => 'Resume deleted successfully']);
    }
}
