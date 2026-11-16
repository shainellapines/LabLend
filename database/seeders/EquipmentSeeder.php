<?php

namespace Database\Seeders;

use App\Enums\EquipmentCondition;
use App\Enums\EquipmentStatus;
use App\Models\Category;
use App\Models\Equipment;
use Illuminate\Database\Seeder;

/**
 * 26 items. Asset tags are <category code>-<4-digit number>, e.g. PRJ-0001.
 */
class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        // [category code, name, brand, model, location, condition]
        $items = [
            ['PRJ', 'Epson Projector', 'Epson', 'EB-X51', 'Custodian office', 'good'],
            ['PRJ', 'Epson Projector', 'Epson', 'EB-X51', 'Custodian office', 'good'],
            ['PRJ', 'Epson Projector', 'Epson', 'EB-E01', 'Custodian office', 'fair'],
            ['PRJ', 'Acer Projector', 'Acer', 'X1228i', 'Lab 2 cabinet', 'good'],
            ['PRJ', 'BenQ Projector', 'BenQ', 'MS560', 'AVR storage', 'good'],
            ['LAP', 'Lenovo ThinkPad', 'Lenovo', 'E14 Gen 5', 'Custodian office', 'good'],
            ['LAP', 'Lenovo ThinkPad', 'Lenovo', 'E14 Gen 5', 'Custodian office', 'good'],
            ['LAP', 'Acer Aspire 5', 'Acer', 'A515-58', 'Custodian office', 'fair'],
            ['LAP', 'Acer Aspire 5', 'Acer', 'A515-58', 'Custodian office', 'good'],
            ['CBL', 'HDMI Cable 5m', 'Ugreen', 'HD104', 'Lab 1 cabinet', 'good'],
            ['CBL', 'HDMI Cable 5m', 'Ugreen', 'HD104', 'Lab 1 cabinet', 'good'],
            ['CBL', 'HDMI Cable 10m', 'Ugreen', 'HD104', 'Lab 2 cabinet', 'fair'],
            ['CBL', 'USB-C to HDMI Adapter', 'Ugreen', 'CM297', 'Custodian office', 'good'],
            ['CBL', 'USB-C to HDMI Adapter', 'Baseus', 'BS-OH024', 'Custodian office', 'good'],
            ['CBL', 'VGA Cable 3m', 'Generic', 'VGA-3M', 'Lab 1 cabinet', 'poor'],
            ['AUD', 'Portable Speaker', 'JBL', 'Flip 6', 'AVR storage', 'good'],
            ['AUD', 'Portable Speaker', 'JBL', 'Charge 5', 'AVR storage', 'good'],
            ['AUD', 'Wireless Microphone Set', 'Shure', 'BLX288', 'AVR storage', 'good'],
            ['AUD', 'Wired Microphone', 'Shure', 'SM58', 'AVR storage', 'fair'],
            ['EXT', 'Extension Cord 5m', 'Omni', 'WER-345', 'Lab 1 cabinet', 'good'],
            ['EXT', 'Extension Cord 5m', 'Omni', 'WER-345', 'Lab 2 cabinet', 'good'],
            ['EXT', 'Power Strip 6-outlet', 'Royu', 'RED116', 'Custodian office', 'good'],
            ['NET', 'Wi-Fi Router', 'TP-Link', 'Archer C6', 'Server room', 'good'],
            ['NET', 'Network Switch 8-port', 'TP-Link', 'TL-SG108', 'Server room', 'good'],
            ['NET', 'LAN Cable Tester', 'Generic', 'RJ45-T', 'Lab 2 cabinet', 'good'],
            ['NET', 'Crimping Tool Set', 'Generic', 'CT-568', 'Lab 2 cabinet', 'fair'],
        ];

        $categories = Category::pluck('id', 'code'); // ['PRJ' => 1, 'LAP' => 2, ...]
        $counters = [];                               // next number per category code

        foreach ($items as [$code, $name, $brand, $model, $location, $condition]) {
            $counters[$code] = ($counters[$code] ?? 0) + 1;

            Equipment::factory()->create([
                'category_id' => $categories[$code],
                'asset_tag' => sprintf('%s-%04d', $code, $counters[$code]),
                'name' => $name,
                'brand' => $brand,
                'model' => $model,
                'location' => $location,
                'condition' => EquipmentCondition::from($condition),
            ]);
        }

        // One old item is retired so the demo shows that status too.
        Equipment::where('asset_tag', 'CBL-0006')->update(['status' => EquipmentStatus::Retired]);
    }
}
