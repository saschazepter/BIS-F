<?php

declare(strict_types=1);

namespace App\Services\PersonalDataSelection\Exporters;

use App\Services\PersonalDataSelection\Exporters\Base\AbstractExporter;
use App\Services\PersonalDataSelection\Exporters\Base\RelationExportable;

class AppsExporter extends AbstractExporter
{
    use RelationExportable;

    protected string $fileName = 'apps.json';
    protected string $relation = 'oAuthClients';
    // todo: columns
}
