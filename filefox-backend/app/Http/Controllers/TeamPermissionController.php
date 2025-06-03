<?php

namespace App\Http\Controllers;

use App\Models\Folders;
use App\Models\TeamPermission;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class TeamPermissionController extends Controller
{
    public function getTeamsWithPermission(Request $request)
    {
        $validated = $request->validate([
            'folder_id'   => 'nullable|integer|exists:folders,id',
            'document_id' => 'nullable|integer|exists:documents,id',
        ]);

        $hasFolderId = array_key_exists('folder_id', $validated);
        $hasDocumentId = array_key_exists('document_id', $validated);

        if (!$hasFolderId && !$hasDocumentId) {
            return response()->json([
                'error' => 'Either folder_id or document_id is required.'
            ], 400);
        }

        $query = TeamPermission::with('team')
            ->when($hasFolderId, fn($q) => $q->where('folder_id', $validated['folder_id']))
            ->when($hasDocumentId, fn($q) => $q->where('document_id', $validated['document_id']));

        $teamPermissions = $query->get()->map(function ($permission) {
            $isFolder = !is_null($permission->folder_id);

            return [
                'team_id' => $permission->team_id,
                'team_name' => $permission->team->team_name,
                'permission' => $permission->permission,
                'item_type' => $isFolder ? 'folder' : 'file',
                'item_id' => $isFolder ? $permission->folder_id : $permission->document_id,
            ];
        });

        return response()->json($teamPermissions);
    }

    public function updateTeamPermissions(Request $request)
    {
        $validated = $request->validate([
            'item_type'   => 'required|in:folder,file',
            'item_id'     => 'required|integer',
            'granted_by'  => 'required|exists:users,id',
            'teams'       => 'required|array',
            'teams.*.team_id'    => 'required|integer|exists:teams,id',
            'teams.*.permission' => 'required|in:viewer,editor,remove',
        ]);

        $type = $validated['item_type'];
        $id = $validated['item_id'];
        $incomingTeams = collect($validated['teams']);

        $existingPermissions = TeamPermission::where(function ($q) use ($type, $id) {
                if ($type === 'folder') {
                    $q->where('folder_id', $id);
                } else {
                    $q->where('document_id', $id);
                }
            })
            ->get()
            ->keyBy('team_id');

        foreach ($incomingTeams as $teamData) {
            $teamId = $teamData['team_id'];
            $newPermission = $teamData['permission'];

            $existing = $existingPermissions->get($teamId);

            if (!$existing) {
                continue;
            }

            if ($newPermission === 'remove') {
                $this->deleteTeamPermissionRecursively($type, $id, $teamId);
            } elseif ($existing->permission !== $newPermission) {
                $this->updateTeamPermissionRecursively($type, $id, $teamId, $newPermission);
            }
        }

        return response()->json(['message' => 'Team permissions updated or removed successfully.', 'response' => 'success']);  
    }

    protected function updateTeamPermissionRecursively($type, $id, $teamId, $newPermission)
    {
        if ($type === 'folder') {
            $folder = Folders::with(['documents', 'children'])->findOrFail($id);

            TeamPermission::where([
                ['folder_id', $folder->id],
                ['team_id', $teamId],
            ])->update(['permission' => $newPermission]);

            foreach ($folder->documents as $document) {
                TeamPermission::where([
                    ['document_id', $document->id],
                    ['team_id', $teamId],
                ])->update(['permission' => $newPermission]);
            }

            foreach ($folder->children as $subfolder) {
                $this->updateTeamPermissionRecursively('folder', $subfolder->id, $teamId, $newPermission);
            }
        } else {
            TeamPermission::where([
                ['document_id', $id],
                ['team_id', $teamId],
            ])->update(['permission' => $newPermission]);
        }
    }

    protected function deleteTeamPermissionRecursively($type, $id, $teamId)
    {
        if ($type === 'folder') {
            $folder = Folders::with(['documents', 'children'])->findOrFail($id);

            TeamPermission::where([
                ['folder_id', $folder->id],
                ['team_id', $teamId],
            ])->delete();

            foreach ($folder->documents as $document) {
                TeamPermission::where([
                    ['document_id', $document->id],
                    ['team_id', $teamId],
                ])->delete();
            }

            foreach ($folder->children as $subfolder) {
                $this->deleteTeamPermissionRecursively('folder', $subfolder->id, $teamId);
            }
        } else {
            TeamPermission::where([
                ['document_id', $id],
                ['team_id', $teamId],
            ])->delete();
        }
    }

    public function shareFileToTeam(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required|integer|exists:documents,id',
            'team_id'     => 'required|integer|exists:teams,id',
            'granted_by'  => 'required|integer|exists:users,id',
            'permission'  => 'required|string|in:viewer,editor',
        ]);

        try {
            $existing = TeamPermission::where('document_id', $validated['document_id'])
                ->where('team_id', $validated['team_id'])
                ->first();

            if ($existing) {
                return response()->json([
                    'response' => 'already_shared',
                    'message'  => 'This team already has permission for this document.',
                ]);
            }

            TeamPermission::create([
                'team_id'     => $validated['team_id'],
                'document_id' => $validated['document_id'],
                'permission'  => $validated['permission'],
                'granted_by'  => $validated['granted_by'],
            ]);

            return response()->json([
                'response' => 'success',
                'message'  => 'Permission successfully assigned to team.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'message'  => 'Server error: ' . $e->getMessage(),
            ]);
        }
    }

}
