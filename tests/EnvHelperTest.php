<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function MailboxRules\env;

#[CoversFunction('MailboxRules\env')]
final class EnvHelperTest extends TestCase
{
    /**
     * @var array<string, string|false>
     */
    private array $originalEnv = [];

    protected function setUp(): void
    {
        // Backup environment variables we'll modify
        $this->originalEnv = [
            'TEST_VAR' => \getenv('TEST_VAR'),
            'EMPTY_VAR' => \getenv('EMPTY_VAR'),
        ];
    }

    protected function tearDown(): void
    {
        // Restore environment variables
        foreach ($this->originalEnv as $name => $value) {
            if ($value === false) {
                \putenv($name);
            } else {
                \putenv($name . '=' . $value);
            }
        }
    }

    public function test_env_returns_value_when_variable_exists(): void
    {
        \putenv('TEST_VAR=some_value');

        $result = env('TEST_VAR');

        $this->assertSame('some_value', $result);
    }

    public function test_env_throws_exception_when_variable_not_defined(): void
    {
        \putenv('TEST_VAR');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('"TEST_VAR" environment variable must be defined');

        env('TEST_VAR');
    }

    public function test_env_throws_exception_when_variable_is_empty(): void
    {
        \putenv('EMPTY_VAR=');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('"EMPTY_VAR" environment variable must be defined');

        env('EMPTY_VAR');
    }

    public function test_env_works_with_numeric_values(): void
    {
        \putenv('TEST_VAR=12345');

        $result = env('TEST_VAR');

        $this->assertSame('12345', $result);
    }

    public function test_env_preserves_whitespace(): void
    {
        \putenv('TEST_VAR= value with spaces ');

        $result = env('TEST_VAR');

        $this->assertSame(' value with spaces ', $result);
    }
}
