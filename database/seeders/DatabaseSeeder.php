<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Position;
use App\Models\Role;
use App\Models\SlaPolicy;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = collect(['Administrator', 'Karyawan'])->mapWithKeys(fn ($name) => [$name => Role::firstOrCreate(['name' => $name])]);
        $departments = collect([
            ['name' => 'IT', 'code' => 'IT'],
            ['name' => 'HR', 'code' => 'HR'],
            ['name' => 'Akunting', 'code' => 'FIN'],
            ['name' => 'Operasional', 'code' => 'OPS'],
            ['name' => 'Marketing', 'code' => 'MKT'],
            ['name' => 'Produksi', 'code' => 'PRD'],
        ])->mapWithKeys(fn ($department) => [
            $department['code'] => Department::firstOrCreate(['code' => $department['code']], ['name' => $department['name']]),
        ]);
        Department::where('code', 'FIN')->update(['name' => 'Akunting', 'is_active' => true]);
        Department::where('code', 'SLS')->update(['is_active' => false]);
        User::whereIn('department_id', Department::where('is_active', false)->pluck('id'))->update(['is_active' => false]);
        foreach ($departments as $department) {
            foreach (['Manager Departemen', 'Kepala Departemen', 'Karyawan'] as $position) {
                Position::firstOrCreate(['department_id' => $department->id, 'name' => $position], ['is_active' => true]);
            }
        }
        $categories = [
            'Lainnya' => [120, 960],
            'Hardware' => [60, 480], 'Software & Application' => [60, 480],
            'Network & Internet' => [30, 240], 'Account & Access Management' => [60, 240],
            'Email & Communication' => [60, 240], 'Website & Internal System' => [30, 480],
            'Database Management' => [60, 480], 'Security & Cybersecurity' => [30, 240],
            'Server & Infrastructure' => [30, 240], 'Data & File Management' => [60, 480],
            'IT Request & Procurement' => [120, 960], 'Maintenance & IT Support' => [120, 480],
        ];
        foreach ($categories as $name => [$response, $resolution]) {
            $category = Category::firstOrCreate(['name' => $name]);
            SlaPolicy::updateOrCreate(['category_id' => $category->id], [
                'response_target_minutes' => $response,
                'resolution_target_minutes' => $resolution,
                'is_active' => true,
            ]);
            $catalog = [
                'Lainnya' => ['Umum' => ['Lainnya']],
                'Hardware' => ['Komputer/Laptop' => ['Komputer tidak menyala', 'Komputer lambat', 'Monitor tidak tampil', 'Keyboard atau mouse rusak', 'Layar bergaris atau kedap-kedip', 'Komputer mati tiba-tiba', 'Port USB atau HDMI bermasalah', 'Laptop tidak terisi daya', 'Printer tidak bisa mencetak', 'Printer macet kertas', 'Kualitas cetak buruk', 'Scanner tidak berfungsi', 'Proyektor tidak terdeteksi', 'Webcam atau microphone bermasalah', 'Kabel dan adaptor perlu diganti']],
                'Software & Application' => ['Aplikasi' => ['Aplikasi tidak bisa dibuka', 'Aplikasi error atau crash', 'Instalasi aplikasi', 'Update aplikasi', 'Lisensi aplikasi bermasalah', 'Microsoft Office tidak aktif', 'Excel atau Word file rusak', 'Spreadsheet tidak bisa disimpan', 'Aplikasi tidak dapat login', 'Sistem absensi bermasalah', 'Aplikasi kehadiran bermasalah', 'ERP tidak dapat digunakan', 'Aplikasi produksi error', 'Zoom atau aplikasi rapat bermasalah', 'Antivirus memblokir aplikasi', 'Permintaan instalasi aplikasi baru']],
                'Network & Internet' => ['Koneksi' => ['Internet tidak tersambung', 'Wi-Fi lambat atau putus-putus', 'LAN tidak terhubung', 'Sinyal Wi-Fi lemah', 'VPN tidak bisa digunakan', 'Permintaan akses jaringan', 'Akses jaringan dibatasi', 'IP address bermasalah', 'Switch atau access point offline', 'Printer jaringan tidak ditemukan', 'File sharing jaringan tidak terbuka', 'Website kantor tidak bisa dibuka dari luar']],
                'Account & Access Management' => ['Akun dan Akses' => ['Lupa password', 'Akun terkunci', 'Permintaan akun baru', 'Permintaan akun karyawan baru', 'Permintaan perubahan akun', 'Permintaan hak akses', 'Pencabutan hak akses', 'Akun email perlu dipulihkan', 'Akun aplikasi perlu diaktifkan', 'Reset PIN atau OTP', 'Akun pegawai resign perlu dinonaktifkan']],
                'Email & Communication' => ['Email' => ['Email tidak bisa dikirim', 'Email tidak bisa diterima', 'Kapasitas mailbox penuh', 'Spam atau phishing', 'Konfigurasi email', 'Email masuk terlambat', 'Lampiran email tidak bisa dikirim', 'Email salah tautan atau tautan rusak', 'Setup email di HP', 'Setup email di aplikasi mail', 'Grup email perlu ditambah atau dihapus', 'Domain atau tanda tangan email bermasalah']],
                'Website & Internal System' => ['Sistem Internal' => ['Sistem tidak bisa diakses', 'Halaman error', 'Data tidak muncul', 'Permintaan perubahan fitur', 'Gangguan performa sistem', 'Laporan tidak dapat dicetak', 'Export laporan gagal', 'Pembaharuan data tidak tersimpan', 'Sistem berganti menjadi sesi berakhir', 'Permintaan akun dashboard', 'Menu atau tombol tidak berfungsi', 'Integrasi sistem bermasalah']],
                'Database Management' => ['Database' => ['Database tidak bisa diakses', 'Query lambat', 'Data tidak sinkron', 'Permintaan backup database', 'Permintaan pemulihan data', 'Tabel atau database penuh', 'Data duplikat', 'Perubahan struktur database', 'Laporan database lambat']],
                'Security & Cybersecurity' => ['Keamanan' => ['Indikasi malware atau virus', 'Email phishing', 'Akun diduga diretas', 'Kebocoran data', 'Laporan insiden keamanan', 'Perangkat mencurigakan', 'Pemindahan data tidak sah', 'Laporan situs palsu', 'Permintaan aktivasi keamanan tambahan', 'USB atau media asing diblokir']],
                'Server & Infrastructure' => ['Server' => ['Server tidak bisa diakses', 'Server down', 'Kapasitas server penuh', 'Permintaan konfigurasi server', 'Monitoring server', 'Backup server gagal', 'Layanan server lambat', 'Pemeliharaan server terjadwal', 'Pemulihan server', 'Storage server hampir penuh']],
                'Data & File Management' => ['Data dan File' => ['File terhapus', 'File tidak bisa dibuka', 'Permintaan pemulihan file', 'Permintaan folder bersama', 'Hak akses file bermasalah', 'Folder share tidak terlihat', 'File tidak sinkron', 'Permintaan upload dokumen besar', 'Format file tidak didukung', 'Perbaikan data rusak', 'Permintaan backup dokumen']],
                'IT Request & Procurement' => ['Permintaan IT' => ['Permintaan komputer atau laptop', 'Permintaan printer', 'Permintaan perangkat jaringan', 'Permintaan software', 'Permintaan perangkat pendukung', 'Permintaan headset atau kamera', 'Permintaan keyboard atau mouse', 'Permintaan monitor tambahan', 'Permintaan akun lisensi', 'Permintaan kabel atau adaptor', 'Permintaan kursi atau meja komputer', 'Permintaan akses Ruang IT']],
                'Maintenance & IT Support' => ['Pemeliharaan' => ['Pemeliharaan komputer', 'Pemeliharaan printer', 'Pembersihan perangkat', 'Pemeriksaan rutin perangkat', 'Relokasi perangkat', 'Pemasangan perangkat baru', 'Pemisahan atau pemindahan workstation', 'Pengecekan performa komputer', 'Pengecekan keamanan perangkat', 'Pembaruan sistem operasi', 'Pembaruan BIOS atau firmware']],
            ];
            foreach ($catalog[$name] ?? ['Umum' => ['Kendala '.$name]] as $subcategoryName => $issueNames) {
                $subcategory = \App\Models\Subcategory::firstOrCreate(
                    ['category_id' => $category->id, 'name' => $subcategoryName],
                    ['is_active' => true],
                );
                foreach ($issueNames as $issueName) {
                    \App\Models\IssueType::firstOrCreate(
                        ['category_id' => $category->id, 'subcategory_id' => $subcategory->id, 'name' => $issueName],
                        ['is_active' => true],
                    );
                }
            }
        }
        foreach ([['Low', 1, 'blue'], ['Medium', 2, 'yellow'], ['High', 3, 'orange'], ['Critical', 4, 'red']] as [$name, $level, $color]) {
            Priority::firstOrCreate(['name' => $name], ['level' => $level, 'sort_order' => $level, 'color' => $color]);
        }

        User::whereIn('email', [
            'supervisor@erlass.test',
            'support@erlass.test',
            'karyawan.hr@erlass.test',
            'karyawan.finance@erlass.test',
            'karyawan.operasional@erlass.test',
            'manager.marketing@erlass.test',
            'kepala.marketing@erlass.test',
            'manager.produksi@erlass.test',
            'kepala.produksi@erlass.test',
            'test@example.com',
        ])->delete();

        $accounts = [
            ['name' => 'Admin Erlass', 'email' => 'admin@erlass.test', 'role' => 'Administrator', 'department' => 'IT'],
            ['name' => 'User Erlass', 'email' => 'user@erlass.test', 'role' => 'Karyawan', 'department' => 'IT'],
        ];

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'department_id' => $departments[$account['department']]->id,
                    'position_id' => Position::where('department_id', $departments[$account['department']]->id)->where('name', 'Karyawan')->value('id'),
                    'is_active' => true,
                ],
            );
            $user->roles()->sync([$roles[$account['role']]->id]);
        }

    }
}
