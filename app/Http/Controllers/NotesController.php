<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class NotesController extends Controller
{
    /**
     * Display a listing of the user's notes.
     */
    public function index(): JsonResponse
    {
        try {
            $notes = Auth::user()->notes()
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 1,
                'message' => 'Notes retrieved successfully',
                'data' => $notes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error retrieving notes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created note.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $note = Auth::user()->notes()->create([
                'title' => $request->title,
                'content' => $request->content
            ]);

            return response()->json([
                'status' => 1,
                'message' => 'Note created successfully',
                'data' => $note
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error creating note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified note.
     */
    public function update(Request $request, Note $note): JsonResponse
    {
        try {
            // Check if the note belongs to the authenticated user
            if ($note->user_id !== Auth::id()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Unauthorized access to this note'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $note->update([
                'title' => $request->title,
                'content' => $request->content
            ]);

            return response()->json([
                'status' => 1,
                'message' => 'Note updated successfully',
                'data' => $note->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error updating note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified note.
     */
    public function destroy(Note $note): JsonResponse
    {
        try {
            // Check if the note belongs to the authenticated user
            if ($note->user_id !== Auth::id()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Unauthorized access to this note'
                ], 403);
            }

            $note->delete();

            return response()->json([
                'status' => 1,
                'message' => 'Note deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error deleting note: ' . $e->getMessage()
            ], 500);
        }
    }
}
