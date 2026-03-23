# ====================================================================================================
#                              مراقب التغييرات الآلي - PowerShell Script
# ====================================================================================================
# الوصف: يراقب التغييرات على الملفات في المشروع ويسجلها تلقائياً
# الاستخدام: .\watch_changes.ps1
# ====================================================================================================

$projectPath = "c:\laragon\www\LINK3\attendance-system"
$logFile = Join-Path $projectPath "MONITORING_LOG.txt"
$snapshotFile = Join-Path $projectPath ".file_snapshot.json"

# الألوان للعرض
function Write-Success { Write-Host $args -ForegroundColor Green }
function Write-Info { Write-Host $args -ForegroundColor Cyan }
function Write-Warning2 { Write-Host $args -ForegroundColor Yellow }
function Write-Change { Write-Host $args -ForegroundColor Magenta }

# دالة لتسجيل التغيير
function Log-Change {
    param(
        [string]$Type,
        [string]$File,
        [string]$Details = ""
    )
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $separator = "`n" + ("-" * 80) + "`n`n"
    
    $entry = "[$timestamp] - $Type`n"
    $entry += "الملف: $File`n"
    if ($Details) {
        $entry += "التفاصيل: $Details`n"
    }
    $entry += "الحالة: تم التسجيل ✓`n"
    $entry += $separator
    
    Add-Content -Path $logFile -Value $entry -Encoding UTF8
}

# دالة للحصول على قائمة الملفات
function Get-FilesList {
    $files = @{}
    Get-ChildItem -Path $projectPath -Recurse -File -ErrorAction SilentlyContinue | Where-Object {
        $_.FullName -notmatch '\\\.git\\|\\node_modules\\|\\vendor\\|\.snapshot\.json'
    } | ForEach-Object {
        $files[$_.FullName] = @{
            Size = $_.Length
            Modified = $_.LastWriteTime.ToString("yyyy-MM-dd HH:mm:ss")
            Hash = (Get-FileHash $_.FullName -Algorithm MD5 -ErrorAction SilentlyContinue).Hash
        }
    }
    return $files
}

Write-Info "======================================================================================================"
Write-Info "                            مراقب التغييرات الآلي - بدء المراقبة"
Write-Info "======================================================================================================"
Write-Info "المسار: $projectPath"
Write-Info "ملف السجل: $logFile"
Write-Info ""

# إنشاء Snapshot أولي إذا لم يكن موجوداً
if (-not (Test-Path $snapshotFile)) {
    Write-Info "➜ إنشاء Snapshot أولي..."
    $currentFiles = Get-FilesList
    $currentFiles | ConvertTo-Json -Depth 10 | Set-Content -Path $snapshotFile -Encoding UTF8
    Write-Success "✓ تم إنشاء Snapshot - عدد الملفات: $($currentFiles.Count)"
    
    # تسجيل بدء المراقبة
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $entry = "`n[$timestamp] - بدء المراقبة الآلية`n"
    $entry += "النوع: نظام`n"
    $entry += "عدد الملفات المراقبة: $($currentFiles.Count)`n"
    $entry += "الحالة: نشط ✓`n"
    $entry += ("-" * 80) + "`n`n"
    Add-Content -Path $logFile -Value $entry -Encoding UTF8
}

Write-Info ""
Write-Success "🔍 المراقبة نشطة - اضغط Ctrl+C للإيقاف"
Write-Info ""
Write-Info "انتظار التغييرات..."
Write-Info "======================================================================================================"
Write-Info ""

# المراقبة المستمرة
$iteration = 0
while ($true) {
    Start-Sleep -Seconds 5  # فحص كل 5 ثواني
    $iteration++
    
    # قراءة الحالة السابقة
    $previousFiles = Get-Content -Path $snapshotFile -Encoding UTF8 | ConvertFrom-Json -AsHashtable
    $currentFiles = Get-FilesList
    
    $changesDetected = $false
    
    # فحص الملفات الجديدة والمعدلة
    foreach ($path in $currentFiles.Keys) {
        if (-not $previousFiles.ContainsKey($path)) {
            # ملف جديد
            $relativePath = $path -replace [regex]::Escape($projectPath), "."
            Write-Change "➜ [إضافة] $relativePath"
            Log-Change -Type "[إضافة] - إنشاء ملف جديد" -File $relativePath
            $changesDetected = $true
        }
        elseif ($currentFiles[$path].Hash -ne $previousFiles[$path].Hash) {
            # ملف معدل
            $relativePath = $path -replace [regex]::Escape($projectPath), "."
            Write-Change "➜ [تعديل] $relativePath"
            Log-Change -Type "[تعديل] - تعديل ملف موجود" -File $relativePath
            $changesDetected = $true
        }
    }
    
    # فحص الملفات المحذوفة
    foreach ($path in $previousFiles.Keys) {
        if (-not $currentFiles.ContainsKey($path)) {
            $relativePath = $path -replace [regex]::Escape($projectPath), "."
            Write-Change "➜ [حذف] $relativePath"
            Log-Change -Type "[حذف] - حذف ملف" -File $relativePath
            $changesDetected = $true
        }
    }
    
    # تحديث الـ Snapshot
    if ($changesDetected) {
        $currentFiles | ConvertTo-Json -Depth 10 | Set-Content -Path $snapshotFile -Encoding UTF8
        Write-Success "✓ تم تحديث السجل - $(Get-Date -Format 'HH:mm:ss')"
        Write-Info ""
    }
    
    # عرض رسالة كل 6 تكرارات (30 ثانية) إذا لم تكن هناك تغييرات
    if (-not $changesDetected -and ($iteration % 6 -eq 0)) {
        Write-Host "." -NoNewline -ForegroundColor DarkGray
    }
}
