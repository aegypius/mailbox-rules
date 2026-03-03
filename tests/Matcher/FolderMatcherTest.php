<?php

declare(strict_types=1);

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Folder;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\FolderMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FolderMatcher::class)]
final class FolderMatcherTest extends TestCase
{
    #[Test]
    public function it_implements_matcher_interface(): void
    {
        $folderMatcher = new FolderMatcher('INBOX');
        $this->assertInstanceOf(\MailboxRules\Matcher\Matcher::class, $folderMatcher);
    }

    #[Test]
    public function it_matches_exact_folder_path(): void
    {
        $folderMatcher = new FolderMatcher('INBOX');
        $message = $this->createMessageWithFolder('INBOX');

        $this->assertTrue($folderMatcher->matches($message));
    }

    #[Test]
    public function it_does_not_match_different_folder(): void
    {
        $folderMatcher = new FolderMatcher('INBOX');
        $message = $this->createMessageWithFolder('Sent');

        $this->assertFalse($folderMatcher->matches($message));
    }

    #[Test]
    public function it_matches_nested_folder_exact(): void
    {
        $folderMatcher = new FolderMatcher('Archives/2024');
        $message = $this->createMessageWithFolder('Archives/2024');

        $this->assertTrue($folderMatcher->matches($message));
    }

    #[Test]
    public function it_matches_wildcard_pattern(): void
    {
        $folderMatcher = new FolderMatcher('Archives/*');
        $message = $this->createMessageWithFolder('Archives/2024');

        $this->assertTrue($folderMatcher->matches($message));
    }

    #[Test]
    public function it_matches_wildcard_at_beginning(): void
    {
        $folderMatcher = new FolderMatcher('*/2024');
        $message = $this->createMessageWithFolder('Archives/2024');

        $this->assertTrue($folderMatcher->matches($message));
    }

    #[Test]
    public function it_matches_regex_pattern(): void
    {
        $folderMatcher = new FolderMatcher('/^Archives\/\d{4}$/i');
        $message = $this->createMessageWithFolder('Archives/2024');

        $this->assertTrue($folderMatcher->matches($message));
    }

    #[Test]
    public function it_handles_case_insensitive_match(): void
    {
        $folderMatcher = new FolderMatcher('inbox');
        $message = $this->createMessageWithFolder('INBOX');

        $this->assertTrue($folderMatcher->matches($message));
    }

    #[Test]
    #[DataProvider('provideMultiplePatterns')]
    public function it_matches_various_patterns(string $pattern, string $folderPath, bool $expected): void
    {
        $folderMatcher = new FolderMatcher($pattern);
        $message = $this->createMessageWithFolder($folderPath);

        $this->assertSame($expected, $folderMatcher->matches($message));
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function provideMultiplePatterns(): array
    {
        return [
            'exact match' => ['INBOX', 'INBOX', true],
            'exact no match' => ['INBOX', 'Sent', false],
            'nested exact match' => ['Projects/ClientA', 'Projects/ClientA', true],
            'nested no match' => ['Projects/ClientA', 'Projects/ClientB', false],
            'wildcard match all' => ['*', 'AnyFolder', true],
            'wildcard match nested' => ['Projects/*', 'Projects/ClientA', true],
            'wildcard no match' => ['Projects/*', 'Archives/2024', false],
            'wildcard both parts' => ['*/ClientA', 'Projects/ClientA', true],
            'regex match' => ['/^(INBOX|Sent)$/i', 'inbox', true],
            'regex no match' => ['/^(INBOX|Sent)$/i', 'Drafts', false],
            'case insensitive' => ['inbox', 'INBOX', true],
        ];
    }

    private function createMessageWithFolder(string $folderPath): Message
    {
        $message = $this->createStub(Message::class);
        $folder = $this->createStub(Folder::class);
        $folder->method('path')->willReturn($folderPath);
        $message->method('folder')->willReturn($folder);

        return $message;
    }
}
