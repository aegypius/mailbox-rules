<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Console\Application;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $containerConfigurator->services()
        ->load('MailboxRules\\', './')
        ->exclude([
            './Action',
            './{functions,services}.php',
        ]);

    $containerConfigurator->services()
        ->set(Application::class)
        ->public()
        ->arg('$name', 'Mailbox Rules')
        ->arg('$version', '1.0.0')
        ->call('add', [service(\MailboxRules\Console\ApplyCommand::class)])
        ->call('setDefaultCommand', ['apply', true])
    ;
};
