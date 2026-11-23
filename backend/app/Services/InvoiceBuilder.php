<?php

namespace App\Services;

use App\Support\Money;

/**
 * Turns plain item/payment arrays into the frozen snapshot stored on an invoice.
 * Item keys: name, description, short_label, unit_price, quantity, billing, unit, amount, is_free, rate_text
 * Payment keys: amount, label, method, paid_at (Y-m-d)
 */
class InvoiceBuilder
{
    public static function build(array $items, array $payments, array $opts = []): array
    {
        $lines = [];
        $subtotal = 0.0;
        $pending = [];

        $forward = (float) ($opts['forward'] ?? 0);
        if ($forward > 0) {
            $lines[] = self::line(count($lines) + 1, 'Previous balance', 'Brought forward from earlier billing', Money::fmt($forward), $forward, false);
            $subtotal += $forward;
        }

        foreach ($items as $it) {
            $free = ! empty($it['is_free']);
            $amount = $free ? 0.0 : (isset($it['amount']) ? (float) $it['amount'] : null);
            $isPending = ! $free && $amount === null;

            $rate = trim((string) ($it['rate_text'] ?? ''));
            if ($rate === '') {
                $price = (float) ($it['unit_price'] ?? 0);
                $rate = $price > 0 ? Money::fmt($price) : '—';
                if (($it['billing'] ?? 'fixed') === 'per_unit' && $price > 0) {
                    $rate .= ' / '.($it['unit'] ?: 'unit');
                }
            }

            $text = $free ? 'Free' : ($isPending ? 'On delivery' : Money::fmt($amount));
            $lines[] = self::line(count($lines) + 1, $it['name'], (string) ($it['description'] ?? ''), $text, $amount, $isPending, $rate);

            if ($isPending) {
                $pending[] = $it;
            } else {
                $subtotal += (float) $amount;
            }
        }

        $payRows = [];
        $paid = 0.0;
        foreach (array_values($payments) as $i => $p) {
            $label = trim((string) ($p['label'] ?? ''));
            if ($label === '') {
                $label = match ($i) {
                    0 => 'Advance paid',
                    1 => '2nd payment received',
                    default => 'Payment received ('.date('j M', strtotime($p['paid_at'])).')',
                };
            }
            $payRows[] = ['label' => $label, 'amount' => (float) $p['amount']];
            $paid += (float) $p['amount'];
        }

        $labels = array_map(fn ($it) => trim($it['short_label'] ?? '') ?: $it['name'], $pending);

        return [
            'lines' => $lines,
            'payments' => $payRows,
            'subtotal' => round($subtotal, 2),
            'paid' => round($paid, 2),
            'balance' => round($subtotal - $paid, 2),
            'has_pending' => $pending !== [],
            'pending_labels' => $labels,
            'pending_sub' => $labels ? '+ '.mb_strtolower(implode(' / ', $labels)) : '',
            'note' => $opts['note'] ?? self::defaultNote($pending),
            'payment_record' => $opts['payment_record'] ?? self::defaultPaymentRecord($payments),
        ];
    }

    /** Flattens a stored snapshot plus invoice/client meta into the variables the PDF view expects. */
    public static function pdfData(array $snap, array $meta): array
    {
        $snap['subtotal'] = Money::fmt($snap['subtotal']);
        $snap['balance'] = Money::fmt($snap['balance']);
        $snap['payments'] = array_map(fn ($p) => $p + ['amount_text' => Money::fmt($p['amount'])], $snap['payments']);
        $snap['note'] = (string) ($snap['note'] ?? '');
        $snap['payment_record'] = (string) ($snap['payment_record'] ?? '');

        return $snap + $meta;
    }

    public static function defaultNote(array $pending): string
    {
        $out = [];
        foreach ($pending as $it) {
            $price = (float) ($it['unit_price'] ?? 0);
            $out[] = ($it['billing'] ?? '') === 'per_unit' && $price > 0
                ? $it['name'].' at Rs '.Money::fmt($price).'/'.($it['unit'] ?: 'unit').', added to the balance on delivery.'
                : $it['name'].' will be added to the balance on delivery.';
        }

        return implode(' ', $out);
    }

    public static function defaultPaymentRecord(array $payments): string
    {
        $bank = [];
        $other = [];
        foreach ($payments as $p) {
            $m = strtolower((string) ($p['method'] ?? 'bank'));
            $amt = Money::rs($p['amount']);
            $date = date('j M Y', strtotime($p['paid_at']));
            if ($m === 'bank' || $m === '') {
                $bank[] = $amt;
            } elseif ($m === 'cash') {
                $other[] = "$amt paid in cash on $date.";
            } else {
                $other[] = "$amt paid via ".ucfirst($m)." on $date.";
            }
        }
        $parts = [];
        if ($bank) {
            $last = array_pop($bank);
            $list = $bank ? implode(', ', $bank).' and '.$last : $last;
            $parts[] = "$list paid via online bank transfer; bank receipts / screenshots are the source of truth.";
        }

        return implode(' ', array_merge($parts, $other));
    }

    private static function line(int $n, string $name, string $desc, string $text, ?float $amount, bool $pending, ?string $rate = null): array
    {
        return [
            'no' => str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            'name' => $name, 'description' => $desc,
            'rate' => $rate ?? '', 'amount' => $amount, 'amount_text' => $text, 'pending' => $pending,
        ];
    }
}
