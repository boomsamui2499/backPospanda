<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            ['name' => "admin", 'email' => 'admin@mail.com', 'email_verified_at' => NULL, 'username' => 'admin', 'password' => '$2y$10$eLFEe0ERo235iF3Zcmv3AeAC2sojojvS29y6PpPlja/72ZgM3mNyG'
            , 'remember_token' => NULL, 'created_at' => '2022-02-11 23:43:22', 'updated_at' => '2022-02-11 23:43:22', 'last_name' => 'ผู้ดูแลระบบ', 'phone_number' => '0805216250','permission'=>'owner'],
            ['name' => "cashier", 'email' => 'cashier@mail.com', 'email_verified_at' => NULL, 'username' => 'cashier', 'password' => '$2y$10$eLFEe0ERo235iF3Zcmv3AeAC2sojojvS29y6PpPlja/72ZgM3mNyG'
            , 'remember_token' => NULL, 'created_at' => '2022-02-11 23:43:22', 'updated_at' => '2022-02-11 23:43:22', 'last_name' => 'cashier', 'phone_number' => '0805216250','permission'=>'cashier'],
            ['name' => "manager", 'email' => 'manager@mail.com', 'email_verified_at' => NULL, 'username' => 'manager', 'password' => '$2y$10$eLFEe0ERo235iF3Zcmv3AeAC2sojojvS29y6PpPlja/72ZgM3mNyG'
            , 'remember_token' => NULL, 'created_at' => '2022-02-11 23:43:22', 'updated_at' => '2022-02-11 23:43:22', 'last_name' => 'manager', 'phone_number' => '0805216250','permission'=>'manager'],
            ['name' => "test", 'email' => 'test@mail.com', 'email_verified_at' => NULL, 'username' => 'test', 'password' => '$2y$10$eLFEe0ERo235iF3Zcmv3AeAC2sojojvS29y6PpPlja/72ZgM3mNyG'
            , 'remember_token' => NULL, 'created_at' => '2022-02-11 23:43:22', 'updated_at' => '2022-02-11 23:43:22', 'last_name' => 'ผู้ทดสอบระบบ', 'phone_number' => '0805216250','permission'=>'test'],];
        foreach ($users as $user) {
            DB::table('users')->insert([$user]);
        }
    }
}
