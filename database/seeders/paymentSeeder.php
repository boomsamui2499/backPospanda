<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class paymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            ['payment_name' => "เงินสด", 'is_special_payment' => 0],
            ['payment_name' => "ติดหนี้", 'is_special_payment' => 0],
            ['payment_name' => "ปัดเศษ", 'is_special_payment' => 0],
            ['payment_name' => "พร้อมเพย์", 'is_special_payment' => 0],
        ];
        foreach ($users as $user) {
            DB::table('payment')->insert([$user]);
        }
    }
}
