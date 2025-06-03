<?php

namespace App\Http\Controllers;

use App\Models\Documents;
use App\Models\Folders;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function getUserPermissions(Request $request)
    {
        $validated = $request->validate([
            'folder_id' => 'nullable|integer|exists:folders,id',
            'document_id' => 'nullable|integer|exists:documents,id',
        ]);

        $hasFolderId = array_key_exists('folder_id', $validated);
        $hasDocumentId = array_key_exists('document_id', $validated);

        if (!$hasFolderId && !$hasDocumentId) {
            return response()->json([
                'error' => 'Either folder_id or document_id is required.'
            ], 400);
        }

        if ($hasFolderId) {
            $permissionableType = 'folder'; 
            $permissionableId = $validated['folder_id'];
        } else {
            $permissionableType = 'file';    
            $permissionableId = $validated['document_id'];
        }

        $permissions = Permission::with('user')
            ->where('permissionable_id', $permissionableId)
            ->where('permissionable_type', $permissionableType)
            ->get()
            ->map(function ($permission) {
                return [
                    'user_id'    => $permission->user_id,
                    'email'      => $permission->user->email ?? null,
                    'permission' => $permission->permission,
                    'permissionable_id' => $permission->permissionable_id,
                    'permissionable_type' => $permission->permissionable_type,
                ];
            });

        return response()->json($permissions);
    }

    public function updateUserPermissions(Request $request)
    {
        $validated = $request->validate([
            'permissionable_type' => 'required|in:folder,file',
            'permissionable_id' => 'required|integer',
            'granted_by' => 'required|exists:users,id',
            'users' => 'required|array',
            'users.*.user_id' => 'required|integer|exists:users,id',
            'users.*.permission' => 'required|in:viewer,editor,remove',
        ]);

        $type = $validated['permissionable_type'];
        $id = $validated['permissionable_id'];
        $incomingUsers = collect($validated['users']);

        $existingPermissions = Permission::where('permissionable_type', $type)
            ->where('permissionable_id', $id)
            ->get()
            ->keyBy('user_id'); 

        foreach ($incomingUsers as $userData) {
            $userId = $userData['user_id'];
            $newPermission = $userData['permission'];

            $existing = $existingPermissions->get($userId);

            if (!$existing) {
                continue;
            }

            if ($newPermission === 'remove') {
                $this->deletePermissionRecursively($type, $id, $userId);
            } else {
                if ($existing->permission !== $newPermission) {
                    $this->updatePermissionRecursively($type, $id, $userId, $newPermission);
                }
            }
        }

        return response()->json(['message' => 'Permissions updated or removed successfully.', 'response' => 'success']);
    }

    protected function updatePermissionRecursively($type, $id, $userId, $newPermission)
    {
        if ($type === 'folder') {
            $folder = Folders::with(['documents', 'children'])->findOrFail($id);

            Permission::where([
                ['permissionable_type', 'folder'],
                ['permissionable_id', $folder->id],
                ['user_id', $userId],
            ])->update(['permission' => $newPermission]);

            foreach ($folder->documents as $document) {
                Permission::where([
                    ['permissionable_type', 'file'],
                    ['permissionable_id', $document->id],
                    ['user_id', $userId],
                ])->update(['permission' => $newPermission]);
            }

            foreach ($folder->children as $subfolder) {
                $this->updatePermissionRecursively('folder', $subfolder->id, $userId, $newPermission);
            }
        } else {
            Permission::where([
                ['permissionable_type', 'file'],
                ['permissionable_id', $id],
                ['user_id', $userId],
            ])->update(['permission' => $newPermission]);
        }
    }

    protected function deletePermissionRecursively($type, $id, $userId)
    {
        if ($type === 'folder') {
            $folder = Folders::with(['documents', 'children'])->findOrFail($id);

            Permission::where([
                ['permissionable_type', 'folder'],
                ['permissionable_id', $folder->id],
                ['user_id', $userId],
            ])->delete();

            foreach ($folder->documents as $document) {
                Permission::where([
                    ['permissionable_type', 'file'],
                    ['permissionable_id', $document->id],
                    ['user_id', $userId],
                ])->delete();
            }

            foreach ($folder->children as $subfolder) {
                $this->deletePermissionRecursively('folder', $subfolder->id, $userId);
            }
        } else {
            Permission::where([
                ['permissionable_type', 'file'],
                ['permissionable_id', $id],
                ['user_id', $userId],
            ])->delete();
        }
    }

    public function shareFileToPerson(Request $request)
{
    $validated = $request->validate([
        'document_id' => 'required|integer|exists:documents,id',
        'email'       => 'required|email',
        'granted_by'  => 'required|integer|exists:users,id',
        'permission'  => 'required|string|in:viewer,editor',
    ]);

    try {
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'response' => 'not_registered',
                'message'  => 'User is not registered.',
            ]);
        }

        if ($user->id === (int) $validated['granted_by']) {
            return response()->json([
                'response' => 'owner',
                'message'  => 'You cannot share a file with yourself.',
            ]);
        }

        $documentType = 'file';

        $existing = Permission::where('permissionable_id', $validated['document_id'])
            ->where('permissionable_type', $documentType)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json([
                'response' => 'already_shared',
                'message'  => 'User already has permission for this document.',
            ]);
        }

        Permission::create([
            'user_id'             => $user->id,
            'granted_by'          => $validated['granted_by'],
            'permission'          => $validated['permission'],
            'permissionable_id'   => $validated['document_id'],
            'permissionable_type' => $documentType,
        ]);

        return response()->json([
            'response' => 'success',
            'message'  => 'Permission assigned successfully.',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'response' => 'error',
            'message'  => 'Server error: ' . $e->getMessage(),
        ]);
    }
}

}
