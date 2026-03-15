<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecretReport;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function submitReport(Request $request): JsonResponse
    {
        $request->validate([
            'token'        => 'required|string|size:64',
            'report_text'  => 'nullable|string|max:5000',
            'report_type'  => 'nullable|string|max:50',
            'voice_effect' => 'nullable|string|max:20',
            'images.*'     => 'nullable|image|max:5120',
            'voice'        => 'nullable|file|max:10240',
        ]);

        $employee = Employee::findByToken($request->token);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'رمز غير صالح'], 403);
        }

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('reports/images', 'public');
            }
        }

        $voicePath = null;
        if ($request->hasFile('voice')) {
            $voicePath = $request->file('voice')->store('reports/voice', 'public');
        }

        SecretReport::create([
            'employee_id'  => $employee->id,
            'report_text'  => $request->report_text,
            'report_type'  => $request->report_type ?? 'violation',
            'image_paths'  => count($imagePaths) > 0 ? $imagePaths : null,
            'has_image'    => count($imagePaths) > 0,
            'voice_path'   => $voicePath,
            'has_voice'    => $voicePath !== null,
            'voice_effect' => $request->voice_effect,
            'status'       => 'new',
        ]);

        return response()->json(['success' => true, 'message' => 'تم إرسال البلاغ بنجاح']);
    }

    public function addLeave(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'leave_type'  => 'required|in:annual,sick,unpaid,other',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'reason'      => 'nullable|string|max:1000',
        ]);

        if (Leave::hasOverlapping($request->employee_id, $request->start_date, $request->end_date)) {
            return response()->json(['success' => false, 'message' => 'يوجد تداخل مع إجازة أخرى'], 422);
        }

        Leave::create([
            'employee_id' => $request->employee_id,
            'leave_type'  => $request->leave_type,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'reason'      => $request->reason,
            'status'      => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'تم إضافة طلب الإجازة بنجاح']);
    }
}
