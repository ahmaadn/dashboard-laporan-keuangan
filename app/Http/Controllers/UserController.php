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
        $isSelf = $request->user()->id === $user->id;

        // Guard: an admin cannot change their own role.
        if ($isSelf && $user->isAdmin() && $data['peran'] !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Anda tidak dapat mengubah peran akun Anda sendiri.'], 422);
        }

        // Guard: don't deactivate the last active admin.
        if ($user->isAdmin() && $data['is_active'] === false) {
            $activeAdmins = $this->countOtherActiveAdmins($user->id);
            if ($activeAdmins === 0) {
                return response()->json(['success' => false, 'message' => 'Tidak dapat menonaktifkan Admin terakhir.'], 422);
            }
        }

        // Guard: don't downgrade the last active admin to pegawai.
        if ($user->isAdmin() && $data['peran'] === 'pegawai') {
            $activeAdmins = $this->countOtherActiveAdmins($user->id);
            if ($activeAdmins === 0) {
                return response()->json(['success' => false, 'message' => 'Tidak dapat menurunkan peran Admin terakhir menjadi Pegawai.'], 422);
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

    private function countOtherActiveAdmins(int $excludeId): int
    {
        return User::where('peran', 'admin')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where('id', '!=', $excludeId)
            ->count();
    }
}
