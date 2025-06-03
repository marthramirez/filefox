<?php

namespace App\Http\Controllers;

use App\Models\Documents;
use App\Models\Folders;
use App\Models\Permission;
use App\Models\TeamPermission;
use Illuminate\Support\Facades\Validator;
use App\Models\Teams;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FolderController extends Controller
{
    public function getUserFolders(Request $request)
    {
        try {
            $data = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $roots = Folders::with('user:id,email')
                ->where('user_id', $data['user_id'])
                ->whereNull('parent_id')
                ->whereNull('deleted_at')
                ->orderBy('folder_name', 'asc')
                ->get();

            $orphans = Folders::with('user:id,email')
                ->where('user_id', $data['user_id'])
                ->whereNotNull('parent_id')
                ->whereNull('deleted_at')
                ->whereHas('parent', function ($q) {
                    $q->onlyTrashed();
                })
                ->orderBy('folder_name', 'asc')
                ->get();

            $folders = $roots->merge($orphans)->map(function ($folder) {
                return [
                    'id' => $folder->id,
                    'folder_name' => $folder->folder_name,
                    'user_id' => $folder->user_id,
                    'parent_id' => $folder->parent_id,
                    'owner_email' => optional($folder->user)->email,
                    'created_at' => $folder->created_at,
                    'updated_at' => $folder->updated_at,
                ];
            });

            return response()->json([
                'folders' => $folders->values(), 
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve folders',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

   public function getSubfolders($folder_id)
{
    if (!is_numeric($folder_id) || !Folders::where('id', $folder_id)->exists()) {
        return response()->json(['error' => 'Invalid folder ID'], 422);
    }

    $folder = Folders::withTrashed()->findOrFail($folder_id);

    $subfolders = Folders::with('user:id,email')
        ->where('parent_id', $folder->id)
        ->whereNull('deleted_at') 
        ->orderBy('folder_name', 'asc')
        ->get()
        ->map(function ($subfolder) {
            return [
                'type' => 'folder',
                'id' => $subfolder->id,
                'folder_name' => $subfolder->folder_name,
                'owner_email' => optional($subfolder->user)->email,
            ];
        });

    return response()->json([
        'folder_id' => $folder->id,
        'folder_name' => $folder->folder_name,
        'subfolders' => $subfolders,
    ]);
}

    public function getFolderDocuments($id)
    {
        $folder = Folders::findOrFail($id);

        $documents = $folder->documents()
            ->orderBy('title', 'asc')
            ->get()
            ->map(function ($doc) {
                return [
                    'type' => 'document',
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'description' => $doc->description,
                    'file_name' => $doc->file_name,
                    'file_path' => $doc->file_path,
                ];
            });

        return response()->json([
            'folder_id' => $folder->id,
            'folder_name' => $folder->folder_name,
            'documents' => $documents,
        ]);
    }


public function getTrashedSubfolders(Request $request)
{
    $validated = $request->validate([
        'parent_id' => 'required|integer|exists:folders,id',
    ]);

    $folderId = $validated['parent_id'];

    try {
        $folder = Folders::withTrashed()->findOrFail($folderId);

        $trashedSubfolders = $folder->children()
            ->onlyTrashed()
            ->orderBy('folder_name', 'asc')
            ->get()
            ->map(function ($subfolder) {
                return [
                    'id' => $subfolder->id,
                    'folder_name' => $subfolder->folder_name,
                    'type' => 'folder',
                ];
            });

        return response()->json([
            'response' => 'success',
            'subfolders' => $trashedSubfolders,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'response' => 'error',
            'message' => 'Server error: ' . $e->getMessage(),
        ], 500);
    }
}


    public function createFolder(Request $request)
    {
        $validated = $request->validate([
            'folder_name' => 'required|string|max:150',
            'parent_id' => 'nullable|exists:folders,id',
            'user_id'=> 'required|integer|exists:users,id',
        ]);

        $folder = Folders::create([
            'folder_name' => $validated['folder_name'],
            'user_id' => $validated['user_id'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return response()->json([
            'response' => 'success',
            'folder' => $folder
        ], 201);
    }
    
    public function renameFolder(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'folder_id' => 'required|integer|exists:folders,id',
                'folder_name' => 'required|string|max:100',
                'user_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'response' => 'validation_error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            $folder = Folders::where('id', $validated['folder_id'])
                            ->firstOrFail();

            $folder->folder_name = $validated['folder_name'];
            $folder->save();

            return response()->json([
                'response' => 'success',
                'folder' => $folder,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function moveFolderToTrash($id)
    {
        $folder = Folders::findOrFail($id);

        $this->softDeleteSubfolders($folder);

        $folder->documents()->delete();

        $folder->delete();

        return response()->json(['message' => 'Folder and all its contents moved to trash', 'response' => 'success']); 
    }

    protected function softDeleteSubfolders(Folders $folder)
    {
        $subfolders = $folder->children;

        foreach ($subfolders as $subfolder) {
            $this->softDeleteSubfolders($subfolder);

            $subfolder->documents()->delete();

            $subfolder->delete();
        }
    }

    public function restoreFolderFromTrash(Request $request)
    {
        $validated = $request->validate([
            'folder_id' => 'required|integer|exists:folders,id',
        ]);

        try {
            $folder = Folders::onlyTrashed()->findOrFail($validated['folder_id']);

            $this->restoreSubfolders($folder);

            return response()->json([
                'message' => 'Folder and its contents restored successfully',
                'response' => 'success',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'response' => 'failed',
            ], 500);
        }
    }

    protected function restoreSubfolders(Folders $folder)
    {
        $folder->restore();

        $folder->documents()->onlyTrashed()->restore();

        $subfolders = $folder->children()->onlyTrashed()->get();

        foreach ($subfolders as $subfolder) {
            $this->restoreSubfolders($subfolder);
        }
    }

    public function getTrashedFolders(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $trashedFolders = Folders::onlyTrashed()
                ->where('user_id', $validated['user_id'])
                ->where(function ($query) {
                    $query->whereNull('parent_id')
                        ->orWhereHas('parent', function ($parentQuery) {
                            $parentQuery->whereNull('deleted_at');
                        });
                })
                ->orderBy('folder_name', 'asc')
                ->get();

            return response()->json([
                'folders' => $trashedFolders,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve folders',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteFolder(int $folderId)
    {
        $folder = Folders::onlyTrashed()->findOrFail($folderId);

        $this->forceDeleteSubfolders($folder);

        $folder->documents()->onlyTrashed()->forceDelete();

        $folder->forceDelete();

        return response()->json(['message' => 'Folder and all its contents permanently deleted', 'response' => 'success']);
    }

    protected function forceDeleteSubfolders(Folders $folder)
    {
        $subfolders = $folder->children()->onlyTrashed()->get();

        foreach ($subfolders as $subfolder) {
            $this->forceDeleteSubfolders($subfolder);

            $subfolder->documents()->onlyTrashed()->forceDelete();

            $subfolder->forceDelete();
        }
    }

    public function shareFolderWithPerson(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'email'      => 'required|email',
            'owner_id'   => 'required|exists:users,id',
            'permission' => 'required|in:viewer,editor',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'response' => 'unregistered',
                'message'  => 'User is not registered.',
            ], 404);
        }

        if ($user->id === (int) $validated['owner_id']) {
            return response()->json([
                'response' => 'owner',
                'message'  => 'Sorry, you cannot share a folder with yourself.',
            ], 200);
        }

        $folder = Folders::findOrFail($id);

        $permissionAdded = $this->addPermissionRecursively(
            $folder,
            $user->id,
            $validated['owner_id'],
            $validated['permission']
        );

        if (! $permissionAdded) {
            return response()->json([
                'response' => 'already_shared',
                'message'  => 'User already has permission for this folder.',
            ], 200);
        }

        return response()->json([
            'response' => 'success',
            'message'  => 'Permission assigned successfully.',
        ], 200);

    } catch (ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        return response()->json([
            'response' => 'error',
            'message'  => 'Failed to add permission.',
            'details'  => $e->getMessage(),
        ], 500);
    }
}

    protected function addPermissionRecursively(Folders $folder, $userId, $grantedBy, $permission)
    {
        $permissionAdded = false;

        $alreadyHasPermission = $folder->permissions()
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyHasPermission) {
            $permissionAdded = true;
            return false; 
        }

        $folder->permissions()->create([
            'user_id'    => $userId,
            'granted_by' => $grantedBy,
            'permission' => $permission,
        ]);
        $permissionAdded = true;

        foreach ($folder->documents as $document) {
            $document->permissions()->create([
                'user_id'    => $userId,
                'granted_by' => $grantedBy,
                'permission' => $permission,
            ]);
        }

        foreach ($folder->children as $subfolder) {
            $this->addPermissionRecursively($subfolder, $userId, $grantedBy, $permission);
        }

        return $permissionAdded;
    }
    
    public function shareFolderWithTeam(Request $request, $folderId)
    {
        $request->validate([
            'team_id'    => 'required|exists:teams,id',
            'granted_by' => 'required|exists:users,id',
            'permission' => 'required|in:viewer,editor',
        ]);

        try {
            $folder = Folders::findOrFail($folderId);

            $teamId     = $request->team_id;
            $grantedBy  = $request->granted_by;
            $permission = $request->permission;

            $permissionAdded = $this->shareFolderAndContentsWithTeam($folder, $teamId, $grantedBy, $permission);

            if (! $permissionAdded) {
                return response()->json(['response' => 'already_shared'], 200);
            }

            return response()->json(['response' => 'success'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Failed to share folder with team',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    protected function shareFolderAndContentsWithTeam(Folders $folder, int $teamId, int $grantedBy, string $permission): bool
    {
        $permissionAdded = false;

        $existingFolderPermission = \App\Models\TeamPermission::where('team_id', $teamId)
            ->where('folder_id', $folder->id)
            ->exists();

        if (! $existingFolderPermission) {
            TeamPermission::create([
                'team_id'    => $teamId,
                'folder_id'  => $folder->id,
                'permission' => $permission,
                'granted_by' => $grantedBy,
            ]);
            $permissionAdded = true;
        }

        foreach ($folder->documents as $document) {
            $existingDocPermission = TeamPermission::where('team_id', $teamId)
                ->where('document_id', $document->id)
                ->exists();

            if (! $existingDocPermission) {
                TeamPermission::create([
                    'team_id'     => $teamId,
                    'document_id' => $document->id,
                    'permission'  => $permission,
                    'granted_by'  => $grantedBy,
                ]);
                $permissionAdded = true;
            }
        }

        foreach ($folder->children as $subfolder) {
            if ($this->shareFolderAndContentsWithTeam($subfolder, $teamId, $grantedBy, $permission)) {
                $permissionAdded = true;
            }
        }

        return $permissionAdded;
    }

   public function getFoldersSharedToUser($userId)
    {
        try {
            $sharedPermissions = Permission::with(['permissionable', 'grantedBy'])
                ->where('user_id', $userId)
                ->where('permissionable_type', 'folder')
                ->get();

            $sharedFolderIds = $sharedPermissions->pluck('permissionable_id')->toArray();

            $topLevelFolders = $sharedPermissions->filter(function ($permission) use ($sharedFolderIds) {
                $folder = $permission->permissionable;

                if (!$folder instanceof Folders || $folder->is_trashed) {
                    return false;
                }

                return !in_array($folder->parent_id, $sharedFolderIds);
            })->map(function ($permission) {
                $folder = $permission->permissionable;
                $owner = $permission->grantedBy;

                return [
                    'id' => $folder->id,
                    'folder_name' => $folder->folder_name,
                    'granted_by' => $owner->id,
                    'owner_email' => $owner->email,
                    'permission' => $permission->permission
                ];
            })->sortBy('folder_name', SORT_NATURAL | SORT_FLAG_CASE)->values();

            return response()->json([
                'response' => 'success',
                'folders' => $topLevelFolders,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getSharedSubfolders(Request $request)
    {
        $validated = $request->validate([
            'folder_id' => 'required|integer|exists:folders,id',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $parentId = $validated['folder_id'];
        $userId = $validated['user_id'];

        try {
            $permissions = Permission::with(['permissionable', 'grantedBy'])
                ->where('user_id', $userId)
                ->where('permissionable_type', 'folder')
                ->get();

            $permittedFolderIds = $permissions->pluck('permissionable_id')->toArray();

            $subfolders = Folders::with('owner') 
                ->where('parent_id', $parentId)
                ->whereIn('id', $permittedFolderIds)
                ->whereNull('deleted_at')
                ->get()
                ->map(function ($folder) use ($permissions) {
                    $permission = $permissions->firstWhere('permissionable_id', $folder->id);

                    return [
                        'id' => $folder->id,
                        'folder_name' => $folder->folder_name,
                        'granted_by' => $permission?->grantedBy?->id,
                        'permission' => $permission?->permission,
                        'owner_email' => $folder->owner?->email,
                    ];
                })
                ->sortBy('folder_name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            return response()->json([
                'response' => 'success',
                'subfolders' => $subfolders,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

 public function createSharedFolder(Request $request)
{
    $validated = $request->validate([
        'folder_name'     => 'required|string|max:150',
        'parent_id'       => 'required|exists:folders,id',
        'creator_id'      => 'required|exists:users,id', 
        'owner_id' => 'required|exists:users,id', 
        'permission'      => 'required|in:viewer,editor',
    ]);

    $folder = Folders::create([
        'folder_name' => $validated['folder_name'],
        'user_id'     => $validated['creator_id'],
        'parent_id'   => $validated['parent_id'],
    ]);

    $permissionAdded = $this->addPermissionRecursively(
        $folder,
        $validated['owner_id'],  
        $validated['creator_id'],       
        $validated['permission']
    );

    return response()->json([
        'response'         => 'success',
        'folder'           => $folder,
        'permission_status'=> $permissionAdded ? 'granted' : 'already_shared'
    ], 201);
}

}
