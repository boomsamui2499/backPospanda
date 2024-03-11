<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MetadataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = [
            ['meta_module' => "store", 'meta_key' => "store_logo", 'meta_value' => ""],
            ['meta_module' => "store", 'meta_key' => "store_name", 'meta_value' => ""],
            ['meta_module' => "store", 'meta_key' => "store_address", 'meta_value' => ""],
            ['meta_module' => "store", 'meta_key' => "store_tel", 'meta_value' => ""],
            ['meta_module' => "store", 'meta_key' => "store_text_header", 'meta_value' => ""],
            ['meta_module' => "store", 'meta_key' => "store_text_end", 'meta_value' => ""],
            ['meta_module' => "store", 'meta_key' => "store_vat", 'meta_value' => "7"],
            ['meta_module' => "store", 'meta_key' => "store_screen message", 'meta_value' => ""],
            ['meta_module' => "store", 'meta_key' => "store_youtube_url", 'meta_value' => ""],
            ['meta_module' => "store", 'meta_key' => "store_round", 'meta_value' => "2"],
            ['meta_module' => "store", 'meta_key' => "store_point", 'meta_value' => ""],
            ['meta_module' => "printer", 'meta_key' => "printer_name", 'meta_value' => "Quickpos"],
            ['meta_module' => "printer", 'meta_key' => "printer_width", 'meta_value' => "1"],
            ['meta_module' => "store", 'meta_key' => "store_print", 'meta_value' => "1"],
            ['meta_module' => "store", 'meta_key' => "store_promptpay", 'meta_value' => ""],
            ['meta_module' => "store", 'meta_key' => "store_line_token", 'meta_value' => ""],
            ['meta_module' => "store", 'meta_key' => "store_scale_prefix", 'meta_value' => ""],
            ['meta_module' => "store", 'meta_key' => "store_order", 'meta_value' => "2"],
            ['meta_module' => "store", 'meta_key' => "store_cut", 'meta_value' => "2"],
            ['meta_module' => "printer", 'meta_key' => "mode_print", 'meta_value' => "1"],
            ['meta_module' => "store", 'meta_key' => "store_tax_number", 'meta_value' => ""],
            ['meta_module' => "store", 'meta_key' => "store_background ", 'meta_value' => "#E4F8F2"],
            ['meta_module' => "store", 'meta_key' => "store_color_top_bar", 'meta_value' => "#0093A1"],
            ['meta_module' => "store", 'meta_key' => "store_color_main", 'meta_value' => "#0093A1"],
            ['meta_module' => "store", 'meta_key' => "store_color_side_bar", 'meta_value' => "#6AE1DB"],
            ['meta_module' => "store", 'meta_key' => "store_sync_mode", 'meta_value' => "1"],
            ['meta_module' => "store", 'meta_key' => "store_default_vat", 'meta_value' => "0"],
            ['meta_module' => "printer", 'meta_key' => "prefix_receipt", 'meta_value' => ""],
            ['meta_module' => "printer", 'meta_key' => "receipt_number", 'meta_value' => "1"],
            ['meta_module' => "printer", 'meta_key' => "delay_time", 'meta_value' => "6"],
            ['meta_module' => "printer", 'meta_key' => "count_cashier", 'meta_value' => "1"],
            //bill
            ['meta_module' => "bill", 'meta_key' => "message_head_bill", 'meta_value' => ""],
            ['meta_module' => "bill", 'meta_key' => "message_previous_number_bill", 'meta_value' => "เลขที่"],
            ['meta_module' => "bill", 'meta_key' => "fontsize_name", 'meta_value' => "30"],
            ['meta_module' => "bill", 'meta_key' => "fontsize_name_is_bold", 'meta_value' => "0"],
            ['meta_module' => "bill", 'meta_key' => "fontsize_head_bill", 'meta_value' => "30"],
            ['meta_module' => "bill", 'meta_key' => "fontsize_head_bill_is_bold", 'meta_value' => "0"],
            ['meta_module' => "bill", 'meta_key' => "fontsize_normal", 'meta_value' => "30"],
            ['meta_module' => "bill", 'meta_key' => "fontsize_normal_is_bold", 'meta_value' => "0"],
            ['meta_module' => "bill", 'meta_key' => "font", 'meta_value' => "cordia New"],
            ['meta_module' => "bill", 'meta_key' => "width_logo", 'meta_value' => "300"],
            ['meta_module' => "bill", 'meta_key' => "higth_logo", 'meta_value' => "100"],
            ['meta_module' => "bill", 'meta_key' => "show_price_is_row", 'meta_value' => "0"],
            ['meta_module' => "bill", 'meta_key' => "message_previous_logo", 'meta_value' => ""],
            ['meta_module' => "bill", 'meta_key' => "message_under_name", 'meta_value' => ""],
            ['meta_module' => "bill", 'meta_key' => "message_telephone", 'meta_value' => ""],
            ['meta_module' => "bill", 'meta_key' => "message_tail_bill", 'meta_value' => "ขอบคุณที่ใข้บริการ"],
            ['meta_module' => "bill", 'meta_key' => "message_under_total", 'meta_value' => ""],
            //branch
            ['meta_module' => "branch", 'meta_key' => "branch_is_use", 'meta_value' => "0"],
            ['meta_module' => "branch", 'meta_key' => "branch_name", 'meta_value' => ""],
            ['meta_module' => "branch", 'meta_key' => "branch_url", 'meta_value' => ""],
            ['meta_module' => "branch", 'meta_key' => "branch_token", 'meta_value' => ""],
            ['meta_module' => "store", 'meta_key' => "store_vat_include", 'meta_value' => "0"],
            ['meta_module' => "pospanda", 'meta_key' => "count", 'meta_value' => "0"],

        ];
        foreach ($datas as $data) {
            DB::table('metaData')->insert([$data]);
        }
    }
}
