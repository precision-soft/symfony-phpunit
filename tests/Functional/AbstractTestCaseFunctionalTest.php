<?php

declare(strict_types=1);

/*
 * Copyright (c) Precision Soft
 */

namespace PrecisionSoft\Symfony\Phpunit\Test\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/** @internal */
#[Group('integration')]
final class AbstractTestCaseFunctionalTest extends TestCase
{
    private string $workingDirectory = '';

    protected function setUp(): void
    {
        parent::setUp();

        if (false === \is_file(static::getPhpunitBinaryPath())) {
            static::markTestSkipped('the phpunit binary is not installed');
        }

        $workingDirectory = \sys_get_temp_dir() . '/symfony-phpunit-functional-' . \uniqid();

        \mkdir($workingDirectory);

        $this->workingDirectory = $workingDirectory;
    }

    protected function tearDown(): void
    {
        if ('' !== $this->workingDirectory) {
            static::removeDirectory($this->workingDirectory);

            $this->workingDirectory = '';
        }

        parent::tearDown();
    }

    public function testSetUpWiresTheMockContainerInARealPhpunitRun(): void
    {
        $this->writeGeneratedTest(
            <<<'PHP'
                public function testTheSluggerMockComesFromTheContainer(): void
                {
                    $sluggerInterface = $this->get(SluggerInterface::class);

                    static::assertSame('hello world', $sluggerInterface->slug('hello world')->toString());
                }
                PHP,
        );

        $processResult = $this->runGeneratedTest();

        static::assertSame(0, $processResult['exitCode'], $processResult['output']);
        static::assertStringContainsString('OK (1 test', $processResult['output']);
    }

    public function testAnUnfulfilledExpectationFailsTheRun(): void
    {
        $this->writeGeneratedTest(
            <<<'PHP'
                public function testAnExpectationThatIsNeverMet(): void
                {
                    $this->get(SluggerInterface::class)
                        ->shouldReceive('slug')
                        ->once();

                    static::assertTrue(true);
                }
                PHP,
        );

        $processResult = $this->runGeneratedTest();

        static::assertNotSame(0, $processResult['exitCode'], $processResult['output']);
        static::assertStringContainsString('slug', $processResult['output']);
    }

    private function writeGeneratedTest(string $testMethod): void
    {
        $testSource = <<<PHP
            <?php

            declare(strict_types=1);

            namespace PrecisionSoft\\Symfony\\Phpunit\\Test\\Functional\\Generated;

            use PrecisionSoft\\Symfony\\Phpunit\\Mock\\SluggerInterfaceMock;
            use PrecisionSoft\\Symfony\\Phpunit\\MockDto;
            use PrecisionSoft\\Symfony\\Phpunit\\TestCase\\AbstractTestCase;
            use Symfony\\Component\\String\\Slugger\\SluggerInterface;

            final class GeneratedTest extends AbstractTestCase
            {
                public static function getMockDto(): MockDto
                {
                    return SluggerInterfaceMock::getMockDto();
                }

            {$testMethod}
            }
            PHP;

        \file_put_contents($this->workingDirectory . '/GeneratedTest.php', $testSource);

        $autoloadPath = static::getPackageRootPath() . '/vendor/autoload.php';

        $configuration = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                     bootstrap="{$autoloadPath}"
                     colors="false"
                     failOnWarning="true">
                <testsuites>
                    <testsuite name="Generated">
                        <directory>{$this->workingDirectory}</directory>
                    </testsuite>
                </testsuites>
            </phpunit>
            XML;

        \file_put_contents($this->workingDirectory . '/phpunit.xml', $configuration);
    }

    /** @return array{exitCode: int, output: string} */
    private function runGeneratedTest(): array
    {
        $command = \sprintf(
            '%s %s --configuration %s 2>&1',
            \escapeshellarg(\PHP_BINARY),
            \escapeshellarg(static::getPhpunitBinaryPath()),
            \escapeshellarg($this->workingDirectory . '/phpunit.xml'),
        );

        $outputLines = [];
        $exitCode = 0;

        \exec($command, $outputLines, $exitCode);

        return ['exitCode' => $exitCode, 'output' => \implode("\n", $outputLines)];
    }

    private static function getPackageRootPath(): string
    {
        return \dirname(__DIR__, 2);
    }

    private static function getPhpunitBinaryPath(): string
    {
        return static::getPackageRootPath() . '/vendor/phpunit/phpunit/phpunit';
    }

    private static function removeDirectory(string $directoryPath): void
    {
        if (false === \is_dir($directoryPath)) {
            return;
        }

        foreach (\scandir($directoryPath) ?: [] as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $entryPath = $directoryPath . '/' . $entry;

            if (true === \is_dir($entryPath)) {
                static::removeDirectory($entryPath);

                continue;
            }

            \unlink($entryPath);
        }

        \rmdir($directoryPath);
    }
}
