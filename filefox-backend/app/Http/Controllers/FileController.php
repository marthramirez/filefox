<?php

namespace App\Http\Controllers;

use App\Models\Documents;
use App\Models\Folders;
use App\Models\Permission;
use App\Models\TeamPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;


use Illuminate\Validation\ValidationException;

class FileController extends Controller
{
    public function fetchUserFiles(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);
        
        $documents = Documents::with(['folder:id,folder_name', 'user:id,email'])
            ->where('uploaded_by', $request->user_id)
            ->where('is_archived', false)
            ->orderBy('created_at', 'desc')
            ->get([
                'id',
                'title',
                'file_name',
                'file_type',
                'file_size',
                'folder_id',
                'created_at',
                'uploaded_by' 
            ]);

        $result = $documents->map(function ($doc) {
            $size = (int) $doc->file_size;
            $formattedSize = $size >= 1073741824 ? number_format($size / 1073741824, 2) . ' GB' :
                            ($size >= 1048576 ? number_format($size / 1048576, 2) . ' MB' :
                            ($size >= 1024 ? number_format($size / 1024, 2) . ' KB' :
                            ($size > 1 ? $size . ' bytes' : ($size == 1 ? '1 byte' : '0 bytes'))));

            return [
                'id' => $doc->id,
                'title' => $doc->title,
                'file_name' => $doc->file_name,
                'file_type' => $doc->file_type,
                'file_size' => $formattedSize,
                'folder_id' => $doc->folder_id,
                'folder_name' => optional($doc->folder)->folder_name ?? '--',
                'created_at' => $doc->created_at->format('m/d/Y'),
                'owner_email' => optional($doc->user)->email,  
                'item_type' => 'file',
            ];
        });

