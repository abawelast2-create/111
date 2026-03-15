@extends('layouts.admin')

@section('content')

<!-- Stats -->
<div class="stats-grid" id="statsGrid">
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><x-icon name="branch" :size="26"/></div>
        <div>
            <div class="stat-value" data-live="branches">{{ $branchCount }}</div>
            <div class="stat-label">الفروع النشطة</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><x-icon name="employees" :size="26"/></div>
        <div>
            <div class="stat-value" data-live="total_employees">{{ $stats['total_employees'] }}</div>
            <div class="stat-label">إجمالي الموظفين</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><x-icon name="checkin" :size="26"/></div>
        <div>
            <div class="stat-value" data-live="checked_in">{{ $stats['checked_in'] }}</div>
            <div class="stat-label">حضروا اليوم</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap purple"><x-icon name="checkout" :size="26"/></div>
        <div>
            <div class="stat-value" data-live="checked_out">{{ $stats['checked_out'] }}</div>
            <div class="stat-label">انصرفوا اليوم</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap red"><x-icon name="absent" :size="26"/></div>
        <div>
            <div class="stat-value" data-live="absent">{{ $stats['total_employees'] - $stats['checked_in'] }}</div>
            <div class="stat-label">غائبون اليوم</div>
        </div>
    </div>
</div>

<div class="dashboard-grid">

    <!-- آخر التسجيلات -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><span class="card-title-bar"></span> آخر التسجيلات</span>
            <div style="display:flex;align-items:center;gap:10px">
                <div class="live-indicator" id="liveIndicator">
                    <span class="live-dot"></span>
                    <span id="liveText">مباشر</span>
                </div>
                <a href="{{ route('admin.attendance.index') }}" class="btn btn-secondary btn-sm">عرض الكل</a>
            </div>
        </div>
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>الموظف</th><th>الفرع</th><th>النوع</th><th>الوقت</th></tr></thead>
            <tbody id="recentTableBody">
            @forelse($recentRecords as $rec)
                <tr data-ts="{{ $rec->timestamp }}">
                    <td><strong>{{ $rec->employee->name ?? '-' }}</strong><br><small style="color:var(--text3)">{{ $rec->employee->job_title ?? '' }}</small></td>
                    <td style="font-size:.78rem;color:var(--text2)">{{ $rec->employee->branch->name ?? '-' }}</td>
                    <td>
                        <span class="badge {{ $rec->type === 'in' ? 'badge-green' : ($rec->type === 'out' ? 'badge-red' : 'badge-purple') }}">
                            {{ $rec->type === 'in' ? 'دخول' : ($rec->type === 'out' ? 'انصراف' : 'إضافي') }}
                        </span>
                    </td>
                    <td style="color:var(--text3);font-size:.8rem">{{ \Carbon\Carbon::parse($rec->timestamp)->format('h:i A') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;color:var(--text3);padding:20px">لا توجد تسجيلات اليوم</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <!-- الغائبون اليوم -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><span class="card-title-bar"></span> غائبون اليوم</span>
            <span class="badge badge-red" id="absentBadge">{{ count($absentList) }}</span>
        </div>
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>الاسم</th><th>الوظيفة</th><th>الفرع</th></tr></thead>
            <tbody id="absentTableBody">
            @forelse($absentList as $emp)
                <tr>
                    <td>{{ $emp->name }}</td>
                    <td style="color:var(--text3)">{{ $emp->job_title }}</td>
                    <td style="font-size:.78rem;color:var(--text2)">{{ $emp->branch->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align:center;color:var(--green);padding:20px">جميع الموظفين حضروا!</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const REFRESH_INTERVAL = 15000;
let lastRecordTs = @json($recentRecords->first()?->timestamp);
let lastAbsentCount = {{ count($absentList) }};

function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}

function updateStatValue(key, newValue) {
    const el = document.querySelector(`[data-live="${key}"]`);
    if (!el) return;
    const nv = String(newValue);
    if (el.textContent.trim() !== nv) {
        el.textContent = nv;
        el.classList.add('updated');
        setTimeout(() => el.classList.remove('updated'), 600);
    }
}

async function refreshDashboard() {
    try {
        const res = await fetch('/api/realtime-dashboard', { credentials: 'same-origin' });
        if (!res.ok) return;
        const data = await res.json();

        if (data.stats) {
            updateStatValue('total_employees', data.stats.total_employees);
            updateStatValue('checked_in', data.stats.checked_in);
            updateStatValue('checked_out', data.stats.checked_out);
            updateStatValue('absent', data.stats.total_employees - data.stats.checked_in);
        }

        document.getElementById('liveIndicator')?.classList.remove('error','paused');
    } catch(e) {
        document.getElementById('liveIndicator')?.classList.add('error');
    }
}

setInterval(refreshDashboard, REFRESH_INTERVAL);
</script>
@endpush
