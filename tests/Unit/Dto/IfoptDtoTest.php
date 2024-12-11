<?php

namespace Tests\Unit\Dto;

use App\Dto\IfoptDto;
use Tests\Unit\UnitTestCase;

class IfoptDtoTest extends UnitTestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testFromString($string, $a, $b, $c, $d, $e): void {
        $dto = IfoptDto::fromString($string);
        $this->assertEquals($a, $dto->a);
        $this->assertEquals($b, $dto->b);
        $this->assertEquals($c, $dto->c);
        $this->assertEquals($d, $dto->d);
        $this->assertEquals($e, $dto->e);
    }

    public static function dataProvider(): array {
        return [
            ['a:b:c:d:e', 'a', 'b', 'c', 'd', 'e'],
            ['a:b:c:d', 'a', 'b', 'c', 'd', null],
            ['a:b:c', 'a', 'b', 'c', null, null],
            ['a:b', 'a', 'b', null, null, null],
            ['a', 'a', null, null, null, null],
            [null, null, null, null, null, null],
            ['de:123:456:789:0', 'de', '123', '456', '789', '0'],
            ['fr:123:456:789:0', 'fr', '123', '456', '789', '0'],
            ['de:123:456:789', 'de', '123', '456', '789', null],
            ['de:123:456', 'de', '123', '456', null, null],
            ['de:123', 'de', '123', null, null, null],
            ['de', 'de', null, null, null, null],
            [':123:456:789:0', null, '123', '456', '789', '0'],
            [':123:456:789', null, '123', '456', '789', null],
            [':123:456', null, '123', '456', null, null],
            [':123', null, '123', null, null, null],
            [':', null, null, null, null, null],
        ];
    }
}
