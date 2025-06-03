<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function getUserInfo(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::select('id', 'fname', 'lname', 'email')
            ->findOrFail($validated['user_id']);

        return response()->json([
            'id' => $user->id,
            'first_name' => $user->fname,
            'last_name' => $user->lname,
            'email' => $user->email,
        ], 200);
    }

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'fname' => 'bail|required|max:50',
                'lname' => 'bail|required|max:50',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
            ]);

            $user = User::create([
                'fname' => $validated['fname'],
                'lname' => $validated['lname'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
            ], 200);

        } catch (ValidationException $e) {
            $errors = $e->validator->errors();
            if ($errors->has('email')) {
                return response()->json([
                    'message' => 'Email is already registered.'
                ], 409);
            }
            return response()->json(['message' => $errors->first()], 422);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Registration failed.'], 500);
        }
    }

    public function login(Request $request)
    {
        try{
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|min:8',
            ]);

            $user = User::where('email', $validated['email'])->first();
            
            if ($user && Hash::check($validated['password'], $user->password)) {
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'user' => $user,
                    'token' => $token,
                    'response' => 'success',
                ]);
                
            } else {
                return response()->json(['error' => 'Invalid credentials']);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Login failed']);
        }
    }

    public function updateAccount(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'fname' => 'required|string|max:50',
            'lname' => 'required|string|max:50',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($request->user()->id !== (int) $validated['user_id']) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $user = $request->user();
        $user->fname = $validated['fname'];
        $user->lname = $validated['lname'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'message' => 'Account updated successfully',
            'reponse' => "success"
        ]);
    }
    
}
