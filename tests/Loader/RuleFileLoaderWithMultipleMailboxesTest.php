<?php

declare(strict_types=1);

namespace Tests\Loader;

use MailboxRules\Loader\RuleFileLoader;
use MailboxRules\ValueObject\MailboxConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RuleFileLoader::class)]
final class RuleFileLoaderWithMultipleMailboxesTest extends TestCase
{
    public function testLoadSingleMailbox(): void
    {
        $loader = new RuleFileLoader();
        $result = $loader->load(__DIR__ . '/../fixtures/single-mailbox.php');

        // Should contain exactly one MailboxConfiguration object
        $configs = iterator_to_array($result, false);
        self::assertCount(1, $configs);
        self::assertInstanceOf(MailboxConfiguration::class, $configs[0]);
    }

    public function testLoadMultipleMailboxes(): void
    {
        $loader = new RuleFileLoader();
        $result = $loader->load(__DIR__ . '/../fixtures/multiple-mailboxes.php');

        // Should contain multiple MailboxConfiguration objects
        $configs = iterator_to_array($result, false);
        self::assertCount(2, $configs);

        foreach ($configs as $config) {
            self::assertInstanceOf(MailboxConfiguration::class, $config);
        }

        // Check names
        self::assertSame('Work', $configs[0]->name);
        self::assertSame('Personal', $configs[1]->name);
    }
}
