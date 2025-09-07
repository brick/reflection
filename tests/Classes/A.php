<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Classes;

class A
{
    private $a;
    protected $b;
    public $c;

    private function a() {}
    protected function b() {}
    public function c() {}
}
