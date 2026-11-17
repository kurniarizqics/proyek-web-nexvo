<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
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
        User::create([
            'name' => 'Ketua Nexvo',
            'email' => 'ketua@nexvo.com',
            'password' => Hash::make('password'),
            'role' => 'ketua',
            'division' => 'Inti',
        ]);

        User::create([
            'name' => 'Bendahara Nexvo',
            'email' => 'bendahara@nexvo.com',
            'password' => Hash::make('password'),
            'role' => 'bendahara',
            'division' => 'Keuangan',
        ]);

        User::create([
            'name' => 'Koordiv Acara',
            'email' => 'koordiv@nexvo.com',
            'password' => Hash::make('password'),
            'role' => 'koordiv',
            'division' => 'Acara',
        ]);

        User::create([
            'name' => 'Admin Sistem',
            'email' => 'admin@nexvo.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'division' => 'IT',
        ]);

        // 2. Kategori Kas Default
        Category::create(['name' => 'Iuran Anggota', 'type' => 'income']);
        Category::create(['name' => 'Dana Sponsor', 'type' => 'income']);
        Category::create(['name' => 'Operasional & Logistik', 'type' => 'expense']);
        Category::create(['name' => 'Konsumsi Rapat', 'type' => 'expense']);
    }
}