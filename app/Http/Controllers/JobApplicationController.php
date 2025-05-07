<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\Resume;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class JobApplicationController extends Controller
{
    /**
     * Constructor to ensure authenticated users only
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the authenticated user's job applications.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        // Get job applications linked to resumes owned by the current user
        $jobApplications = JobApplication::whereHas('resume', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();
        
        return response()->json($jobApplications);
    }
    
    /**
     * Store a newly created job application (verifying resume ownership).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'resume_id' => 'required|exists:resumes,id',
            'company' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'status' => 'required|string|in:applying,applied,interviewing,offered,rejected',
            'date_applied' => 'sometimes|date',
            'description' => 'required|string',
            'notes' => 'nullable|string',
            'link' => 'required|url',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Verify that the resume belongs to the authenticated user
        $resume = Resume::find($request->resume_id);
        if (!$resume || $resume->user_id !== $user->id) {
            return response()->json([
                'message' => 'The specified resume does not belong to you.'
            ], 403);
        }
        
        $jobApplication = JobApplication::create($validator->validated());
        
        return response()->json($jobApplication, 201);
    }
    
    /**
     * Display the specified job application (if it belongs to the authenticated user).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        
        try {
            $jobApplication = JobApplication::with('resume')->findOrFail($id);
            
            // Check if the associated resume belongs to the authenticated user
            if ($jobApplication->resume->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized access'
                ], 403);
            }
            
            return response()->json($jobApplication);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'message' => 'Job application not found.'
            ], 404);
        }
    }
    
    /**
     * Update the specified job application (if it belongs to the authenticated user).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        
        try {
            $jobApplication = JobApplication::with('resume')->findOrFail($id);
            
            // Check if the associated resume belongs to the authenticated user
            if ($jobApplication->resume->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized access'
                ], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'resume_id' => 'sometimes|exists:resumes,id',
                'company' => 'sometimes|string|max:255',
                'position' => 'sometimes|string|max:255',
                'status' => 'sometimes|string|in:applying,applied,interviewing,offered,rejected',
                'date_applied' => 'sometimes|date',
                'description' => 'sometimes|string',
                'notes' => 'sometimes|nullable|string',
                'link' => 'sometimes|url',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            // If resume_id is being updated, verify the new resume belongs to the user
            if ($request->has('resume_id') && $request->resume_id != $jobApplication->resume_id) {
                $newResume = Resume::find($request->resume_id);
                if (!$newResume || $newResume->user_id !== $user->id) {
                    return response()->json([
                        'message' => 'The specified resume does not belong to you.'
                    ], 403);
                }
            }
            
            $jobApplication->update($validator->validated());
            
            return response()->json($jobApplication);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'message' => 'Job application not found.'
            ], 404);
        }
    }
    
    /**
     * Remove the specified job application (if it belongs to the authenticated user).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        
        try {
            $jobApplication = JobApplication::with('resume')->findOrFail($id);
            
            // Check if the associated resume belongs to the authenticated user
            if ($jobApplication->resume->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized access'
                ], 403);
            }
            
            $jobApplication->delete();
            
            return response()->json([
                'message' => 'Job application deleted successfully.'
            ]);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'message' => 'Job application not found.'
            ], 404);
        }
    }
}