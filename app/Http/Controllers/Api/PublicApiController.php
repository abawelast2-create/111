<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicApiController extends Controller
{
    /**
     * توليد API Token
     */
    public function createToken(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'token_name' => 'required|string|max:100',
            'abilities' => 'nullable|array',
        ]);

        $admin = Admin::where('username', $request->username)->first();

        if (!$admin || !\Hash::check($request->password, $admin->password_hash)) {
            return response()->json(['message' => 'بيانات الاعتماد غير صحيحة'], 401);
        }

        $abilities = $request->abilities ?? ['*'];
        $token = $admin->createToken($request->token_name, $abilities);

        return response()->json([
            'token'     => $token->plainTextToken,
            'type'      => 'Bearer',
            'abilities' => $abilities,
        ]);
    }

    /**
     * إلغاء Token
     */
    public function revokeToken(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم إلغاء التوكن بنجاح']);
    }

    // =================== بيانات الحضور ===================

    /**
     * قائمة سجلات الحضور
     */
    public function attendanceIndex(Request $request): JsonResponse
    {
        $query = Attendance::with('employee:id,name,job_title,branch_id');

        if ($request->filled('date')) {
            $query->where('attendance_date', $request->date);
        }
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('attendance_date', [$request->from, $request->to]);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $perPage = min($request->input('per_page', 50), 100);

        return response()->json($query->latest('timestamp')->cursorPaginate($perPage));
    }

    // =================== الموظفون ===================

    /**
     * قائمة الموظفين
     */
    public function employeeIndex(Request $request): JsonResponse
    {
        $query = Employee::with('branch:id,name');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('job_title', 'like', "%{$search}%");
            });
        }

        return response()->json($query->cursorPaginate(50));
    }

    /**
     * تفاصيل موظف
     */
    public function employeeShow(Employee $employee): JsonResponse
    {
        $employee->load('branch:id,name');

        $todayRecords = $employee->attendances()
            ->where('attendance_date', today()->toDateString())
            ->get();

        return response()->json([
            'employee'      => $employee,
            'today_records' => $todayRecords,
        ]);
    }

    // =================== الفروع ===================

    /**
     * قائمة الفروع
     */
    public function branchIndex(): JsonResponse
    {
        $branches = Branch::active()
            ->withCount(['employees' => fn ($q) => $q->active()])
            ->get();

        return response()->json(['data' => $branches]);
    }

    /**
     * إنشاء موظف جديد
     */
    public function employeeStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'branch_id' => 'required|integer|exists:branches,id',
            'job_title' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
        ]);

        $validated['pin'] = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $validated['unique_token'] = bin2hex(random_bytes(16));

        $employee = Employee::create($validated);

        return response()->json($employee, 201);
    }

    // =================== الإجازات ===================

    /**
     * قائمة الإجازات
     */
    public function leaveIndex(Request $request): JsonResponse
    {
        $query = Leave::with('employee:id,name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        return response()->json($query->latest()->cursorPaginate(50));
    }

    /**
     * إنشاء طلب إجازة
     */
    public function leaveStore(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type'  => 'required|in:annual,sick,unpaid,other',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'reason'      => 'nullable|string|max:1000',
        ]);

        if (Leave::hasOverlapping($request->employee_id, $request->start_date, $request->end_date)) {
            return response()->json(['message' => 'يوجد إجازة متداخلة'], 422);
        }

        $leave = Leave::create($request->only('employee_id', 'leave_type', 'start_date', 'end_date', 'reason'));

        return response()->json($leave, 201);
    }

    /**
     * الموافقة على إجازة
     */
    public function leaveApprove(Request $request, Leave $leave): JsonResponse
    {
        $leave->update([
            'status'      => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        return response()->json(['message' => 'تمت الموافقة على الإجازة', 'leave' => $leave]);
    }

    /**
     * رفض إجازة
     */
    public function leaveReject(Leave $leave): JsonResponse
    {
        $leave->update(['status' => 'rejected']);
        return response()->json(['message' => 'تم رفض الإجازة', 'leave' => $leave]);
    }
}
