@extends('layouts.admin')

@section('content')

<div class="card">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> التقارير السرية</span>
        <span class="badge badge-purple">{{ $reports->total() }}</span>
    </div>

    @forelse($reports as $report)
    <div class="card" style="border: 1px solid var(--border); margin-bottom:16px; padding:18px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:12px">
            <div>
                <strong style="font-size:.92rem">{{ $report->employee->name ?? 'مجهول' }}</strong>
                <span class="badge badge-blue" style="margin-right:8px">{{ $report->type ?? 'عام' }}</span>
                <div style="font-size:.72rem;color:var(--text3);margin-top:4px">{{ $report->created_at->format('Y-m-d h:i A') }}</div>
            </div>
            <div style="display:flex;gap:6px">
                @php
                $statusColors = ['new'=>'badge-yellow','reviewed'=>'badge-blue','in_progress'=>'badge-orange','resolved'=>'badge-green','dismissed'=>'badge-red','archived'=>'badge-gray'];
                $statusLabels = ['new'=>'جديد','reviewed'=>'تمت المراجعة','in_progress'=>'قيد المعالجة','resolved'=>'تم الحل','dismissed'=>'مرفوض','archived'=>'مؤرشف'];
                @endphp
                <span class="badge {{ $statusColors[$report->status] ?? 'badge-gray' }}">{{ $statusLabels[$report->status] ?? $report->status }}</span>
            </div>
        </div>

        <p style="font-size:.88rem;line-height:1.7;color:var(--text2);margin-bottom:12px">{{ $report->report_text }}</p>

        @if($report->image_paths)
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px">
            @foreach(json_decode($report->image_paths, true) ?? [] as $img)
                <a href="{{ asset('storage/' . $img) }}" target="_blank">
                    <img src="{{ asset('storage/' . $img) }}" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid var(--border)">
                </a>
            @endforeach
        </div>
        @endif

        @if($report->voice_path)
        <div style="margin-bottom:10px">
            <audio controls style="width:100%;max-width:300px">
                <source src="{{ asset('storage/' . $report->voice_path) }}">
            </audio>
        </div>
        @endif

        <!-- Status actions -->
        <form method="POST" action="{{ route('admin.secret-reports.update', $report) }}" style="display:flex;gap:6px;flex-wrap:wrap">
            @csrf
            <select name="status" class="form-control" style="max-width:180px;padding:6px 10px;font-size:.8rem">
                @foreach($statusLabels as $val => $lbl)
                    <option value="{{ $val }}" {{ $report->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary">تحديث</button>
        </form>
    </div>
    @empty
        <div style="text-align:center;color:var(--text3);padding:40px">لا توجد تقارير سرية</div>
    @endforelse

    @if($reports->hasPages())
    <div style="margin-top:16px;display:flex;justify-content:center">
        {{ $reports->links('pagination::simple-default') }}
    </div>
    @endif
</div>

@endsection
