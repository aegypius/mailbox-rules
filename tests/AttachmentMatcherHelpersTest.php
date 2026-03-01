<?php

namespace Tests;

use MailboxRules\Matcher\AttachmentTypeMatcher;
use MailboxRules\Matcher\HasAttachmentMatcher;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function MailboxRules\attachmentType;
use function MailboxRules\hasAttachment;

#[CoversFunction('MailboxRules\hasAttachment')]
#[CoversFunction('MailboxRules\attachmentType')]
final class AttachmentMatcherHelpersTest extends TestCase
{
    public function testHasAttachmentReturnsCorrectMatcher(): void
    {
        $matcher = hasAttachment();

        self::assertInstanceOf(HasAttachmentMatcher::class, $matcher);
    }

    public function testAttachmentTypeReturnsCorrectMatcher(): void
    {
        $matcher = attachmentType('image/jpeg');

        self::assertInstanceOf(AttachmentTypeMatcher::class, $matcher);
    }
}
