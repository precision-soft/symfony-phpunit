<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Dev\PhpStan\Test;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PrecisionSoft\Dev\PhpStan\Rule\MessageCaseRule;

/**
 * @internal
 * @extends RuleTestCase<MessageCaseRule>
 */
final class MessageCaseRuleTest extends RuleTestCase
{
    public function testViolation(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixture/MessageCase/Violation.php'],
            [
                ['message must be lowercase, found `Unable`', 16],
                ['message must be lowercase, found `Fixture`', 17],
                ['message must be lowercase, found `Loading`', 18],
                ['message must be lowercase, found `Fixture`', 20],
            ],
        );
    }

    public function testClean(): void
    {
        $this->analyse([__DIR__ . '/Fixture/MessageCase/Clean.php'], []);
    }

    protected function getRule(): Rule
    {
        return new MessageCaseRule(static::createReflectionProvider());
    }
}