        return response()->json([
            'response' => 'success',
            'documents' => $result,
        ]);
    }

    public function uploadFile(Request $request)
    {
        try {
            $validated = $request->validate([
                'uploaded_by' => 'required|integer|exists:users,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'file' => 'required|file',
                'folder_id' => 'nullable|integer|exists:folders,id',
            ]);

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileExtension = $file->getClientOriginalExtension();
            $fileSize = $file->getSize();
            $generatedName = Str::random(20) . '.' . $fileExtension;

            $path = $file->storeAs('documents', $generatedName, 'public');

            $document = Documents::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'file_name' => $originalName,
                'file_path' => $path, 
                'file_type' => $fileExtension,
                'file_size' => $fileSize,
                'uploaded_by' => $validated['uploaded_by'],
                'folder_id' => $validated['folder_id'] ?? null,
            ]);

            return response()->json([
                'message' => 'File uploaded successfully',
                'document' => $document,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function fetchDocumentDetails($id)
    {
        $document = Documents::findOrFail($id);
        return response()->json($document);
    }

    public function updateFile(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'file' => 'nullable|file',
            ]);

            $document = Documents::findOrFail($id);

            if ($request->hasFile('file')) {
                if (Storage::disk('private_public')->exists(str_replace('private/public/', '', $document->file_path))) {
                    Storage::disk('private_public')->delete(str_replace('private/public/', '', $document->file_path));
                }

                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $size = $file->getSize();
                $generatedName = Str::random(20) . '.' . $extension;
                $path = $file->storeAs('documents', $generatedName, 'public');

                $document->file_name = $originalName;
                $document->file_path = $path;
                $document->file_type = $extension;
                $document->file_size = $size;
            }


            $document->title = $validated['title'];
            $document->description = $validated['description'];
            $document->save();

            return response()->json([
                'message' => 'Document updated successfully',
                'document' => $document,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e], 500);
        }
    }

    public function viewPdf($id)
    {
        $document = Documents::withTrashed()->findOrFail($id); 

        if (strtolower($document->file_type) !== 'pdf') {
            return response()->json(['error' => 'Only PDF preview supported'], 400);
        }

        $path = $document->file_path;

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $file = Storage::disk('public')->get($path);
        $mime = Storage::disk('public')->mimeType($path);

        return response($file)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', 'inline; filename="' . $document->file_name . '"');
    }

    public function downloadDocx($id)
    {
        $document = Documents::withTrashed()->findOrFail($id); 

        $path = $document->file_path;

        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return Storage::disk('public')->download($path, $document->file_name);
    }


    public function fileDownload($id)
    {
        $document = Documents::findOrFail($id);
       $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $document->file_path));


        if (!file_exists($fullPath)) {
            return response()->json([
                'error' => 'File not found.',
                'file_path' => $fullPath,
                'stored_path' => $document->file_path
            ], 404);
        }

        return response()->download($fullPath, $document->file_name, [
            'Content-Type' => mime_content_type($fullPath),
            'Content-Disposition' => 'attachment; filename="' . $document->file_name . '"',
        ]);
    }

    public function getTrashedFiles(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $userId = $request->input('user_id');

        $documents = Documents::onlyTrashed()
            ->where('uploaded_by', $userId)
            ->where('is_archived', false)
            ->where(function ($query) {
                $query->whereNull('folder_id') 
                    ->orWhereHas('folder', function ($folderQuery) {
                        $folderQuery->whereNull('deleted_at'); 
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get([
                'id',
                'title',
                'file_name',
                'file_type',
                'file_size',
                'folder_id',
                'created_at'
            ]);

        $result = $documents->map(function ($doc) {
            return [
                'id' => $doc->id,
                'title' => $doc->title,
                'file_name' => $doc->file_name,
                'file_type' => $doc->file_type,
                'file_size' => $doc->file_size,
                'folder_id' => $doc->folder_id,
                'folder_name' => $doc->folder ? $doc->folder->folder_name : '--',
                'created_at' => $doc->created_at->format('m/d/Y'),
                'item_type' => 'file',
            ];
        });

        return response()->json([
            'response' => 'success',
            'documents' => $result,
        ]);
    }

    public function moveFileToTrash($id)
    {
        try {
            $document = Documents::findOrFail($id);

            $document->delete();

            return response()->json([
                'message' => 'Document moved to trash successfully',
                'response' => 'success',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }
    }
    public function restoreFileFromTrash(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required|integer|exists:documents,id',
        ]);

        try {
            $document = Documents::onlyTrashed()->findOrFail($validated['document_id']);

            $document->restore();

            return response()->json([
                'message' => 'Document restored successfully',
                'response' => 'success',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'response' => 'failed',
            ], 500);
        }
    }

    public function deleteFile($id)
    {
        try {
            $document = Documents::withTrashed()->findOrFail($id);

            if (Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }

            $document->forceDelete();

            return response()->json([
                'message' => 'Document permanently deleted successfully',
                'response' => 'success',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function getFolderFiles($id)
{
    $folder = Folders::with('user:id,email')->findOrFail($id);

    $documentType = 'file';

    $documents = Documents::with('user:id,email')
        ->where('folder_id', $id)
        ->orderBy('title', 'asc')
        ->get()
        ->map(function ($doc) {
            return [
                'id' => $doc->id,
                'title' => $doc->title,
                'file_name' => $doc->file_name,
                'file_path' => $doc->file_path,
                'file_type' => $doc->file_type,
                'owner_email' => optional($doc->user)->email,
            ];
        });

    return response()->json([
        'folder_id' => $folder->id,
        'folder_name' => $folder->folder_name,
        'owner_email' => optional($folder->user)->email,  
        'documents' => $documents,
        'item_type' => $documentType,
    ]);
}

    public function getTeamSharedFolders(Request $request)
    {
        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
        ]);

        try {
            $teamId = $validated['team_id'];

            $permissions = TeamPermission::where('team_id', $teamId)
                ->whereNotNull('folder_id')
                ->with('folder') 
                ->get();

            $sharedFolderIds = $permissions
                ->pluck('folder_id')
                ->filter() 
                ->toArray();

            $folders = $permissions
                ->filter(function ($perm) use ($sharedFolderIds) {
                    $folder = $perm->folder;
                    if (!$folder) return false;

                    if (is_null($folder->parent_id)) return true;

                    return !in_array($folder->parent_id, $sharedFolderIds);
                })
                ->sortBy(fn($perm) => $perm->folder->folder_name)
                ->values()
                ->map(function ($perm) {
                    return [
                        'id'          => $perm->folder->id,
                        'folder_name' => $perm->folder->folder_name,
                        'parent_id'   => null, 
                        'permission'  => $perm->permission,
                        'granted_by'  => $perm->granted_by,
                        'created_at'  => $perm->folder->created_at,
                    ];
                });

            return response()->json([
                'response' => 'success',
                'folders'  => $folders,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'message'  => 'Failed to fetch shared folders.',
                'details'  => $e->getMessage(),
            ], 500);
        }
    }


     public function getSharedDocuments(Request $request)
        {
            $validated = $request->validate([
                'user_id' => ['required', 'integer', 'exists:users,id'],
            ]);

            $userId = $validated['user_id'];


        $permissionableIds = \App\Models\Permission::where('user_id', $userId)
        ->where('permissionable_type', \App\Models\Documents::class)
        ->pluck('permissionable_id')
        ->toArray();


            $documents = \App\Models\Documents::whereIn('id', $permissionableIds)->get();

            return response()->json([
                'documents' => $documents   ,
            ]);
        }

    public function getUserSharedFiles($userId)
    {
        try {
            $sharedFilePermissions = Permission::with(['permissionable', 'grantedBy'])
                ->where('user_id', $userId)
                ->where('permissionable_type', 'file')
                ->get();

            $sharedFolderIds = Permission::where('user_id', $userId)
                ->where('permissionable_type', 'folder')
                ->pluck('permissionable_id')
                ->toArray();

            $sharedDocuments = $sharedFilePermissions->filter(function ($permission) use ($sharedFolderIds) {
                $file = $permission->permissionable;

                if (!$file instanceof Documents || $file->is_trashed) {
                    return false;
                }

                return !in_array($file->folder_id, $sharedFolderIds);
            })->map(function ($permission) {
                $doc = $permission->permissionable;
                $owner = $permission->grantedBy;

                return [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'description' => $doc->description ?? null,
                    'file_type' => $doc->file_type,
                    'file_path' => $doc->file_path,
                    'file_name' => $doc->file_name,
                    'granted_by' => $owner->id,
                    'owner_email' => $owner->email,
                    'permission' => $permission->permission,
                ];
            })->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE)->values(); 

            return response()->json([
                'response' => 'success',
                'documents' => $sharedDocuments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getSharedFolderFiles(Request $request)
{
    $validated = $request->validate([
        'folder_id' => 'required|integer|exists:folders,id',
        'user_id' => 'required|integer|exists:users,id',
    ]);

    $folderId = $validated['folder_id'];
    $userId = $validated['user_id'];

    try {
        $filePermissions = Permission::with('grantedBy')
            ->where('user_id', $userId)
            ->where('permissionable_type', 'file')
            ->get();

        $permittedFileIds = $filePermissions->pluck('permissionable_id')->toArray();

        $sharedFiles = Documents::with('owner') 
            ->where('folder_id', $folderId)
            ->whereIn('id', $permittedFileIds)
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($doc) use ($filePermissions) {
                $permission = $filePermissions->firstWhere('permissionable_id', $doc->id);

                return [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'description' => $doc->description ?? null,
                    'file_type' => $doc->file_type,
                    'file_path' => $doc->file_path,
                    'file_name' => $doc->file_name,
                    'granted_by' => $permission?->grantedBy?->id ?? null,
                    'permission' => $permission?->permission ?? null,
                    'owner_email' => $doc->owner?->email, 
                ];
            })
            ->sortBy('file_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json([
            'response' => 'success',
            'documents' => $sharedFiles,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'response' => 'error',
            'message' => 'Server error: ' . $e->getMessage(),
        ], 500);
    }
}

public function getTrashedFolderFiles(Request $request)
{
    $request->validate([
        'user_id' => 'required|integer|exists:users,id',
        'parent_id' => 'required|integer|exists:folders,id',
    ]);

    $userId = $request->input('user_id');
    $parentId = $request->input('parent_id');

    $documents = Documents::onlyTrashed()
        ->where('uploaded_by', $userId)
        ->where('is_archived', false)
        ->where('folder_id', $parentId)
        ->whereHas('folder', function ($query) {
            $query->onlyTrashed(); 
        })
        ->with([
            'folder:id,folder_name,deleted_at', 
            'user:id,email'
        ])
        ->orderBy('created_at', 'desc')
        ->get([
            'id',
            'title',
            'file_type',
            'file_size',
            'folder_id',
            'file_name',
            'uploaded_by',
        ]);

    $result = $documents->map(function ($doc) {
        return [
            'id' => $doc->id,
            'title' => $doc->title,
            'file_name' => $doc->file_name,
            'file_type' => $doc->file_type,
            'file_size' => $doc->file_size,
            'folder_id' => $doc->folder_id,
            'folder_name' => optional($doc->folder)->folder_name ?? '--',
            'owner_email' => optional($doc->user)->email ?? 'N/A',
            'item_type' => 'file',
        ];
    });

    return response()->json([
        'response' => 'success',
        'documents' => $result,
    ]);
}

}
