<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use function PHPUnit\Framework\isEmpty;

class StudentController extends Controller
{
    public function getStudentById($id)
    {
        try {
            $student = Student::where('id', $id)->first();

            if (!$student) {
                return response()->json([
                    'message' => 'No student found!',
                ], 404);
            }

            return response()->json($student, 200);
            
        } catch (\Exception $e) {
            Log::error('Error fetching student: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllStudent()
    {
        try {
            $students = Student::latest()->get();
            return response()->json($students, 200);
            
        } catch (\Exception $e) {
            Log::error('Error fetching students: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch students',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addStudent(Request $request)
    {
        try {
            // Log incoming request for debugging
            Log::info('Add student request received', [
                'has_file' => $request->hasFile('profile_image'),
                'all_data' => $request->all()
            ]);

            // Validate request
            $data = $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'course' => ['required', 'string', 'max:255'],
                'year_level' => ['required', 'string', 'max:50'],
                'profile_image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:5120'], // 5MB max
            ]);

            // Handle file upload
            if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
                $file = $request->file('profile_image');
                
                // Log file details
                Log::info('File details:', [
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'extension' => $file->getClientOriginalExtension()
                ]);

                // Store the file and get the path
                $path = $file->store('profile_images', 'public');
                
                if (!$path) {
                    Log::error('Failed to store image');
                    return response()->json([
                        'message' => 'Failed to upload image',
                        'errors' => ['profile_image' => ['Image upload failed']]
                    ], 500);
                }

                // IMPORTANT: Store ONLY the filename with its folder path
                // This will store "profile_images/filename.jpg" which is what we need
                $data['profile_image'] = $path;
                
                Log::info('Image stored successfully at: ' . $path);
            } else {
                Log::warning('No valid image file in request');
                return response()->json([
                    'message' => 'No valid image file',
                    'errors' => ['profile_image' => ['Please select a valid image file']]
                ], 422);
            }

            // Create student
            $student = Student::create($data);

            Log::info('Student created successfully with ID: ' . $student->id);

            return response()->json([
                'message' => 'Student added successfully!',
                'student' => $student
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Student creation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to add student. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStudent(Request $request, $id)
    {
        try {
            Log::info('Update student request received for ID: ' . $id);

            $student = Student::where('id', $id)->first();

            if (!$student) {
                return response()->json([
                    'message' => 'No student found!',
                ], 404);
            }

            // Validate request
            $data = $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'course' => ['required', 'string', 'max:255'],
                'year_level' => ['required', 'string', 'max:50'],
                'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
            ]);

            // Handle file upload if present
            if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
                // Delete old image
                if ($student->profile_image) {
                    Storage::disk('public')->delete($student->profile_image);
                    Log::info('Deleted old image: ' . $student->profile_image);
                }

                // Store new image
                $file = $request->file('profile_image');
                $path = $file->store('profile_images', 'public');
                
                if (!$path) {
                    Log::error('Failed to store new image');
                    return response()->json([
                        'message' => 'Failed to upload image',
                        'errors' => ['profile_image' => ['Image upload failed']]
                    ], 500);
                }

                // Store the full path including folder
                $data['profile_image'] = $path;
                Log::info('New image stored at: ' . $path);
            }

            // Update student
            $student->update($data);

            Log::info('Student updated successfully with ID: ' . $id);

            return response()->json([
                'message' => 'Student updated successfully!',
                'student' => $student
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed for update', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Student update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to update student. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteStudent($id)
    {
        try {
            Log::info('Delete student request received for ID: ' . $id);

            $student = Student::where('id', $id)->first();

            if (!$student) {
                return response()->json([
                    'message' => 'No student found!',
                ], 404);
            }

            // Delete image file
            if ($student->profile_image) {
                Storage::disk('public')->delete($student->profile_image);
                Log::info('Deleted image: ' . $student->profile_image);
            }

            // Delete student
            $student->delete();

            Log::info('Student deleted successfully with ID: ' . $id);

            return response()->json([
                'message' => 'Student deleted successfully!',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Student deletion error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to delete student. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}