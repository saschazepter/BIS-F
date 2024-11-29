<?php

declare(strict_types=1);

namespace App\Services\PersonalDataSelection\Exporters\Base;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

abstract class AbstractExporter
{
    protected User   $user;
    protected string $fileName;

    public function __construct(User $user) {
        $this->user = $user;

        if (!isset($this->fileName)) {
            throw new \InvalidArgumentException('Property $fileName must be set in ' . static::class);
        }
    }

    public function getFileName(): string {
        return $this->fileName;
    }

    abstract protected function exportData(): array|string|Collection;
}
