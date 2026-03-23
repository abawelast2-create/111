<?php
// =============================================================
// mobile.php - Mobile Attendance with Radar & Map (Lightweight)
// =============================================================
header('Alt-Svc: clear');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('X-Version: 4.0.'.time());

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$token = trim($_GET['token'] ?? '');
$employee = null;
$error = '';

if (empty($token)) { $error = 'invalid_link'; }
else {
    $employee = getEmployeeByToken($token);
    if (!$employee) { $error = 'expired_link'; }
}

if ($error) {
    http_response_code(400);
    die("<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>خطأ</title><style>body{font-family:Tahoma;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#0c1219;color:#e74c3c;text-align:center;padding:20px}.box{background:#131d28;padding:40px;border-radius:16px;border:1px solid #e74c3c33}h2{margin-bottom:10px}p{color:#888;font-size:14px}</style></head><body><div class='box'><h2>⚠️ رابط غير صالح</h2><p>تأكد من استخدام الرابط الصحيح</p></div></body></html>");
}

$branchId = ($employee && !empty($employee['branch_id'])) ? (int)$employee['branch_id'] : null;
$schedule = getBranchSchedule($branchId);
$workLat = (float) getSystemSetting('work_latitude', '24.572307');
$workLon = (float) getSystemSetting('work_longitude', '46.602552');
$geofenceRadius = (int) getSystemSetting('geofence_radius', '500');
$branchName = '';

if ($branchId) {
    $branchStmt = db()->prepare("SELECT name, latitude, longitude, geofence_radius FROM branches WHERE id = ? AND is_active = 1");
    $branchStmt->execute([$branchId]);
    $branch = $branchStmt->fetch();
    if ($branch) {
        $workLat = (float)$branch['latitude'];
        $workLon = (float)$branch['longitude'];
        $geofenceRadius = (int)$branch['geofence_radius'];
        $branchName = $branch['name'];
    }
}

$todayStatus = 'none';
$checkInTime = null;
$checkOutTime = null;

if ($employee) {
    $stmt = db()->prepare("SELECT type, timestamp FROM attendances WHERE employee_id=? AND attendance_date=CURDATE() ORDER BY timestamp ASC");
    $stmt->execute([$employee['id']]);
    foreach ($stmt->fetchAll() as $rec) {
        if ($rec['type']==='in' && !$checkInTime) $checkInTime = $rec['timestamp'];
        if ($rec['type']==='out') $checkOutTime = $rec['timestamp'];
    }
    if ($checkOutTime) $todayStatus = 'checked_out';
    elseif ($checkInTime) $todayStatus = 'checked_in';
}

$now = new DateTime();
$coTime = DateTime::createFromFormat('H:i:s', $schedule['check_out_start_time']) ?: DateTime::createFromFormat('H:i', $schedule['check_out_start_time']);
$showCheckoutButton = false;
if ($coTime) {
    $coTime->modify("-{$schedule['checkout_show_before']} minutes");
    $showCheckoutButton = $now >= $coTime;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no,viewport-fit=cover">
<meta name="theme-color" content="#0a0f16">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title><?=htmlspecialchars($employee['name'])?></title>
<link rel="icon" type="image/png" href="<?=SITE_URL?>/assets/images/loogo.png">
<style>
:root{
  --pri:#d4722a;--pri-dk:#a85518;--pri-lt:#e69050;
  --bg:#0a0f16;--card:#111921;--card2:#15202b;
  --txt:#e2e8f0;--dim:#64748b;
  --grn:#10b981;--grn2:#34d399;
  --red:#ef4444;--red2:#f87171;
  --blu:#3b82f6;--ylw:#fbbf24;
  --rd:16px;
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent}
html,body{height:100%;overflow:hidden;overscroll-behavior:none}
body{font-family:'Segoe UI',Tahoma,Arial,sans-serif;background:var(--bg);color:var(--txt);display:flex;flex-direction:column;height:100vh;height:100dvh;position:relative}

/* Header */
.hdr{display:flex;align-items:center;gap:12px;padding:12px 16px;background:linear-gradient(135deg,var(--card) 0%,var(--card2) 100%);border-bottom:1px solid rgba(255,255,255,0.06);flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,0.3);z-index:20}
.hdr img{width:36px;height:36px;border-radius:50%;border:2px solid var(--pri);object-fit:cover;box-shadow:0 2px 6px rgba(212,114,42,0.3)}
.hdr-i{flex:1;min-width:0}
.hdr-n{font-size:15px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:2px}
.hdr-b{font-size:11px;color:var(--dim);display:flex;align-items:center;gap:4px}
.hdr-b::before{content:'📍';font-size:10px}
.hdr-c{font-size:15px;font-weight:700;color:var(--pri);font-variant-numeric:tabular-nums;direction:ltr;letter-spacing:0.5px}

