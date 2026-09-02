<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Dev\PhpStan\Test;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PrecisionSoft\Dev\PhpStan\Rule\ParameterNameRule;

/**
 * @internal
 * @extends RuleTestCase<ParameterNameRule>
 */
final class ParameterNameRuleTest extends RuleTestCase
{
    public function testViolation(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixture/ParameterName/Violation.php'],
            [
                ['name `$config` is an abbreviation, write the full word', 12],
                ['name `$value1` is numbered, give each variable a descriptive name', 12],
                ['name `$e` is an abbreviation, write the full word', 16],
            ],
        );
    }

    public function testClean(): void
    {
        $this->analyse([__DIR__ . '/Fixture/ParameterName/Clean.php'], []);
    }

    protected function getRule(): Rule
    {
        return new ParameterNameRule(['config', 'e'], ['sha1']);
    }
}
