<?php

declare(strict_types=1);

namespace App\Services\PersonalDataSelection\Exporters;

use App\Services\PersonalDataSelection\Exporters\Base\AbstractExporter;
use App\Services\PersonalDataSelection\Exporters\Base\ModelExportable;

class StatusExporter extends AbstractExporter
{
    use ModelExportable;

    protected string $fileName = 'statuses.json';

    protected function exportData(): array|string {
        return $this->user->statuses()->with('tags')->get()->toArray(); // todo: columns definieren
    }
}
