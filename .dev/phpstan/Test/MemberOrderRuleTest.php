<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Dev\PhpStan\Test;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PrecisionSoft\Dev\PhpStan\Rule\MemberOrderRule;

/**
 * @internal
 * @extends RuleTestCase<MemberOrderRule>
 */
final class MemberOrderRuleTest extends RuleTestCase
{
    public function testViolation(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixture/MemberOrder/Violation.php'],
            [
                ['class member `describe` is out of order: abstract method must come before constructor', 11],
                ['class member `create` is out of order: public static method must come before public method', 18],
                ['class member `LABEL` is out of order: protected constant must come before public method', 23],
                ['class member `getName` is out of order: public method must come before protected method', 30],
            ],
        );
    }

    public function testClean(): void
    {
        $this->analyse([__DIR__ . '/Fixture/MemberOrder/Clean.php'], []);
    }

    protected function getRule(): Rule
    {
        return new MemberOrderRule();
    }
}
