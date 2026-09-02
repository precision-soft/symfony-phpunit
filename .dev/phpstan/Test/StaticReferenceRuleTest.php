<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Dev\PhpStan\Test;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PrecisionSoft\Dev\PhpStan\Rule\StaticReferenceRule;

/**
 * @internal
 * @extends RuleTestCase<StaticReferenceRule>
 */
final class StaticReferenceRuleTest extends RuleTestCase
{
    public function testViolation(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixture/StaticReference/Violation.php'],
            [
                ['`self::` must be `static::` where late static binding is legal', 14],
                ['`self::` must be `static::` where late static binding is legal', 19],
                ['`self::` must be `static::` where late static binding is legal', 21],
                ['`self::` must be `static::` where late static binding is legal', 21],
            ],
        );
    }

    public function testClean(): void
    {
        $this->analyse([__DIR__ . '/Fixture/StaticReference/Clean.php'], []);
        $this->analyse([__DIR__ . '/Fixture/StaticReference/Marker.php'], []);
        $this->analyse([__DIR__ . '/Fixture/StaticReference/Suit.php'], []);
        $this->analyse([__DIR__ . '/Fixture/StaticReference/Sealed.php'], []);
    }

    protected function getRule(): Rule
    {
        return new StaticReferenceRule();
    }
}
