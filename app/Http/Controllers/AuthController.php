<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\User;
use App\Models\Student;
use App\Models\Instructor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        return $this->handleTryCatch(function () use ($request) {
            $validated = $request->validate([
                'name' => 'required|string',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'role' => 'required|numeric',
                'fcm_token' => 'nullable|string',
                'guardians_name' => 'required|string',
                'last_name' => 'required|string',
                'address' => 'required|string',
                'phone' => 'required|numeric',
                'family_email' => 'required|email',
                'username' => 'required|string',
                'city' => 'required|string',
                'state' => 'required|string',
                'zip_code' => 'required|string',
                'contact_type_id' => 'required|exists:contact_types,id',
                'is_term_&_condition' => 'boolean',
                'emergency_contact_name' => 'string',
                'emergency_contact_phone' => 'numeric',
                'zip_code' => 'required|string',
                // Students fields
                'students' => 'array',
                'students.*.first_name' => 'required|string',
                'students.*.last_name' => 'required|string',
                'students.*.gender' => 'required|string|in:male,female',
                'students.*.medical_condition' => 'nullable|string',
                'students.*.one_time_reg_fee' => 'nullable|numeric',
            ]);

            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role' => $validated['role'],
                'fcm_token' => $validated['fcm_token'] ?? null,
            ]);

            $family = Family::create([
                'user_id' => $user->id,
                'guardians_name' => $validated['guardians_name'],
                'last_name' => $validated['last_name'],
                'address' => $validated['address'],
                'phone' => $validated['phone'],
                'email' => $validated['family_email'],
                'username' => $validated['username'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'zip_code' => $validated['zip_code'],
                'contact_type_id' => $validated['contact_type_id'],
                'is_term_&_condition' => $validated['is_term_&_condition'] ?? false,
                'emergency_contact_name' => $validated['emergency_contact_name'],
                'emergency_contact_phone' => $validated['emergency_contact_phone'],
            ]);

            // Create students if provided
            $students = [];
            if (isset($validated['students']) && is_array($validated['students'])) {
                foreach ($validated['students'] as $studentData) {
                    $students[] = Student::create([
                        'family_id' => $family->id,
                        'first_name' => $studentData['first_name'],
                        'last_name' => $studentData['last_name'],
                        'gender' => $studentData['gender'],
                        'medical_condition' => $studentData['medical_condition'] ?? null,
                        'one_time_reg_fee' => $studentData['one_time_reg_fee'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            $token = JWTAuth::fromUser($user);

            return $this->successResponse([
                'user' => $user,
                'family' => $family,
                'students' => $students,
                'token' => $token,
            ], 'User registered successfully with family profile and students.', 201);
        });
    }

    public function login(Request $request)
    {
        return $this->handleTryCatch(function () use ($request) {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
                'fcm_token' => 'nullable|string',
            ]);

            $credentials = $request->only('email', 'password');

            if (! $token = JWTAuth::attempt($credentials)) {
                return $this->errorResponse('Invalid credentials', null, 401);
            }

            $user = JWTAuth::setToken($token)->authenticate();

            // Update FCM token if provided
            if (isset($validated['fcm_token'])) {
                $user->update(['fcm_token' => $validated['fcm_token']]);
            }

            return $this->successResponse([
                'user' => $user,
                'token' => $token,
            ], 'Login successful');
        });
    }

    // Web login with role-based redirect
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function webLogin(Request $request)
    {
        // Debug logging
        Log::info('Login attempt started', [
            'email' => $request->email,
            'has_password' => !empty($request->password),
            'request_data' => $request->all()
        ]);

        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            Log::info('Validation passed', $credentials);

            if (Auth::guard('web')->attempt($credentials)) {
                $request->session()->regenerate();
                $user = Auth::guard('web')->user();

                Log::info('Login successful', ['user_id' => $user->id, 'role' => $user->role]);

                // Role-based redirect
                switch ($user->role) {
                    case 1: // Admin
                        return redirect()->intended('/admin/dashboard');
                    case 2: // Instructor
                        Log::info('Redirecting to instructor dashboard');
                        return redirect()->intended('/instructor/dashboard');
                    case 3: // Family
                        return redirect()->intended('/family/dashboard');
                    default:
                        Log::warning('Invalid user role', ['role' => $user->role]);
                        Auth::guard('web')->logout();
                        return back()->withErrors([
                            'email' => 'Invalid user role.',
                        ]);
                }
            }

            Log::warning('Login failed - invalid credentials');
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Login error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors([
                'email' => 'An error occurred during login. Please try again.',
            ])->withInput();
        }
    }

    public function webLogout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/login');
    }

    public function logout(Request $request)
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    // Create family profile after registration
    public function createFamily(Request $request)
    {
        return $this->handleTryCatch(function () use ($request) {
            $user = $request->user();

            $validated = $request->validate([
                'guardians_name' => 'required|string',
                'last_name' => 'required|string',
                'address' => 'required|string',
                'phone' => 'required|numeric',
                'email' => 'required|email',
                'username' => 'required|string',
                'city' => 'required|string',
                'state' => 'required|string',
                'zip_code' => 'required|string',
                'contact_type_id' => 'required|exists:contact_types,id',
                'is_term_&_condition' => 'boolean',
                'emergency_contact_name' => 'string',
                'emergency_contact_phone' => 'numeric',
            ]);

            $family = Family::create(array_merge($validated, [
                'user_id' => $user->id,
            ]));

            return $this->successResponse($family, 'Family profile created successfully.', 201);
        });
    }

    // Update family profile
    public function updateFamily(Request $request)
    {
        return $this->handleTryCatch(function () use ($request) {
            $user = $request->user();

            $validated = $request->validate([
                'guardians_name' => 'string',
                'last_name' => 'string',
                'address' => 'string',
                'phone' => 'numeric',
                'email' => 'email',
                'username' => 'string',
                'city' => 'string',
                'state' => 'string',
                'zip_code' => 'string',
                'contact_type_id' => 'exists:contact_types,id',
                'is_term_&_condition' => 'boolean',
                'emergency_contact_name' => 'string',
                'emergency_contact_phone' => 'numeric',
            ]);

            $family = Family::where('user_id', $user->id)->firstOrFail();
            $family->update($validated);

            return $this->successResponse($family, 'Family profile updated successfully.');
        });
    }

    // Get family profile
    public function getFamily(Request $request)
    {
        return $this->handleTryCatch(function () use ($request) {
            $user = $request->user();
            $family = Family::where('user_id', $user->id)->first();

            return $this->successResponse($family, 'Family profile retrieved successfully');
        });
    }

    // Delete family profile
    public function deleteFamily(Request $request)
    {
        return $this->handleTryCatch(function () use ($request) {
            $user = $request->user();
            $family = Family::where('user_id', $user->id)->firstOrFail();
            $family->delete();

            return $this->successResponse(null, 'Family profile deleted successfully.');
        });
    }

    // Get complete profile (user + family + students)
    public function getProfile(Request $request)
    {
        return $this->handleTryCatch(function () use ($request) {
            $user = $request->user();
            $family = Family::where('user_id', $user->id)->with('students')->first();

            return $this->successResponse([
                'user' => $user,
                'family' => $family,
                'students' => $family ? $family->students : [],
            ], 'Profile retrieved successfully');
        });
    }
}
