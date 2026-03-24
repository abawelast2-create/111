<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Services\DataGeneratorService;
use Illuminate\Http\Request;

class DataGeneratorController extends Controller
{
    public function index()
    {
        $branches  = Branch::active()->withCount('employees')->get();
        $employees = Employee::where('is_active', true)->whereNotNull('branch_id')->orderBy('name')->get();
        $batches   = DataGeneratorService::getBatches();
        $genCount  = \App\Models\Attendance::where('notes', 'LIKE', DataGeneratorService::GENERATED_TAG . '%')->count();

        return view('admin.data-generator', compact('branches', 'employees', 'batches', 'genCount'));
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'from'     => 'required|date',
            'to'       => 'required|date|after_or_equal:from',
            'scope'    => 'required|in:all,branch,employee',
            'scope_id' => 'nullable|integer',
        ]);

        $preview = DataGeneratorService::preview(
            $validated['from'],
            $validated['to'],
            $validated['scope'],
            $validated['scope_id'] ?? null
        );

        return response()->json($preview);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'from'     => 'required|date',
            'to'       => 'required|date|after_or_equal:from',
            'level'    => 'required|integer|min:1|max:10',
            'scope'    => 'required|in:all,branch,employee',
            'scope_id' => 'nullable|integer',
        ]);

        // Safety: limit to 2 years max
        $daysDiff = now()->parse($validated['from'])->diffInDays($validated['to']);
        if ($daysDiff > 730) {
            return back()->with('error', 'الحد الأقصى للفترة سنتان (730 يوماً)');
        }

        $result = DataGeneratorService::generate(
            $validated['from'],
            $validated['to'],
            $validated['level'],
            $validated['scope'],
            $validated['scope_id'] ?? null
        );

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success',
            "تم توليد {$result['total_records']} سجل بنجاح! " .
            "(حضور: {$result['checkins']}، انصراف: {$result['checkouts']}، " .
            "تأخير: {$result['late_records']}، غياب: {$result['absences']}، " .
            "معرف الدفعة: {$result['batch_id']})"
        );
    }

    public function cleanup(Request $request)
    {
        $batchId = $request->input('batch_id');
        $deleted = DataGeneratorService::cleanup($batchId ?: null);

        return back()->with('success', "تم حذف {$deleted} سجل مولّد" . ($batchId ? " (دفعة: {$batchId})" : " (جميع الدفعات)"));
    }
}
