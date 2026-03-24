<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportSchedule;
use App\Models\Branch;
use App\Services\ReportMailService;
use Illuminate\Http\Request;

class ReportMailController extends Controller
{
    public function index()
    {
        $schedules = ReportSchedule::with('creator')->latest()->get();
        $branches  = Branch::active()->get();

        return view('admin.report-schedules', compact('schedules', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'report_type' => 'required|in:daily,late,full',
            'frequency'   => 'required|in:daily,weekly,monthly',
            'send_time'   => 'required|date_format:H:i',
            'send_day'    => 'nullable|string|max:10',
            'recipients'  => 'required|string',
            'branch_id'   => 'nullable|exists:branches,id',
        ]);

        $recipients = array_filter(array_map('trim', explode(',', $validated['recipients'])));

        foreach ($recipients as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return back()->withErrors(['recipients' => "البريد {$email} غير صالح"])->withInput();
            }
        }

        ReportSchedule::create([
            'name'        => $validated['name'],
            'report_type' => $validated['report_type'],
            'frequency'   => $validated['frequency'],
            'send_time'   => $validated['send_time'],
            'send_day'    => $validated['send_day'],
            'recipients'  => $recipients,
            'filters'     => $validated['branch_id'] ? ['branch_id' => $validated['branch_id']] : null,
            'created_by'  => session('admin_id'),
        ]);

        return redirect()->route('admin.report-schedules.index')->with('success', 'تم إنشاء الجدولة بنجاح');
    }

    public function update(Request $request, ReportSchedule $schedule)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'report_type' => 'required|in:daily,late,full',
            'frequency'   => 'required|in:daily,weekly,monthly',
            'send_time'   => 'required|date_format:H:i',
            'send_day'    => 'nullable|string|max:10',
            'recipients'  => 'required|string',
            'branch_id'   => 'nullable|exists:branches,id',
        ]);

        $recipients = array_filter(array_map('trim', explode(',', $validated['recipients'])));

        foreach ($recipients as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return back()->withErrors(['recipients' => "البريد {$email} غير صالح"])->withInput();
            }
        }

        $schedule->update([
            'name'        => $validated['name'],
            'report_type' => $validated['report_type'],
            'frequency'   => $validated['frequency'],
            'send_time'   => $validated['send_time'],
            'send_day'    => $validated['send_day'],
            'recipients'  => $recipients,
            'filters'     => $validated['branch_id'] ? ['branch_id' => $validated['branch_id']] : null,
        ]);

        return redirect()->route('admin.report-schedules.index')->with('success', 'تم تحديث الجدولة بنجاح');
    }

    public function destroy(ReportSchedule $schedule)
    {
        $schedule->delete();

        return redirect()->route('admin.report-schedules.index')->with('success', 'تم حذف الجدولة');
    }

    public function toggle(ReportSchedule $schedule)
    {
        $schedule->update(['is_active' => !$schedule->is_active]);

        $status = $schedule->is_active ? 'تفعيل' : 'إيقاف';

        return redirect()->route('admin.report-schedules.index')->with('success', "تم {$status} الجدولة");
    }

    public function sendNow(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:daily,late,full',
            'recipients'  => 'required|string',
            'from'        => 'required|date',
            'to'          => 'required|date|after_or_equal:from',
            'branch_id'   => 'nullable|exists:branches,id',
        ]);

        $recipients = array_filter(array_map('trim', explode(',', $validated['recipients'])));

        foreach ($recipients as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return back()->withErrors(['recipients' => "البريد {$email} غير صالح"])->withInput();
            }
        }

        $filters = [];
        if (!empty($validated['branch_id'])) {
            $filters['branch_id'] = $validated['branch_id'];
        }

        $result = ReportMailService::sendReport(
            $validated['report_type'],
            $recipients,
            $validated['from'],
            $validated['to'],
            $filters
        );

        if ($result['sent'] > 0) {
            $msg = "تم إرسال التقرير بنجاح إلى {$result['sent']} مستلم";
            if (!empty($result['failed'])) {
                $msg .= "، فشل الإرسال إلى " . count($result['failed']) . " مستلم";
            }
            return back()->with('success', $msg);
        }

        return back()->with('error', 'فشل إرسال التقرير: ' . ($result['failed'][0]['error'] ?? 'خطأ غير معروف'));
    }
}
