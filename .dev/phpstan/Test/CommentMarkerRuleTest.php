<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Dev\PhpStan\Test;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PrecisionSoft\Dev\PhpStan\Rule\CommentMarkerRule;

/**
 * @internal
 * @extends RuleTestCase<CommentMarkerRule>
 */
final class CommentMarkerRuleTest extends RuleTestCase
{
    public function testViolation(): void
    {
        $this->analyse(
            [__DIR__ . '/Fixture/CommentMarker/Violation.php'],
            [
                ['comment carries a `TODO` marker, track the work in the issue tracker instead', 8],
                ['comment carries a `FIXME` marker, track the work in the issue tracker instead', 12],
                ['comment carries a `XXX` marker, track the work in the issue tracker instead', 15],
            ],
        );
    }

    public function testClean(): void
    {
        $this->analyse([__DIR__ . '/Fixture/CommentMarker/Clean.php'], []);
    }

    protected function getRule(): Rule
    {
        return new CommentMarkerRule();
    }
}
