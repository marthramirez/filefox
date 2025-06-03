<?php

namespace App\Http\Controllers;

use App\Models\Documents;
use App\Models\Folders;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getDashboardData(Request $request)
    {
        $user = $request->user(); 
        $userId = $user->id;

        $uploadedDocumentsCount = Documents::where('uploaded_by', $userId)
            ->whereNull('deleted_at')
            ->count();

        $pendingInvitesCount = DB::table('team_members')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->count();

        $acceptedTeamsCount = DB::table('team_members')
            ->where('user_id', $userId)
            ->where('status', 'accepted')
            ->distinct()
            ->count('team_id');

        $trashedFileCount = Documents::onlyTrashed()
            ->where('uploaded_by', $userId)
            ->whereNull('folder_id')
            ->count();

        $trashedFolderCount = Folders::onlyTrashed()
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->count();

        $totalTrashedItemsCount = $trashedFileCount + $trashedFolderCount;

        $folderPermissions = Permission::with('permissionable')
            ->where('user_id', $userId)
            ->where('permissionable_type', 'folder')
            ->get();

        $sharedFolderIds = $folderPermissions->pluck('permissionable_id')->toArray();

        $topLevelSharedFoldersCount = $folderPermissions->filter(function ($permission) use ($sharedFolderIds) {
            $folder = $permission->permissionable;
            return $folder instanceof Folders &&
                !$folder->is_trashed &&
                !in_array($folder->parent_id, $sharedFolderIds);
        })->count();

        $filePermissions = Permission::with('permissionable')
            ->where('user_id', $userId)
            ->where('permissionable_type', 'file')
            ->get();

        $topLevelSharedFilesCount = $filePermissions->filter(function ($permission) use ($sharedFolderIds) {
            $file = $permission->permissionable;
            return $file instanceof Documents &&
                !$file->is_trashed &&
                !in_array($file->folder_id, $sharedFolderIds);
        })->count();

        $totalSharedItemsCount = $topLevelSharedFoldersCount + $topLevelSharedFilesCount;

        return response()->json([
            'uploaded_documents' => $uploadedDocumentsCount,
            'pending_invites' => $pendingInvitesCount,
            'accepted_teams' => $acceptedTeamsCount,
            'trashed_items' => $totalTrashedItemsCount,
            'shared_items' => $totalSharedItemsCount,
        ]);
    }

}

