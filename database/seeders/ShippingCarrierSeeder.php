<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShippingCarrierSeeder extends Seeder
{
    public function run(): void
    {
        $carriers = [
            [
                'name'        => 'Giao Hàng Nhanh',
                'code'        => 'ghn',
                'base_fee'    => 25000,
                'per_km_fee'  => 0,
                'is_active'   => true,
            ],
            [
                'name'        => 'Giao Hàng Tiết Kiệm',
                'code'        => 'ghtk',
                'base_fee'    => 18000,
                'per_km_fee'  => 0,
                'is_active'   => true,
            ],
            [
                'name'        => 'ViettelPost',
                'code'        => 'viettelpost',
                'base_fee'    => 20000,
                'per_km_fee'  => 0,
                'is_active'   => true,
            ],
        ];

        foreach ($carriers as $carrier) {
            $carrierId = DB::table('shipping_carriers')->insertGetId(array_merge($carrier, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            $this->seedZones($carrierId, $carrier['code']);
        }

        $this->command->info('✅ Đã seed ' . count($carriers) . ' nhà vận chuyển với zones.');
    }

    private function seedZones(int $carrierId, string $code): void
    {
        // Phí ship theo miền, điều chỉnh theo từng carrier
        $feeMap = [
            'ghn'         => ['north' => 25000, 'central' => 30000, 'south' => 35000],
            'ghtk'        => ['north' => 18000, 'central' => 22000, 'south' => 25000],
            'viettelpost' => ['north' => 20000, 'central' => 25000, 'south' => 28000],
        ];

        $fees = $feeMap[$code] ?? ['north' => 25000, 'central' => 30000, 'south' => 35000];

        $zones = [
            // Miền Bắc
            ['province' => 'Hà Nội',       'region' => 'north',   'fee' => $fees['north'],   'estimated_days' => 1],
            ['province' => 'Hải Phòng',     'region' => 'north',   'fee' => $fees['north'],   'estimated_days' => 2],
            ['province' => 'Bắc Ninh',      'region' => 'north',   'fee' => $fees['north'],   'estimated_days' => 2],
            ['province' => 'Hưng Yên',      'region' => 'north',   'fee' => $fees['north'],   'estimated_days' => 2],
            ['province' => 'Hải Dương',     'region' => 'north',   'fee' => $fees['north'],   'estimated_days' => 2],
            ['province' => 'Quảng Ninh',    'region' => 'north',   'fee' => $fees['north'],   'estimated_days' => 2],
            ['province' => 'Thái Nguyên',   'region' => 'north',   'fee' => $fees['north'],   'estimated_days' => 2],
            ['province' => 'Vĩnh Phúc',     'region' => 'north',   'fee' => $fees['north'],   'estimated_days' => 2],
            ['province' => 'Hà Nam',        'region' => 'north',   'fee' => $fees['north'],   'estimated_days' => 2],
            ['province' => 'Nam Định',      'region' => 'north',   'fee' => $fees['north'],   'estimated_days' => 2],
            ['province' => 'Ninh Bình',     'region' => 'north',   'fee' => $fees['north'],   'estimated_days' => 2],
            ['province' => 'Thanh Hóa',     'region' => 'north',   'fee' => $fees['north'],   'estimated_days' => 3],

            // Miền Trung
            ['province' => 'Đà Nẵng',       'region' => 'central', 'fee' => $fees['central'], 'estimated_days' => 2],
            ['province' => 'Huế',           'region' => 'central', 'fee' => $fees['central'], 'estimated_days' => 3],
            ['province' => 'Quảng Nam',     'region' => 'central', 'fee' => $fees['central'], 'estimated_days' => 3],
            ['province' => 'Quảng Ngãi',    'region' => 'central', 'fee' => $fees['central'], 'estimated_days' => 3],
            ['province' => 'Bình Định',     'region' => 'central', 'fee' => $fees['central'], 'estimated_days' => 3],
            ['province' => 'Phú Yên',       'region' => 'central', 'fee' => $fees['central'], 'estimated_days' => 3],
            ['province' => 'Khánh Hòa',     'region' => 'central', 'fee' => $fees['central'], 'estimated_days' => 3],
            ['province' => 'Nghệ An',       'region' => 'central', 'fee' => $fees['central'], 'estimated_days' => 3],
            ['province' => 'Hà Tĩnh',       'region' => 'central', 'fee' => $fees['central'], 'estimated_days' => 3],

            // Miền Nam
            ['province' => 'TP. Hồ Chí Minh', 'region' => 'south', 'fee' => $fees['south'],  'estimated_days' => 2],
            ['province' => 'Bình Dương',    'region' => 'south',   'fee' => $fees['south'],   'estimated_days' => 2],
            ['province' => 'Đồng Nai',      'region' => 'south',   'fee' => $fees['south'],   'estimated_days' => 2],
            ['province' => 'Long An',       'region' => 'south',   'fee' => $fees['south'],   'estimated_days' => 3],
            ['province' => 'Tiền Giang',    'region' => 'south',   'fee' => $fees['south'],   'estimated_days' => 3],
            ['province' => 'Cần Thơ',       'region' => 'south',   'fee' => $fees['south'],   'estimated_days' => 3],
            ['province' => 'Vũng Tàu',      'region' => 'south',   'fee' => $fees['south'],   'estimated_days' => 3],
            ['province' => 'Bình Phước',    'region' => 'south',   'fee' => $fees['south'],   'estimated_days' => 3],
            ['province' => 'Tây Ninh',      'region' => 'south',   'fee' => $fees['south'],   'estimated_days' => 3],
            ['province' => 'An Giang',      'region' => 'south',   'fee' => $fees['south'],   'estimated_days' => 4],
            ['province' => 'Kiên Giang',    'region' => 'south',   'fee' => $fees['south'],   'estimated_days' => 4],
        ];

        $rows = array_map(fn($z) => array_merge($z, [
            'carrier_id' => $carrierId,
            'created_at' => now(),
            'updated_at' => now(),
        ]), $zones);

        DB::table('shipping_zones')->insert($rows);
    }
}