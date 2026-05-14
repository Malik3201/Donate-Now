<?php
declare(strict_types=1);

function is_restricted_status(string $status): bool
{
    return in_array($status, ['blocked', 'suspended', 'temporary_hold'], true);
}
