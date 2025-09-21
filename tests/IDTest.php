<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ndtan\ID;

final class IDTest extends TestCase
{
    public function testUuid4(): void
    {
        $u = ID::uuid4();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $u);
    }

    public function testUuid7(): void
    {
        $u = ID::uuid7();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $u);
    }
}
