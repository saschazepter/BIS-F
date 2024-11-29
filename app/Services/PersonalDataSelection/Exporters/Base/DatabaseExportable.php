<?php

declare(strict_types=1);

namespace App\Services\PersonalDataSelection\Exporters\Base;

trait DatabaseExportable
{
    protected array  $columns   = [];
    protected string $tableName = '';

    public function getData(): array {

    }
}
