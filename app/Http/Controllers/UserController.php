<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::withTrashed()->orderBy('nama')->get();

        return view('users.index', [
            'pengguna' => UserResource::collection($users)->resolve(),
            'currentUser' => $request->user() ? UserResource::make($request->user())->resolve() : null,
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->mapped();

        $user = User::create($data);

        return response()->json([
            'success' => true,
            'resource' => UserResource::make($user->fresh())->resolve(),
        ], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        if ($user->trashed()) {
            return response()->json(['success' => false, 'message' => 'Pengguna yang sudah dihapus tidak dapat diubah.'], 422);
        }

        $data = $request->mapped();

        // Guard: don't deactivate the last active admin.
        if ($user->isAdmin() && $data['is_active'] === false) {
            $activeAdmins = User::where('peran', 'admin')
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->where('id', '!=', $user->id)
                ->count();
            if ($activeAdmins === 0) {
                return response()->json(['success' => false, 'message' => 'Tidak dapat menonaktifkan Admin terakhir.'], 422);
            }
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'resource' => UserResource::make($user->fresh())->resolve(),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(['success' => true]);
    }
}
