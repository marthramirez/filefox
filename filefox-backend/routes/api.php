<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamPermissionController;
use App\Models\Documents;
use App\Models\Folders;
use App\Models\Teams;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route for Login and Register 
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Dashboard Data
    Route::post('/dashboard/data', [DashboardController::class, 'getDashboardData']);

    // Route for Users 
    Route::post('/updateAccount', [AuthController::class, 'updateAccount']);
    Route::post('/getUserInfo', [AuthController::class, 'getUserInfo']);

    // Route for Folders
    Route::post('/createFolder', [FolderController::class, 'createFolder']);
    Route::put('/renameFolder', [FolderController::class, 'renameFolder']);
    Route::post('/getUserFolders', [FolderController::class, 'getUserFolders']);
    Route::get('/getSubfolders/{id}', [FolderController::class, 'getSubfolders']);
    Route::delete('/softDeleteFolder/{id}', [FolderController::class, 'moveFolderToTrash']);
    Route::delete('/deleteFolder/{id}', [FolderController::class, 'deleteFolder']);
    Route::post('/getTrashedFolders', [FolderController::class, 'getTrashedFolders']);
    Route::post('/restoreFolderFromTrash', [FolderController::class, 'restoreFolderFromTrash']);
    Route::post('/getTrashedSubfolders', [FolderController::class, 'getTrashedSubfolders']);
    Route::get('/getFoldersSharedToUser/{id}', [FolderController::class, 'getFoldersSharedToUser']);
    Route::post('/getSharedSubfolders', [FolderController::class, 'getSharedSubfolders']);


    //Route for Files
    Route::post('/uploadFile', [FileController::class, 'uploadFile']);
    Route::get('/view/file/{id}', [FileController::class, 'fetchDocumentDetails']);
    Route::post('/update/file/{id}', [FileController::class, 'updateFile']);
    Route::post('/getFile', [FileController::class, 'getUserFiles']);
    Route::get('/getFolderFiles/{id}', [FileController::class, 'getFolderFiles']);
    Route::delete('/softDeleteFile/{id}', [FileController::class, 'moveFileToTrash']);
    Route::delete('/deleteFile/{id}', [FileController::class, 'deleteFile']);
    Route::post('/fetchUserFiles', [FileController::class, 'fetchUserFiles']);
    Route::get('/fileDownload/{id}', [FileController::class, 'fileDownload']);
    Route::post('/getTrashedFiles', [FileController::class, 'getTrashedFiles']);
    Route::post('/restoreFile', [FileController::class, 'restoreFileFromTrash']);
    Route::post('/getTrashedFolderFiles', [FileController::class, 'getTrashedFolderFiles']);
    Route::get('/getUserSharedFiles/{id}', [FileController::class, 'getUserSharedFiles']);
    Route::post('/getSharedFolderFiles', [FileController::class, 'getSharedFolderFiles']);
    Route::get('/pdf/view/{id}', [FileController::class, 'viewPdf']);
    Route::get('/docx/download/{id}', [FileController::class, 'downloadDocx']);

    //Route for Teams
    Route::post('/createTeam', [TeamController::class, 'createTeam']);
    Route::post('/inviteMember', [TeamController::class, 'inviteMember']);
    Route::post('/acceptInvitation', [TeamController::class, 'acceptInvitation']);
    Route::post('/rejectInvitation', [TeamController::class, 'rejectInvitation']);
    Route::post('/getUserTeams', [TeamController::class, 'getUserTeams']);
    Route::post('/getTeamMembers', [TeamController::class, 'getTeamMembers']);
    Route::post('/removeMember', [TeamController::class, 'removeMember']);
    Route::post('/leaveTeam', [TeamController::class, 'leaveTeam']);
    Route::post('/changeRole', [TeamController::class, 'changeMemberRole']);
    Route::post('/getTeamSharedFiles', [TeamController::class, 'getTeamSharedFiles']);
    Route::post('/getTeamFolderFiles', [TeamController::class, 'getTeamFolderFiles']);
    Route::post('/getTeamSharedFolders', [TeamController::class, 'getTeamSharedFolders']);
    Route::post('/getTeamSharedFiles', [TeamController::class, 'getTeamSharedFiles']);
    Route::post('/getTeamSubfolders', [TeamController::class, 'getTeamSubfolders']);
    Route::post('/getUserInvites', [TeamController::class, 'getUserInvites']);

    // Permissions
    Route::post('/permissions/teams', [TeamPermissionController::class, 'getTeamsWithPermission']);
    Route::post('/permissions/user', [PermissionController::class, 'getUserPermissions']);
    Route::post('/permissions/update', [PermissionController::class, 'updateUserPermissions']);
    Route::post('/teamPermissions/update', [TeamPermissionController::class, 'updateTeamPermissions']);
    Route::post('/shareFileToPerson', [PermissionController::class, 'shareFileToPerson']);
    Route::post('/shareFileToTeam', [TeamPermissionController::class, 'shareFileToTeam']);
    Route::post('/shareFolderWithPerson/{id}', [FolderController::class, 'shareFolderWithPerson']);
    Route::post('/shareFolderWithTeam/{id}', [FolderController::class, 'shareFolderWithTeam']);
    Route::post('/createSharedFolder', [FolderController::class, 'createSharedFolder']);

});