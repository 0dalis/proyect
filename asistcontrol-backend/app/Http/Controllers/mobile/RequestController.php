<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requests = RequestModel::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['requests' => $requests]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:permission,justification,vacation',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $model = RequestModel::create([
            'company_id' => $request->user()->company_id,
            'user_id' => $request->user()->id,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'reason' => $request->reason,
            'status' => 'pending',
            'is_paid' => false,
        ]);

        return response()->json(['message' => 'Solicitud enviada.', 'request' => $model], 201);
    }
}
