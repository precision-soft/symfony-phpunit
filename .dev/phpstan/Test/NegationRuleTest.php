<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Dev\PhpStan\Test;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PrecisionSoft\Dev\PhpStan\Rule\NegationRule;

/**
 * @internal
 * @extends RuleTestCase<NegationRule>
 */
final class NegationRuleTest extends RuleTestCase
{
    public function testViolation(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixture/Negation/Violation.php'],
            [
                ['negation operator is forbidden, write an explicit comparison instead', 11],
                ['negation operator is forbidden, write an explicit comparison instead', 15],
            ],
        );
    }

    public function testClean(): void
    {
        $this->analyse([__DIR__ . '/Fixture/Negation/Clean.php'], []);
    }

    protected function getRule(): Rule
    {
        return new NegationRule();
    }
}
