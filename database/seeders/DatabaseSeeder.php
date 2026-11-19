<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Proposal;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Role Sesuai UI Figma (Password semua akun: password)
        $ketua = User::updateOrCreate(
            ['email' => 'ketua@nexvo.com'],
            [
                'name'     => 'Ketua Nexvo',
                'password' => Hash::make('password'),
                'role'     => 'ketua',
                'division' => 'Inti',
            ]
        );

        $bendahara = User::updateOrCreate(
            ['email' => 'bendahara@nexvo.com'],
            [
                'name'     => 'Bendahara Nexvo',
                'password' => Hash::make('password'),
                'role'     => 'bendahara',
                'division' => 'Keuangan',
            ]
        );

        $koordiv = User::updateOrCreate(
            ['email' => 'koordiv@nexvo.com'],
            [
                'name'     => 'Koordiv Acara',
                'password' => Hash::make('password'),
                'role'     => 'koordiv',
                'division' => 'Acara',
            ]
        );

        $anggota = User::updateOrCreate(
            ['email' => 'anggota@nexvo.com'],
            [
                'name'     => 'Anggota Acara',
                'password' => Hash::make('password'),
                'role'     => 'anggota',
                'division' => 'Acara',
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@nexvo.com'],
            [
                'name'     => 'Admin Sistem',
                'password' => Hash::make('password'),
                'role'     => 'admin',
                'division' => 'IT',
            ]
        );

        // 2. Kategori Kas Default
        $catIuran = Category::firstOrCreate(['name' => 'Iuran Anggota', 'type' => 'income']);
        $catSponsor = Category::firstOrCreate(['name' => 'Dana Sponsor', 'type' => 'income']);
        $catOperasional = Category::firstOrCreate(['name' => 'Operasional & Logistik', 'type' => 'expense']);
        $catKonsumsi = Category::firstOrCreate(['name' => 'Konsumsi Rapat', 'type' => 'expense']);

        // 3. Data Awal Buku Kas (Hanya diisi jika tabel transaksi masih kosong)
        if (Transaction::count() === 0) {
            Transaction::create([
                'user_id'          => $bendahara->id,
                'category_id'      => $catSponsor->id,
                'proposal_id'      => null,
                'amount'           => 5000000,
                'transaction_date' => now()->subDays(7)->toDateString(),
                'description'      => 'Saldo Awal & Dana Sponsor Kepengurusan',
                'proof_path'       => null,
            ]);

            Transaction::create([
                'user_id'          => $bendahara->id,
                'category_id'      => $catIuran->id,
                'proposal_id'      => null,
                'amount'           => 1000000,
                'transaction_date' => now()->subDays(4)->toDateString(),
                'description'      => 'Iuran Kas Bulanan Anggota',
                'proof_path'       => null,
            ]);

            Transaction::create([
                'user_id'          => $bendahara->id,
                'category_id'      => $catOperasional->id,
                'proposal_id'      => null,
                'amount'           => 350000,
                'transaction_date' => now()->subDays(2)->toDateString(),
                'description'      => 'Pengadaan ATK & Kesekretariatan',
                'proof_path'       => null,
            ]);
        }

        // 4. Contoh Pengajuan Awal dari Anggota (Menunggu Validasi Koordiv Acara)
        if (Proposal::count() === 0) {
            Proposal::create([
                'user_id' => $anggota->id,
                'title'   => 'Kebutuhan Perlengkapan Seminar Kampus',
                'amount'  => 650000,
                'reason'  => 'Cetak banner, sertifikat pembicara, dan konsumsi panitia divisi acara.',
                'status'  => 'pending_koordiv',
            ]);
        }
    }
}