<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmpDocumentFile;
use App\Models\EmpDocumentGroup;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function profileAction(Request $request): JsonResponse
    {
        $request->validate([
            'action'      => 'required|in:add_group,save_group,delete_group,delete_file,delete_photo',
            'employee_id' => 'required|integer|exists:employees,id',
        ]);

        $employee = Employee::findOrFail($request->integer('employee_id'));

        return match ($request->string('action')->toString()) {
            'add_group' => $this->addGroup($employee),
            'save_group' => $this->saveGroup($request, $employee),
            'delete_group' => $this->deleteGroup($request, $employee),
            'delete_file' => $this->deleteFile($request, $employee),
            'delete_photo' => $this->deletePhoto($employee),
            default => response()->json(['success' => false, 'message' => 'إجراء غير معروف'], 422),
        };
    }

    public function uploadProfile(Request $request): JsonResponse
    {
        $request->validate([
            'action'      => 'required|in:photo,document',
            'employee_id' => 'required|integer|exists:employees,id',
            'file'        => 'required|file|max:10240',
            'group_id'    => 'nullable|integer',
        ]);

        $employee = Employee::findOrFail($request->integer('employee_id'));

        if ($request->action === 'photo') {
            return $this->uploadPhoto($request, $employee);
        }

        return $this->uploadDocument($request, $employee);
    }

    public function getGroupFiles(Request $request): JsonResponse
    {
        $request->validate([
            'group_id' => 'required|integer|exists:emp_document_groups,id',
        ]);

        $group = EmpDocumentGroup::with('files')->findOrFail($request->integer('group_id'));

        return response()->json([
            'success' => true,
            'files' => $group->files->map(fn ($f) => [
                'id'            => $f->id,
                'file_path'     => $f->file_path,
                'file_type'     => $f->file_type,
                'original_name' => $f->original_name,
                'file_size'     => $f->file_size,
            ]),
        ]);
    }

    public function serveFile(Request $request)
    {
        $request->validate([
            'f' => 'required|string|max:500',
            't' => 'nullable|string|size:64',
        ]);

        $path = ltrim(str_replace('\\', '/', $request->query('f')), '/');
        if (!str_starts_with($path, 'profiles/')) {
            abort(403, 'غير مصرح');
        }

        if (!preg_match('#^profiles/(\d+)/#', $path, $m)) {
            abort(403, 'غير مصرح');
        }

        $employeeId = (int) $m[1];
        $adminLoggedIn = !empty(session('admin_id'));

        if (!$adminLoggedIn) {
            $token = (string) $request->query('t', '');
            $employee = Employee::findByToken($token);
            if (!$employee || $employee->id !== $employeeId) {
                abort(403, 'غير مصرح');
            }
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
        if (!in_array($mime, $allowed, true)) {
            abort(403, 'نوع ملف غير مدعوم');
        }

        return response()->file(Storage::disk('public')->path($path), [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function savePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'key'   => 'required|in:dark_mode,language,sidebar_collapsed,notifications_enabled',
            'value' => 'nullable|string|max:255',
        ]);

        $adminId = (int) session('admin_id');
        if (!$adminId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
        }

        UserPreference::setValue($adminId, $request->key, $request->value);

        return response()->json(['success' => true, 'message' => 'تم حفظ التفضيل']);
    }

    private function addGroup(Employee $employee): JsonResponse
    {
        if ($employee->documentGroups()->count() >= 10) {
            return response()->json(['success' => false, 'message' => 'الحد الأقصى 10 مجموعات'], 422);
        }

        $group = EmpDocumentGroup::create([
            'employee_id' => $employee->id,
            'group_name'  => 'مجموعة جديدة',
            'expiry_date' => today()->addYear()->toDateString(),
            'sort_order'  => 0,
        ]);

        AuditLog::record('add_document_group', "إضافة مجموعة وثائق للموظف: {$employee->name}", $employee->id);

        return response()->json([
            'success' => true,
            'group' => [
                'id'          => $group->id,
                'group_name'  => $group->group_name,
                'expiry_date' => $group->expiry_date?->toDateString(),
                'days_left'   => $group->days_left,
                'file_count'  => 0,
                'files'       => [],
            ],
        ]);
    }

    private function saveGroup(Request $request, Employee $employee): JsonResponse
    {
        $request->validate([
            'group_id'    => 'required|integer|exists:emp_document_groups,id',
            'group_name'  => 'required|string|max:200',
            'expiry_date' => 'required|date',
        ]);

        $group = EmpDocumentGroup::where('id', $request->group_id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $group->update([
            'group_name'  => $request->group_name,
            'expiry_date' => $request->expiry_date,
        ]);

        AuditLog::record('edit_document_group', "تعديل مجموعة وثائق #{$group->id}", $employee->id);

        return response()->json([
            'success'   => true,
            'days_left' => $group->fresh()->days_left,
        ]);
    }

    private function deleteGroup(Request $request, Employee $employee): JsonResponse
    {
        $request->validate([
            'group_id' => 'required|integer|exists:emp_document_groups,id',
        ]);

        $group = EmpDocumentGroup::where('id', $request->group_id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        foreach ($group->files as $file) {
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }
        }

        $group->delete();
        AuditLog::record('delete_document_group', "حذف مجموعة وثائق #{$request->group_id}", $employee->id);

        return response()->json(['success' => true]);
    }

    private function deleteFile(Request $request, Employee $employee): JsonResponse
    {
        $request->validate([
            'file_id' => 'required|integer|exists:emp_document_files,id',
        ]);

        $file = EmpDocumentFile::query()
            ->where('id', $request->file_id)
            ->whereHas('group', fn ($q) => $q->where('employee_id', $employee->id))
            ->firstOrFail();

        if (Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();
        AuditLog::record('delete_document_file', "حذف ملف وثيقة #{$request->file_id}", $employee->id);

        return response()->json(['success' => true]);
    }

    private function deletePhoto(Employee $employee): JsonResponse
    {
        if ($employee->profile_photo && Storage::disk('public')->exists($employee->profile_photo)) {
            Storage::disk('public')->delete($employee->profile_photo);
        }

        $employee->update(['profile_photo' => null]);
        AuditLog::record('delete_profile_photo', "حذف صورة بروفايل للموظف: {$employee->name}", $employee->id);

        return response()->json(['success' => true]);
    }

    private function uploadPhoto(Request $request, Employee $employee): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($employee->profile_photo && Storage::disk('public')->exists($employee->profile_photo)) {
            Storage::disk('public')->delete($employee->profile_photo);
        }

        $ext = $request->file('file')->getClientOriginalExtension();
        $name = 'photo.' . strtolower($ext);
        $dir = "profiles/{$employee->id}";

        $path = $request->file('file')->storeAs($dir, $name, 'public');
        $employee->update(['profile_photo' => $path]);

        AuditLog::record('upload_profile_photo', "رفع صورة بروفايل للموظف: {$employee->name}", $employee->id);

        return response()->json(['success' => true, 'path' => $path]);
    }

    private function uploadDocument(Request $request, Employee $employee): JsonResponse
    {
        $request->validate([
            'group_id' => 'required|integer|exists:emp_document_groups,id',
            'file'     => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ]);

        $group = EmpDocumentGroup::where('id', $request->group_id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        if ($group->files()->count() >= 10) {
            return response()->json(['success' => false, 'message' => 'الحد الأقصى 10 ملفات لكل مجموعة'], 422);
        }

        $originalName = $request->file('file')->getClientOriginalName();
        $safeOriginal = preg_replace('/[^a-zA-Z0-9._\-\x{0600}-\x{06FF} ]/u', '', $originalName) ?: 'document';
        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        $random = Str::random(16) . '.' . $ext;
        $dir = "profiles/{$employee->id}/docs/{$group->id}";
        $path = $request->file('file')->storeAs($dir, $random, 'public');

        $file = EmpDocumentFile::create([
            'group_id'       => $group->id,
            'file_path'      => $path,
            'file_type'      => $ext === 'pdf' ? 'pdf' : 'image',
            'original_name'  => $safeOriginal,
            'file_size'      => (int) $request->file('file')->getSize(),
            'sort_order'     => 0,
        ]);

        AuditLog::record('upload_document_file', "رفع ملف وثيقة للموظف: {$employee->name}", $employee->id);

        return response()->json([
            'success' => true,
            'file' => [
                'id'            => $file->id,
                'file_path'     => $file->file_path,
                'file_type'     => $file->file_type,
                'original_name' => $file->original_name,
                'file_size'     => $file->file_size,
            ],
        ]);
    }
}
