<?php

declare(strict_types=1);

namespace App\Services\PersonalDataSelection\Exporters;

use App\Services\PersonalDataSelection\Exporters\Base\AbstractExporter;
use App\Services\PersonalDataSelection\Exporters\Base\RelationExportable;

class TokenExporter extends AbstractExporter
{
    use RelationExportable;

    protected string $fileName = 'tokens.json';
    protected string $relation = 'tokens';
    // todo: columns
}
