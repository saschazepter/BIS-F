<?php

declare(strict_types=1);

namespace App\Services\PersonalDataSelection\Exporters\Base;

trait ModelExportable
{
    protected function onExportValidation(): void {
        // todo check for model + columns
    }
}
