<?php

namespace App\Http\Controllers;

use App\Http\Requests\CapitalInjectionRequest;
use App\Http\Resources\CapitalInjectionResource;
use App\Http\Resources\UserResource;
use App\Models\CapitalInjection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CapitalController extends Controller
{
    public function index(Request $request)
    {
        $capital = CapitalInjection::orderByDesc('tanggal')->get();

        return view('capital.index', [
            'modal' => CapitalInjectionResource::collection($capital)->resolve(),
            'currentUser' => $request->user() ? UserResource::make($request->user())->resolve() : null,
        ]);
    }

    public function store(CapitalInjectionRequest $request): JsonResponse
    {
        $data = $request->mapped();
        $data['user_id'] = $request->user()->id;

        $entry = CapitalInjection::create($data);

        return response()->json([
            'success' => true,
            'resource' => CapitalInjectionResource::make($entry)->resolve(),
        ], 201);
    }

    public function destroy(CapitalInjection $capitalInjection): JsonResponse
    {
        $capitalInjection->delete();

        return response()->json(['success' => true]);
    }
}
