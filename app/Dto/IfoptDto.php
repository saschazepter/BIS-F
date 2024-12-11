<?php

declare(strict_types=1);

namespace App\Dto;

class IfoptDto
{
    public ?string $a;
    public ?string $b;
    public ?string $c;
    public ?string $d;
    public ?string $e;

    public function __construct(?string $a, ?string $b, ?string $c, ?string $d, ?string $e) {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
        $this->d = $d;
        $this->e = $e;
    }

    public function __toString(): string {
        return implode(':', array_filter([$this->a, $this->b, $this->c, $this->d, $this->e]));
    }

    /**
     * @todo remove this. This is ONLY for testing purposes, as long as stellwerk doesn't have own station ids
     */
    public function toStationId(): string {
        return implode(':', array_filter([$this->a, $this->b, $this->c]));
    }

    public static function fromString(?string $ifopt): IfoptDto {
        if ($ifopt === null) {
            return new IfoptDto(null, null, null, null, null);
        }
        $explodedIfopt = explode(':', $ifopt);

        return new IfoptDto(
            $explodedIfopt[0] ?? null,
            $explodedIfopt[1] ?? null,
            $explodedIfopt[2] ?? null,
            $explodedIfopt[3] ?? null,
            $explodedIfopt[4] ?? null
        );
    }
}
