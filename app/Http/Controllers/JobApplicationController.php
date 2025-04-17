<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class JobApplicationController extends Controller
{
    /**
     * Display a listing of all job applications.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $jobApplications = JobApplication::all();
        
        return response()->json($jobApplications);
    }
    
    /**
     * Store a newly created job application.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resume_id' => 'required|exists:resumes,id',
            'company' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'status' => 'required|string|in:appling, applied,interviewing,offered,rejected',
            'date_applied' => 'sometimes|date',
            'description' => 'required|string',
            'notes' => 'string',
            'link' => 'required|url',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $jobApplication = JobApplication::create($validator->validated());
        
        return response()->json($jobApplication, 201);
    }
    
    /**
     * Display the specified job application.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $jobApplication = JobApplication::findOrFail($id);
            
            return response()->json($jobApplication);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'message' => 'Job application not found.'
            ], 404);
        }
    }
    
    /**
     * Update the specified job application.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $jobApplication = JobApplication::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'resume_id' => 'sometimes|exists:resumes,id',
                'company' => 'sometimes|string|max:255',
                'position' => 'sometimes|string|max:255',
                'status' => 'sometimes|string|in:applied,interviewing,offered,rejected',
                'date_applied' => 'sometimes|date',
                'description' => 'sometimes|string',
                'notes' => 'sometimes|string',
                'link' => 'sometimes|url',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
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
     * Remove the specified job application.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $jobApplication = JobApplication::findOrFail($id);
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