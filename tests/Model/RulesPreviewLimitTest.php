<?php

declare(strict_types=1);

namespace Tests\Model;

use MailboxRules\Action\LogAction;
use MailboxRules\Adapter\InMemoryMailbox;
use MailboxRules\Matcher\AnyMatcher;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rules::class)]
final class RulesPreviewLimitTest extends TestCase
{
    private function rules(int $messages): Rules
    {
        $mailbox = new InMemoryMailbox();
        for ($i = 0; $i < $messages; ++$i) {
            $mailbox->deliver('INBOX');
        }

        return new Rules($mailbox, [
            new Rule('Test', new AnyMatcher(), static fn (): iterable => yield new LogAction()),
        ]);
    }

    #[Test]
    public function preview_uses_default_limit_of_10(): void
    {
        self::assertCount(10, $this->rules(12)->preview());
    }

    #[Test]
    public function preview_accepts_custom_limit(): void
    {
        self::assertCount(50, $this->rules(60)->preview(50));
    }

    #[Test]
    public function preview_with_zero_limit_fetches_all_messages(): void
    {
        self::assertCount(12, $this->rules(12)->preview(0));
    }
}
