<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin account
        DB::table('users')->insert([
            'name' => 'Admin DTU LingoAI',
            'email' => 'admin@dtu-lingoai.edu.vn',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Student accounts
        $students = [
            [
                'name' => 'Nguyễn Văn An',
                'email' => 'an.nguyenvan@student.dtu.edu.vn',
                'password' => 'student123',
            ],
            [
                'name' => 'Trần Thị Bình',
                'email' => 'binh.tranthi@student.dtu.edu.vn',
                'password' => 'student123',
            ],
            [
                'name' => 'Lê Hoàng Cường',
                'email' => 'cuong.lehoang@student.dtu.edu.vn',
                'password' => 'student123',
            ],
            [
                'name' => 'Phạm Thị Dung',
                'email' => 'dung.phamthi@student.dtu.edu.vn',
                'password' => 'student123',
            ],
            [
                'name' => 'Đỗ Minh Đức',
                'email' => 'duc.dominh@student.dtu.edu.vn',
                'password' => 'student123',
            ],
        ];

        foreach ($students as $student) {
            DB::table('users')->insert([
                'name' => $student['name'],
                'email' => $student['email'],
                'password' => Hash::make($student['password']),
                'role' => 'student',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
