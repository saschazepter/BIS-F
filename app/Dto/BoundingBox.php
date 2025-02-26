<?php

namespace App\Dto;

use Illuminate\Contracts\Support\Arrayable;

class BoundingBox implements Arrayable
{
    public Coordinate $topLeft;
    public Coordinate $bottomRight;

    public function __construct(Coordinate $topLeft, Coordinate $bottomRight)
    {
        $this->topLeft = $topLeft;
        $this->bottomRight = $bottomRight;
    }

    public function toArray(): array
    {
        return [
            'topLeft' => $this->topLeft->toArray(),
            'bottomRight' => $this->bottomRight->toArray()
        ];
    }
}
