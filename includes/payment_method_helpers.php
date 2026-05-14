<?php
declare(strict_types=1);

/**
 * @return list<array{value:string,label:string,hint:string,short:string}>
 */
function payment_method_types_meta(): array
{
    return [
        ['value' => 'easypaisa', 'label' => 'EasyPaisa', 'hint' => 'Mobile wallet', 'short' => 'EP'],
        ['value' => 'jazzcash', 'label' => 'JazzCash', 'hint' => 'Mobile wallet', 'short' => 'JC'],
        ['value' => 'bank', 'label' => 'Bank transfer', 'hint' => 'Account / IBAN', 'short' => 'BK'],
        ['value' => 'other', 'label' => 'Other', 'hint' => 'Custom method', 'short' => 'OT'],
    ];
}

function payment_method_type_key(string $type): string
{
    return in_array($type, ['easypaisa', 'jazzcash', 'bank', 'other'], true) ? $type : 'other';
}

function payment_method_type_label(string $type): string
{
    foreach (payment_method_types_meta() as $row) {
        if ($row['value'] === payment_method_type_key($type)) {
            return $row['label'];
        }
    }

    return 'Other';
}

function payment_method_type_short(string $type): string
{
    foreach (payment_method_types_meta() as $row) {
        if ($row['value'] === payment_method_type_key($type)) {
            return $row['short'];
        }
    }

    return 'OT';
}
