<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // الإعدادات الأولية
        $settings = [
            ['setting_key' => 'work_latitude',        'setting_value' => '24.572307',               'description' => 'خط عرض موقع العمل'],
            ['setting_key' => 'work_longitude',       'setting_value' => '46.602552',               'description' => 'خط طول موقع العمل'],
            ['setting_key' => 'geofence_radius',      'setting_value' => '25',                      'description' => 'نصف قطر الجيوفينس بالمتر'],
            ['setting_key' => 'work_start_time',      'setting_value' => '08:00',                   'description' => 'بداية الدوام الرسمي'],
            ['setting_key' => 'work_end_time',        'setting_value' => '16:00',                   'description' => 'نهاية الدوام الرسمي'],
            ['setting_key' => 'check_in_start_time',  'setting_value' => '07:00',                   'description' => 'بداية وقت تسجيل الدخول'],
            ['setting_key' => 'check_in_end_time',    'setting_value' => '10:00',                   'description' => 'نهاية وقت تسجيل الدخول'],
            ['setting_key' => 'check_out_start_time', 'setting_value' => '15:00',                   'description' => 'بداية وقت تسجيل الانصراف'],
            ['setting_key' => 'check_out_end_time',   'setting_value' => '20:00',                   'description' => 'نهاية وقت تسجيل الانصراف'],
            ['setting_key' => 'checkout_show_before',  'setting_value' => '30',                     'description' => 'دقائق قبل إظهار زر الانصراف'],
            ['setting_key' => 'allow_overtime',       'setting_value' => '1',                       'description' => 'السماح بالدوام الإضافي'],
            ['setting_key' => 'overtime_start_after',  'setting_value' => '60',                     'description' => 'دقائق بعد نهاية الدوام لبدء الإضافي'],
            ['setting_key' => 'overtime_min_duration', 'setting_value' => '30',                     'description' => 'الحد الأدنى للدوام الإضافي بالدقائق'],
            ['setting_key' => 'site_name',            'setting_value' => 'نظام الحضور والانصراف',   'description' => 'اسم النظام'],
            ['setting_key' => 'company_name',         'setting_value' => '',                        'description' => 'اسم الشركة'],
            ['setting_key' => 'timezone',             'setting_value' => 'Asia/Riyadh',             'description' => 'المنطقة الزمنية'],
        ];
        DB::table('settings')->insert($settings);

        // المدير الافتراضي
        DB::table('admins')->insert([
            'username'      => 'admin',
            'password_hash' => Hash::make('Admin@1234'),
            'full_name'     => 'مدير النظام',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // الفروع
        $branches = [
            ['name' => 'صرح الاوروبي',  'latitude' => 24.57231000, 'longitude' => 46.60256100, 'geofence_radius' => 25],
            ['name' => 'صرح الرئيسي',   'latitude' => 24.57236300, 'longitude' => 46.60278800, 'geofence_radius' => 25],
            ['name' => 'فضاء 1',        'latitude' => 24.57107600, 'longitude' => 46.61104800, 'geofence_radius' => 25],
            ['name' => 'فضاء 2',        'latitude' => 24.56932700, 'longitude' => 46.61478200, 'geofence_radius' => 25],
            ['name' => 'صرح الامريكي',  'latitude' => 24.57246600, 'longitude' => 46.60298500, 'geofence_radius' => 25],
        ];
        foreach ($branches as $b) {
            DB::table('branches')->insert(array_merge($b, [
                'work_start_time'      => '08:00:00',
                'work_end_time'        => '16:00:00',
                'check_in_start_time'  => '07:00:00',
                'check_in_end_time'    => '10:00:00',
                'check_out_start_time' => '15:00:00',
                'check_out_end_time'   => '20:00:00',
                'checkout_show_before' => 30,
                'allow_overtime'       => true,
                'overtime_start_after' => 60,
                'overtime_min_duration'=> 30,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]));
        }

        $branchMap = DB::table('branches')->pluck('id', 'name');

        $employees = [
            ['إسلام', 'موظف', '+966549820672', 'صرح الاوروبي'],
            ['حسني', 'موظف', '+966537491699', 'صرح الاوروبي'],
            ['بخاري', 'موظف', '+923095734018', 'صرح الاوروبي'],
            ['أبو سليمان', 'موظف', '+966500651865', 'صرح الاوروبي'],
            ['صابر', 'موظف', '+966570899595', 'صرح الاوروبي'],
            ['زاهر', 'موظف', '+966546481759', 'صرح الرئيسي'],
            ['أيمن', 'موظف', '+966555090870', 'صرح الرئيسي'],
            ['أمجد', 'موظف', '+966555106370', 'صرح الرئيسي'],
            ['نجيب', 'موظف', '+923475914157', 'صرح الرئيسي'],
            ['محمد جلال', 'موظف', '+966573603727', 'فضاء 1'],
            ['محمد بلال', 'موظف', '+966503863694', 'فضاء 1'],
            ['رمضان عباس علي', 'موظف', '+966594119151', 'فضاء 1'],
            ['محمد أفريدي', 'موظف', '+966565722089', 'فضاء 1'],
            ['سلفادور ديلا', 'موظف', '+966541756875', 'فضاء 1'],
            ['محمد خان', 'موظف', '+966594163035', 'فضاء 1'],
            ['أندريس بورتس', 'موظف', '+966590087140', 'فضاء 1'],
            ['حسن (آصف)', 'موظف', '+966582670736', 'فضاء 1'],
            ['رمضان أويس علي', 'موظف', '+966531096640', 'فضاء 1'],
            ['ساكدا بندولا', 'موظف', '+966572746930', 'فضاء 1'],
            ['شحاتة', 'موظف', '+966545677065', 'فضاء 1'],
            ['منذر محمد', 'موظف', '+966556593723', 'فضاء 1'],
            ['مصطفى عوض سعد', 'موظف', '+966555106370', 'فضاء 1'],
            ['عنايات', 'موظف', '+966582329361', 'فضاء 1'],
            ['محمد خميس', 'موظف', '+966153254390', 'فضاء 1'],
            ['عبد الهادي يونس', 'موظف', '+966159626196', 'فضاء 1'],
            ['عبدالله اليمني', 'موظف', '+966536765655', 'فضاء 2'],
            ['أفضل', 'موظف', '+966599258117', 'فضاء 2'],
            ['حبيب', 'موظف', '+966573263203', 'فضاء 2'],
            ['إمتي', 'موظف', '+966595806604', 'فضاء 2'],
            ['عرنوس', 'موظف', '+966500089178', 'فضاء 2'],
            ['عرفان', 'موظف', '+966597255093', 'فضاء 2'],
            ['وسيم', 'موظف', '+966531806242', 'فضاء 2'],
            ['جهاد', 'موظف', '+966508512355', 'فضاء 2'],
            ['ابانوب', 'موظف', '+966536781886', 'فضاء 2'],
            ['قتيبة', 'موظف', '+966597024453', 'فضاء 2'],
            ['وداعة الله', 'موظف', '+966571761401', 'صرح الامريكي'],
            ['وقاص', 'موظف', '+966598997295', 'صرح الامريكي'],
            ['شعبان', 'موظف', '+966595153544', 'صرح الامريكي'],
            ['مصعب', 'موظف', '+966555792273', 'صرح الامريكي'],
            ['بلال', 'موظف', '+966594154009', 'صرح الامريكي'],
        ];

        // Generate PINs from last 4 digits of phone, handle duplicates
        $usedPins = [];
        foreach ($employees as $emp) {
            $phone = $emp[2];
            $digits = preg_replace('/[^0-9]/', '', $phone);
            $pin = substr($digits, -4);

            if (in_array($pin, $usedPins)) {
                // Duplicate: generate a unique random PIN
                do {
                    $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                } while (in_array($pin, $usedPins));
            }
            $usedPins[] = $pin;

            DB::table('employees')->insert([
                'name'         => $emp[0],
                'job_title'    => $emp[1],
                'pin'          => $pin,
                'phone'        => $phone,
                'unique_token' => bin2hex(random_bytes(32)),
                'branch_id'    => $branchMap[$emp[3]] ?? null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}