/* Radar Zone */
.rz{flex:1 1 0;display:flex;align-items:center;justify-content:center;padding:10px;position:relative;min-height:0;overflow:hidden;background:radial-gradient(circle at center,rgba(16,185,129,0.03) 0%,transparent 70%)}
.rw{position:relative;border-radius:50%;box-shadow:0 8px 32px rgba(0,0,0,0.4),inset 0 0 0 3px rgba(16,185,129,0.1)}
#map{width:100%;height:100%;border-radius:50%;z-index:1;overflow:hidden}
.leaflet-container{background:#0a0f16!important;border-radius:50%}
.dark-tiles{filter:brightness(0.6) invert(1) contrast(1.8) hue-rotate(200deg) saturate(0.3) brightness(0.8)}
.leaflet-tile-pane{border-radius:50%;overflow:hidden}
.leaflet-control-container{display:none!important}

/* Radar Canvas Overlay */
canvas#radar{position:absolute;top:0;left:0;width:100%;height:100%;border-radius:50%;pointer-events:none;z-index:10}

/* Distance Badge */
.db{position:absolute;top:8px;left:50%;transform:translateX(-50%);padding:6px 16px;border-radius:20px;font-size:12px;font-weight:700;background:rgba(0,0,0,0.85);color:var(--txt);border:1px solid rgba(255,255,255,0.1);z-index:15;white-space:nowrap;backdrop-filter:blur(10px);box-shadow:0 4px 12px rgba(0,0,0,0.4)}
.db.ok{border-color:var(--grn);background:rgba(16,185,129,0.15);color:var(--grn2)}
.db.no{border-color:var(--red);background:rgba(239,68,68,0.15);color:var(--red2)}

/* Bottom Panel */
.bot{padding:16px 16px;padding-bottom:max(20px,calc(env(safe-area-inset-bottom) + 16px));background:linear-gradient(135deg,var(--card) 0%,var(--card2) 100%);border-top:1px solid rgba(255,255,255,0.06);flex-shrink:0;box-shadow:0 -2px 12px rgba(0,0,0,0.3);z-index:20;position:relative}
.sr{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:14px;font-size:13px;color:var(--dim);font-weight:500}
.sd{width:10px;height:10px;border-radius:50%;display:inline-block;position:relative}
.sd::after{content:'';position:absolute;inset:-2px;border-radius:50%;opacity:0.3}
.sd.i{background:var(--grn2);box-shadow:0 0 8px rgba(52,211,153,0.5)}
.sd.i::after{background:var(--grn2)}
.sd.o{background:var(--red2);box-shadow:0 0 8px rgba(248,113,113,0.5)}
.sd.o::after{background:var(--red2)}
.sd.n{background:var(--blu);box-shadow:0 0 8px rgba(59,130,246,0.5)}
.sd.n::after{background:var(--blu)}

/* Action Button */
.ab{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:15px;border:none;border-radius:var(--rd);font-size:16px;font-weight:700;cursor:pointer;color:#fff;transition:all .2s ease;position:relative;overflow:hidden}
.ab::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,0.1),transparent);opacity:0;transition:opacity .2s}
.ab:hover::before{opacity:1}
.ab:active{transform:scale(.97)}
.ab.ci{background:linear-gradient(135deg,#059669,var(--grn));box-shadow:0 4px 14px rgba(16,185,129,.35)}
.ab.co{background:linear-gradient(135deg,#dc2626,var(--red));box-shadow:0 4px 14px rgba(239,68,68,.35)}
.ab.ds{background:#1e293b;box-shadow:none;cursor:default;opacity:.6}

/* GPS Modal */
.gm{position:fixed;inset:0;background:rgba(0,0,0,.9);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;z-index:100;padding:24px}
.gb{background:linear-gradient(135deg,var(--card),var(--card2));border-radius:20px;padding:32px 24px;text-align:center;max-width:320px;width:100%;border:1px solid rgba(255,255,255,.08);box-shadow:0 12px 40px rgba(0,0,0,0.5)}
.gb .gi{font-size:48px;margin-bottom:16px;animation:pulse 2s infinite}
.gb h3{margin-bottom:12px;color:var(--pri);font-size:18px;font-weight:700}
.gb p{color:var(--dim);font-size:13px;margin-bottom:20px;line-height:1.7}
.gb button{background:linear-gradient(135deg,var(--pri-dk),var(--pri));color:#fff;border:none;padding:12px 28px;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 4px 12px rgba(212,114,42,.3);transition:all .2s}
.gb button:active{transform:scale(.95)}

@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}

/* Loading Spinner */
.ld{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:20}
.ld::after{content:'';width:40px;height:40px;border:4px solid rgba(16,185,129,0.2);border-top-color:var(--grn);border-radius:50%;animation:spin 0.8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>

<div class="hdr">
  <img src="<?=SITE_URL?>/assets/images/loogo.png" alt="Logo" onerror="this.style.display='none'">
  <div class="hdr-i">
    <div class="hdr-n"><?=htmlspecialchars($employee['name'])?></div>
    <div class="hdr-b"><?=htmlspecialchars($branchName)?></div>
  </div>
  <div class="hdr-c" id="clk">--:--:--</div>
</div>
<!-- v4.0 -->

<div class="rz">
  <div class="rw" id="rw">
    <div id="map"></div>
    <canvas id="radar"></canvas>
    <div class="db" id="db">📍 جاري تحديد الموقع...</div>
    <div class="ld" id="ld"></div>
  </div>
</div>

<div class="bot">
  <div class="sr">
    <?php if($todayStatus==='checked_in'):?><span class="sd i"></span> حضور <?=$checkInTime?date('H:i',strtotime($checkInTime)):''?>
    <?php elseif($todayStatus==='checked_out'):?><span class="sd o"></span> انصراف <?=$checkOutTime?date('H:i',strtotime($checkOutTime)):''?>
    <?php else:?><span class="sd n"></span> لم يتم التسجيل<?php endif;?>
  </div>
  <?php if($todayStatus==='none'):?>
    <button class="ab ci" id="aBtn" onclick="doA('check-in')">📥 تسجيل الحضور</button>
  <?php elseif($todayStatus==='checked_in' && $showCheckoutButton):?>
    <button class="ab co" id="aBtn" onclick="doA('check-out')">📤 تسجيل الانصراف</button>
  <?php elseif($todayStatus==='checked_in'):?>
    <button class="ab ds" disabled>⏳ الانصراف لاحقاً</button>
  <?php else:?>
    <button class="ab ds" disabled>✅ انتهى اليوم</button>
  <?php endif;?>
</div>

<div class="gm" id="gm" style="display:none">
  <div class="gb">
    <div class="gi">📍</div>
    <h3>مطلوب إذن الموقع</h3>
    <p>يحتاج النظام للوصول إلى موقعك لتسجيل الحضور</p>
    <button onclick="rGPS()">إعادة المحاولة</button>
  </div>
</div>

<script>
const C={ei:<?=(int)$employee['id']?>,tk:'<?=htmlspecialchars($token,ENT_QUOTES)?>',bLa:<?=(float)$workLat?>,bLo:<?=(float)$workLon?>,gR:<?=(int)$geofenceRadius?>,mg:10};
let map,branchMarker,userMarker,geofenceCircle,marginCircle,radarCanvas,ctx,tLa=null,tLo=null,sw=0;
const SP=0.018;

// Clock
function tk(){
  const d=new Date();
  document.getElementById('clk').textContent=String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0')+':'+String(d.getSeconds()).padStart(2,'0');
}
setInterval(tk,1000);tk();

// Haversine distance
function hv(a1,o1,a2,o2){
  const p=Math.PI/180,da=(a2-a1)*p,dg=(o2-o1)*p;
  const a=Math.sin(da/2)**2+Math.cos(a1*p)*Math.cos(a2*p)*Math.sin(dg/2)**2;
  return 6371e3*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}

// Initialize map and canvas
function iMap(){
  const wr=document.getElementById('rw');
  const bH=document.body.clientHeight;
  const hH=document.querySelector('.hdr').offsetHeight;
  const btH=document.querySelector('.bot').offsetHeight;
  const av=bH-hH-btH-32;
  const sz=Math.max(240,Math.min(document.body.clientWidth-32,av,480));
  wr.style.width=sz+'px';wr.style.height=sz+'px';
  
  // Load Leaflet dynamically
  if(!window.L){
    const lf=document.createElement('link');
    lf.rel='stylesheet';
    lf.href='../assets/vendor/leaflet/leaflet.min.css';
    document.head.appendChild(lf);
    
    const sc=document.createElement('script');
    sc.src='../assets/vendor/leaflet/leaflet.min.js';
    sc.onload=()=>setTimeout(iLeaflet,100);
    document.body.appendChild(sc);
  }else{iLeaflet()}
}

function iLeaflet(){
  document.getElementById('ld').style.display='none';
  
  // Create Leaflet map
  map=L.map('map',{
    center:[C.bLa,C.bLo],
    zoom:16,
    zoomControl:false,
    attributionControl:false,
    dragging:true,
    scrollWheelZoom:false,
    doubleClickZoom:false,
    boxZoom:false,
    keyboard:false
  });
  
  // Dark tile layer
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    maxZoom:19,
    className:'dark-tiles'
  }).addTo(map);
  
  // Branch marker (center)
  branchMarker=L.circleMarker([C.bLa,C.bLo],{
    radius:8,
    fillColor:'#d4722a',
    fillOpacity:1,
    color:'#fff',
    weight:2,
    opacity:1
  }).addTo(map);
  
  // Geofence circle
  geofenceCircle=L.circle([C.bLa,C.bLo],{
    radius:C.gR,
    fillColor:'#10b981',
    fillOpacity:0.08,
    color:'#10b981',
    weight:2,
    opacity:0.4,
    dashArray:'8, 6'
  }).addTo(map);
  
  // +10m margin circle
  marginCircle=L.circle([C.bLa,C.bLo],{
    radius:C.gR+C.mg,
    fillColor:'transparent',
    fillOpacity:0,
    color:'#fbbf24',
    weight:1.5,
    opacity:0.25,
    dashArray:'4, 8'
  }).addTo(map);
  
  // User marker (will update with GPS)
  userMarker=L.circleMarker([C.bLa,C.bLo],{
    radius:10,
    fillColor:'#10b981',
    fillOpacity:0.9,
    color:'#fff',
    weight:3,
    opacity:1
  }).addTo(map);
  
  // Initialize radar canvas overlay
  iRadar();
  
  // Start GPS
  iGPS();
}

function iRadar(){
  radarCanvas=document.getElementById('radar');
  const sz=radarCanvas.parentElement.offsetWidth;
  const dp=window.devicePixelRatio||1;
  radarCanvas.width=sz*dp;
  radarCanvas.height=sz*dp;
  radarCanvas.style.width=sz+'px';
  radarCanvas.style.height=sz+'px';
  ctx=radarCanvas.getContext('2d');
  ctx.scale(dp,dp);
  
  // Start radar animation
  drRadar();
}

function drRadar(){
  const w=radarCanvas.width/window.devicePixelRatio;
  const h=radarCanvas.height/window.devicePixelRatio;
  const cx=w/2,cy=h/2,r=Math.min(cx,cy)-2;
  
  ctx.clearRect(0,0,w,h);
  
  // Radar sweep animation
  sw+=SP;
  if(sw>Math.PI*2)sw-=Math.PI*2;
  
  // Draw sweep cone (fading trail)
  const cn=1.2,sl=50;
  for(let i=0;i<sl;i++){
    const f=i/sl;
    const a=sw-cn*f;
    const op=(1-f)*(1-f)*0.12;
    ctx.beginPath();
    ctx.moveTo(cx,cy);
    ctx.arc(cx,cy,r,a-0.02,a+0.02);
    ctx.closePath();
    ctx.fillStyle='rgba(16,185,129,'+op+')';
    ctx.fill();
  }
  
  // Sweep line with gradient
  const lx=cx+Math.cos(sw-Math.PI/2)*r;
  const ly=cy+Math.sin(sw-Math.PI/2)*r;
  const lg=ctx.createLinearGradient(cx,cy,lx,ly);
  lg.addColorStop(0,'rgba(16,185,129,0.15)');
  lg.addColorStop(0.6,'rgba(16,185,129,0.6)');
  lg.addColorStop(1,'rgba(52,211,153,0.9)');
  ctx.beginPath();
  ctx.moveTo(cx,cy);
  ctx.lineTo(lx,ly);
  ctx.strokeStyle=lg;
  ctx.lineWidth=2;
  ctx.stroke();
  
  // Tip glow
  const tg=ctx.createRadialGradient(lx,ly,0,lx,ly,16);
  tg.addColorStop(0,'rgba(52,211,153,0.5)');
  tg.addColorStop(1,'rgba(52,211,153,0)');
  ctx.beginPath();
  ctx.arc(lx,ly,16,0,Math.PI*2);
  ctx.fillStyle=tg;
  ctx.fill();
  
  requestAnimationFrame(drRadar);
}

// GPS tracking
function iGPS(){
  if(!navigator.geolocation){
    document.getElementById('gm').style.display='flex';
    return;
  }
  
  navigator.geolocation.watchPosition(p=>{
    tLa=p.coords.latitude;
    tLo=p.coords.longitude;
    
    // Update user marker on map with smooth animation
    if(userMarker){
      userMarker.setLatLng([tLa,tLo]);
      
      // Calculate distance
      const d=hv(C.bLa,C.bLo,tLa,tLo);
      const ok=d<=C.gR;
      
      // Update marker color
      userMarker.setStyle({
        fillColor:ok?'#10b981':'#ef4444',
        color:'#fff'
      });
      
      // Update distance badge
      const badge=document.getElementById('db');
      badge.className='db '+(ok?'ok':'no');
      badge.textContent=(ok?'✓ داخل النطاق ':'✗ خارج النطاق ')+Math.round(d)+' م';
      
      // Recenter map to show both branch and user
      if(d>C.gR*0.3){
        map.fitBounds([
          [C.bLa,C.bLo],
          [tLa,tLo]
        ],{padding:[50,50],maxZoom:16});
      }
    }
    
    document.getElementById('gm').style.display='none';
  },err=>{
    console.error('GPS Error:',err);
    document.getElementById('gm').style.display='flex';
  },{
    enableHighAccuracy:true,
    timeout:15000,
    maximumAge:0
  });
}

function rGPS(){
  document.getElementById('gm').style.display='none';
  iGPS();
}

// Check-in/out action
function doA(ac){
  if(!tLa){
    alert('⏳ جاري تحديد موقعك، الرجاء الانتظار...');
    return;
  }
  
  const btn=document.getElementById('aBtn');
  if(!btn)return;
  
  const orig=btn.innerHTML;
  btn.disabled=true;
  btn.innerHTML='⏳ جاري التسجيل...';
  
  fetch('<?=SITE_URL?>/api/'+ac+'.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({
      employee_id:C.ei,
      token:C.tk,
      latitude:tLa,
      longitude:tLo
    })
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      btn.innerHTML='✅ '+(d.message||'تم بنجاح');
      btn.className='ab ds';
      setTimeout(()=>location.reload(),1200);
    }else{
      alert('❌ '+d.message||'حدث خطأ');
      btn.disabled=false;
      btn.innerHTML=orig;
    }
  })
  .catch(err=>{
    console.error(err);
    // Retry once
    fetch('<?=SITE_URL?>/api/'+ac+'.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({employee_id:C.ei,token:C.tk,latitude:tLa,longitude:tLo})
    })
    .then(r=>r.json())
    .then(d=>{
      if(d.success){
        btn.innerHTML='✅ تم';
        setTimeout(()=>location.reload(),1200);
      }else{
        alert(d.message||'خطأ');
        btn.disabled=false;
        btn.innerHTML=orig;
      }
    })
    .catch(()=>{
      alert('⚠️ خطأ في الاتصال، تأكد من الإنترنت');
      btn.disabled=false;
      btn.innerHTML=orig;
    });
  });
}

// Unregister old service workers to clear cache
if('serviceWorker' in navigator){
  navigator.serviceWorker.getRegistrations().then(regs=>{
    regs.forEach(r=>r.unregister());
  });
  if(caches) caches.keys().then(k=>k.forEach(n=>caches.delete(n)));
}

// Initialize on load
window.addEventListener('load',iMap);
window.addEventListener('resize',()=>{
  if(map)map.invalidateSize();
  if(radarCanvas)iRadar();
});
</script>
</body>
</html>
