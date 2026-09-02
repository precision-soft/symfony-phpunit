<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Dev\PhpStan\Test;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PrecisionSoft\Dev\PhpStan\Rule\VariableNameRule;

/**
 * @internal
 * @extends RuleTestCase<VariableNameRule>
 */
final class VariableNameRuleTest extends RuleTestCase
{
    public function testViolation(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixture/VariableName/Violation.php'],
            [
                ['name `$config` is an abbreviation, write the full word', 12],
                ['name `$result1` is numbered, give each variable a descriptive name', 13],
                ['name `$config` is an abbreviation, write the full word', 14],
                ['name `$result1` is numbered, give each variable a descriptive name', 14],
                ['name `$i` is an abbreviation, write the full word', 16],
                ['name `$i` is an abbreviation, write the full word', 16],
                ['name `$i` is an abbreviation, write the full word', 16],
                ['name `$result1` is numbered, give each variable a descriptive name', 17],
                ['name `$result1` is numbered, give each variable a descriptive name', 20],
            ],
        );
    }

    public function testClean(): void
    {
        $this->analyse([__DIR__ . '/Fixture/VariableName/Clean.php'], []);
    }

    protected function getRule(): Rule
    {
        return new VariableNameRule(['config', 'i'], ['sha1', 'utf8']);
    }
}
