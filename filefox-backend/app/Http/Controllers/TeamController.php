<?php

namespace App\Http\Controllers;

use App\Models\Documents;
use App\Models\Folders;
use App\Models\TeamMember;
use App\Models\TeamPermission;
use App\Models\Teams;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    public function createTeam(Request $request)
    {
        try {
            $request->validate([
                'user_id'    => 'required|integer|exists:users,id',
                'team_name'  => 'required|string|max:150|unique:teams,team_name',
            ]);

            $team = Teams::create([
                'team_name' => $request->team_name,
                'created_by' => $request->user_id,
            ]);

            TeamMember::create([
                'user_id'    => $request->user_id,
                'team_id'    => $team->id,
                'granted_by' => $request->user_id,
                'role'       => 'owner',
                'status'     => 'accepted',
            ]);

            return response()->json([
                'message' => 'Team created successfully.',
                'team'    => $team
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function inviteMember(Request $request)
    {
        $request->validate([
            'team_id'  => 'required|exists:teams,id',
            'email'    => 'required|email',
            'owner_id' => 'required|exists:users,id',
            'role'     => 'required|in:admin,member',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['response' => 'not_registered']);
        }

        $existing = TeamMember::where('team_id', $request->team_id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existing) {
            return response()->json(['response' => 'existing']);
        }

        TeamMember::create([
            'user_id'    => $user->id,
            'team_id'    => $request->team_id,
            'granted_by' => $request->owner_id,
            'role'       => $request->role,
            'status'     => 'pending',
        ]);

        return response()->json(['response' => 'success'], 200);
    }

    public function acceptInvitation(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:team_members,id',
        ]);

        $membership = TeamMember::where('id', $request->invitation_id)
            ->where('status', 'pending')
            ->first();

        if (!$membership) {
            return response()->json(['error' => 'No pending invitation found.'], 404);
        }

        $membership->update(['status' => 'accepted']);

        return response()->json(['message' => 'Invitation accepted.', "response" => "success"]);
    }

    public function rejectInvitation(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:team_members,id',
        ]);

        $membership = TeamMember::where('id', $request->invitation_id)
            ->where('status', 'pending')
            ->first();

        if (!$membership) {
            return response()->json(['error' => 'No pending invitation found.'], 404);
        }

        $membership->update(['status' => 'rejected']);

        return response()->json(['message' => 'Invitation rejected.']);
    }

   public function getUserTeams(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $userId = $request->user_id;

        $teams = DB::table('teams')
            ->leftJoin('team_members as owners', function ($join) {
                $join->on('teams.id', '=', 'owners.team_id')
                    ->where('owners.role', 'owner')
                    ->where('owners.status', 'accepted');
            })
            ->leftJoin('users as owner_users', 'owners.user_id', '=', 'owner_users.id')
            ->where(function ($query) use ($userId) {
                $query->whereIn('teams.id', function ($q) use ($userId) {
                    $q->select('team_id')
                        ->from('team_members')
                        ->where('user_id', $userId)
                        ->where('status', 'accepted');
                });
            })
            ->select(
                'teams.id',
                'teams.team_name',
                'teams.created_by',
                'owners.user_id as owner_id',
                'owner_users.email as owner_email',
                DB::raw('(
                    SELECT COUNT(*) FROM team_members 
                    WHERE team_members.team_id = teams.id 
                    AND team_members.status = "accepted"
                ) AS total_members')
            )
            ->orderBy('teams.team_name', 'asc')
            ->get();

        return response()->json([
            'response' => 'success',
            'teams' => $teams,
        ]);
    }

    public function getTeamMembers(Request $request)
    {
        $request->validate([
            'team_id' => 'required|integer|exists:teams,id',
            'user_id' => 'required|integer|exists:users,id', 
        ]);

        $teamId = $request->team_id;
        $currentUserId = $request->user_id;

        $members = DB::table('team_members')
        ->join('users', 'team_members.user_id', '=', 'users.id')
        ->where('team_members.team_id', $teamId)
        ->where('team_members.status', 'accepted')
        ->select(
            'users.id as user_id',
            'users.email',
            'team_members.role'
        )
        ->orderByRaw("
            CASE 
                WHEN users.id = ? THEN 0         -- Current user first
                WHEN team_members.role = 'owner' THEN 1
                WHEN team_members.role = 'admin' THEN 2
                WHEN team_members.role = 'member' THEN 3
                ELSE 4
            END
        ", [$currentUserId])
        ->get();

        return response()->json([
            'response' => 'success',
            'team_members' => $members,
        ]);
    }

    public function removeMember(Request $request)
    {
        $request->validate([
            'team_id'   => 'required|exists:teams,id',
            'member_id' => 'required|exists:users,id',
            'requester_id' => 'required|exists:users,id', 
        ]);

        $team = Teams::findOrFail($request->team_id);

        $member = TeamMember::where('team_id', $request->team_id)
            ->where('user_id', $request->member_id)
            ->first();

        if (!$member) {
            return response()->json(['response' => 'not_member'], 404);
        }

        $member->delete();

        return response()->json(['response' => 'member_removed'], 200);
    }

    public function leaveTeam(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $member = TeamMember::where('team_id', $request->team_id)
            ->where('user_id', $request->user_id)
            ->first();

        if (!$member) {
            return response()->json(['response' => 'not_member'], 404);
        }

        if ($member->role === 'owner') {
            $otherOwner = TeamMember::where('team_id', $request->team_id)
                ->where('user_id', '!=', $request->user_id)
                ->where('role', 'owner')
                ->where('status', 'accepted') 
                ->first();

            if (!$otherOwner) {
                return response()->json(['response' => 'no_owner'], 403);
            }
        }

        $member->delete();

        return response()->json(['response' => 'left_team'], 200);
    }

    public function changeMemberRole(Request $request)
    {
        $request->validate([
            'team_id'      => 'required|exists:teams,id',
            'member_id'    => 'required|exists:users,id',
            'role'         => 'required|in:admin,member,owner',
            'requester_id' => 'required|exists:users,id',
        ]);

        $requester = TeamMember::where('team_id', $request->team_id)
        ->where('user_id', $request->requester_id)
        ->whereIn('role', ['owner', 'admin']) 
        ->where('status', 'accepted')
        ->first();

        if (!$requester) {
            return response()->json(['response' => 'unauthorized'], 403);
        }

        if ($requester->role !== 'owner' && $request->role === 'owner') {
            return response()->json(['response' => 'unauthorized_owner_change'], 403);
        }

        if (!$requester) {
            return response()->json(['response' => 'unauthorized'], 403);
        }

        $team = Teams::findOrFail($request->team_id);

        $member = TeamMember::where('team_id', $request->team_id)
            ->where('user_id', $request->member_id)
            ->first();

        if (!$member) {
            return response()->json(['response' => 'not_a_member'], 404);
        }

        if ($request->role === 'owner') {
            $currentOwner = TeamMember::where('team_id', $request->team_id)
                ->where('role', 'owner')
                ->first();

            if ($currentOwner) {
                $currentOwner->role = 'admin';
                $currentOwner->save();
            }
        }

        $member->role = $request->role;
        $member->save();

        return response()->json(['response' => 'success'], 200);
    }

    public function getTeamSharedFiles(Request $request)
    {
        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
        ]);

        try {
            $teamId = $validated['team_id'];

            $sharedFolderIds = TeamPermission::where('team_id', $teamId)
                ->whereNotNull('folder_id')
                ->pluck('folder_id')
                ->toArray();

            $permissions = TeamPermission::where('team_id', $teamId)
                ->whereNotNull('document_id')
                ->with([
                    'document' => function ($query) {
                        $query->whereNull('deleted_at')
                            ->where('is_archived', false);
                    },
                    'document.folder' => function ($query) {
                        $query->whereNull('deleted_at');
                    }
                ])
                ->get();

            $documents = $permissions
                ->filter(function ($perm) use ($sharedFolderIds) {
                    if (!$perm->document) return false;

                    $folderId = $perm->document->folder_id;
                    return !$folderId || !in_array($folderId, $sharedFolderIds);
                })
                ->map(function ($perm) {
                    $doc = $perm->document;

                    return [
                        'id'          => $doc->id,
                        'title'       => $doc->title,
                        'file_name'   => $doc->file_name,
                        'file_type'   => $doc->file_type,
                        'file_size'   => $doc->file_size,
                        'folder_id'   => $doc->folder_id,
                        'folder_name' => optional($doc->folder)->name ?? '--',
                        'created_at'  => $doc->created_at->format('m/d/Y'),
                        'permission'  => $perm->permission,
                        'item_type'   => 'file',
                    ];
                })
                ->sortBy('title')
                ->values();

            return response()->json([
                'response'  => 'success',
                'documents' => $documents,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'message'  => 'Failed to fetch shared documents.',
                'details'  => $e->getMessage(),
            ], 500);
        }
    }

    public function getTeamFolderFiles(Request $request)
{
    $validated = $request->validate([
        'team_id'   => 'required|exists:teams,id',
        'folder_id' => 'nullable|exists:folders,id',
    ]);

    try {
        $teamId   = $validated['team_id'];
        $folderId = $validated['folder_id'] ?? null;

        $permissions = TeamPermission::where('team_id', $teamId)
            ->whereNotNull('document_id')
            ->with([
                'document' => function ($query) {
                    $query->whereNull('deleted_at')->where('is_archived', false);
                },
                'document.folder' => function ($query) {
                    $query->whereNull('deleted_at');
                }
            ])
            ->get();

        $sharedFolderIds = TeamPermission::where('team_id', $teamId)
            ->whereNotNull('folder_id')
            ->pluck('folder_id')
            ->toArray();

        $allFolders = Folders::whereNull('deleted_at')->get()->keyBy('id');

        $isFolderSharedThroughAncestors = function ($folderIdToCheck) use ($sharedFolderIds, $allFolders) {
            while ($folderIdToCheck) {
                if (in_array($folderIdToCheck, $sharedFolderIds)) return true;
                $folder = $allFolders[$folderIdToCheck] ?? null;
                $folderIdToCheck = $folder ? $folder->parent_id : null;
            }
            return false;
        };

        $documents = $permissions
            ->filter(function ($perm) use ($folderId, $isFolderSharedThroughAncestors) {
                if (!$perm->document) return false;

                $docFolderId = $perm->document->folder_id;

                if ($folderId === null) {
                    return !$isFolderSharedThroughAncestors($docFolderId);
                } else {
                    return $docFolderId == $folderId && $isFolderSharedThroughAncestors($docFolderId);
                }
            })
            ->map(function ($perm) use ($folderId) {
                $document = $perm->document;
                $folder   = $document->folder;

                return [
                    'id'          => $document->id,
                    'title'       => $document->title,
                    'file_name'   => $document->file_name,
                    'file_type'   => $document->file_type,
                    'file_size'   => $document->file_size,
                    'folder_id'   => $folder ? $folder->id : null,
                    'folder_name' => $folder ? $folder->folder_name : '--',
                    'created_at'  => $document->created_at->format('m/d/Y'),
                    'permission'  => $perm->permission,
                    'item_type'   => 'file',
                ];
            })
            ->sortBy('title')
            ->values();

        return response()->json([
            'response'  => 'success',
            'documents' => $documents,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'response' => 'error',
            'message'  => 'Failed to fetch shared documents.',
            'details'  => $e->getMessage(),
        ], 500);
    }
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
                ->with(['folder' => function ($query) {
                    $query->whereNull('deleted_at'); 
                }, 'folder.parent' => function ($query) {
                    $query->whereNull('deleted_at'); 
                }])
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

    public function getTeamSubfolders(Request $request)
    {
        $validated = $request->validate([
            'team_id'     => 'required|exists:teams,id',
            'folder_id'   => 'required|exists:folders,id', 
        ]);

        try {
            $teamId   = $validated['team_id'];
            $parentId = $validated['folder_id'];

            $permissions = TeamPermission::where('team_id', $teamId)
                ->whereNotNull('folder_id')
                ->with(['folder' => function ($query) use ($parentId) {
                    $query->where('parent_id', $parentId)
                        ->whereNull('deleted_at');
                }])
                ->get();

            $subfolders = $permissions
                ->filter(fn($perm) => $perm->folder !== null)
                ->sortBy(fn($perm) => $perm->folder->folder_name)
                ->values()
                ->map(function ($perm) {
                    return [
                        'id'          => $perm->folder->id,
                        'folder_name' => $perm->folder->folder_name,
                        'parent_id'   => $perm->folder->parent_id,
                        'permission'  => $perm->permission,
                        'granted_by'  => $perm->granted_by,
                        'created_at'  => $perm->folder->created_at,
                    ];
                });

            return response()->json([
                'response' => 'success',
                'folders'  => $subfolders,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'response' => 'error',
                'message'  => 'Failed to fetch subfolders.',
                'details'  => $e->getMessage(),
            ], 500);
        }
    }


    public function getUserInvites(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $userId = $request->user_id;

        $invites = TeamMember::with(['team', 'owner'])
            ->where('user_id', $userId)
            ->where('status', 'pending')  
            ->get()
            ->map(function ($invite) {
                return [
                    'id' => $invite->id, 
                    'team_id' => $invite->team->id,
                    'team_name'   => $invite->team->team_name ?? 'N/A',
                    'owner_email' => $invite->owner->email ?? 'N/A',
                    'role'        => $invite->role,
                    'invited_at'  => $invite->created_at->format('m/d/Y'),
                ];
            });

        return response()->json(['invites' => $invites, "response" => "success"]);
    }

}
