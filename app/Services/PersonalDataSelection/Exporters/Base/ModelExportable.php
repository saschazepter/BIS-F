<?php

declare(strict_types=1);

namespace App\Services\PersonalDataSelection\Exporters\Base;

trait ModelExportable
{
    protected function onExportValidation(): bool {
        return true;
        // todo check for model + columns
    }
}
