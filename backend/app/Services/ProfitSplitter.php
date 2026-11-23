<?php

namespace App\Services;

class ProfitSplitter
{
    public const HALF = 'half';   // 50% company, 50% shared by partners
    public const EQUAL = 'equal'; // partners and company get equal parts

    /**
     * @param  array<int,string>  $partners  [partnerId => name]
     * @return array<int,array{party:string,partner_id:?int,label:string,percent:float,amount:float}>
     */
    public static function split(float $profit, string $mode, array $partners): array
    {
        $count = max(count($partners), 1);
        $each = $mode === self::EQUAL ? 100 / ($count + 1) : 50 / $count;
        $base = max($profit, 0);

        $rows = [];
        $given = 0.0;
        foreach ($partners as $id => $name) {
            $amount = round($base * $each / 100, 2);
            $given += $amount;
            $rows[] = ['party' => 'partner', 'partner_id' => $id, 'label' => $name, 'percent' => round($each, 2), 'amount' => $amount];
        }
        // company takes the rest, so rounding and any loss land here
        $rows[] = [
            'party' => 'company', 'partner_id' => null, 'label' => 'Company',
            'percent' => round(100 - $each * count($partners), 2), 'amount' => round($profit - $given, 2),
        ];

        return $rows;
    }
}
