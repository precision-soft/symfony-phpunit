<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Dev\PhpStan\Test;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PrecisionSoft\Dev\PhpStan\Rule\ImportedClassNameRule;

/**
 * @internal
 * @extends RuleTestCase<ImportedClassNameRule>
 */
final class ImportedClassNameRuleTest extends RuleTestCase
{
    public function testViolation(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixture/ImportedClassName/Violation.php'],
            [
                ['class `ReturnTypeWillChange` must be imported with `use` instead of being referenced inline', 11],
                ['class `ArrayObject` must be imported with `use` instead of being referenced inline', 12],
                ['class `ArrayIterator` must be imported with `use` instead of being referenced inline', 14],
                ['class `Countable` must be imported with `use` instead of being referenced inline', 16],
                ['class `Closure` must be imported with `use` instead of being referenced inline', 21],
                ['class `Throwable` must be imported with `use` instead of being referenced inline', 22],
                ['class `RuntimeException` must be imported with `use` instead of being referenced inline', 23],
            ],
        );
    }

    public function testClean(): void
    {
        $this->analyse([__DIR__ . '/Fixture/ImportedClassName/Clean.php'], []);
    }

    protected function getRule(): Rule
    {
        return new ImportedClassNameRule();
    }
}
