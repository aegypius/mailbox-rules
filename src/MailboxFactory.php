<?php

declare(strict_types=1);

namespace MailboxRules;

use DirectoryTree\ImapEngine\Mailbox;
use MailboxRules\ValueObject\Dsn;

final readonly class MailboxFactory
{
    public static function createMailbox(Dsn $dsn): Mailbox
    {
        return new Mailbox([
            'port' => $dsn->port,               // The port number to connect to
            'host' => $dsn->host,                // The hostname of the IMAP server
            'username' => $dsn->user,           // Your IMAP username
            'password' => $dsn->password,        //	Your IMAP password or OAuth token
            // timeout	int	Connection timeout in seconds
            // 'encryption'	string	Encryption method ('ssl', 'starttls', or null for no encryption)
            // debug	bool	Enable debug logging
            // validate_cert	bool	Whether to validate SSL certificates
            // authentication	string	Authentication method ('plain' or 'oauth')
            // proxy	array	Proxy configuration options
        ]);
    }
}
