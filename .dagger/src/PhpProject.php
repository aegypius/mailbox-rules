<?php

declare(strict_types=1);

namespace DaggerModule;

use CompileError;
use Dagger\Attribute\DaggerFunction;
use Dagger\Attribute\DaggerObject;
use Dagger\Attribute\DefaultPath;
use Dagger\Attribute\Doc;
use Dagger\Attribute\Ignore;
use Dagger\Container;
use Dagger\Directory;
use InvalidArgumentException;

use function Dagger\dag;

#[DaggerObject]
#[Doc("PHP Code Quality functions")]
class PhpProject
{
    /**
     * Allows to install vendors with
     *
     * @throws CompileError
     * @throws InvalidArgumentException
     */
    private function vendors(Directory $source): Directory
    {
        return dag()
            ->container()
            ->from("composer:2")
            ->withMountedDirectory("/app", $source)
            ->withWorkdir("/app")
            ->withExec([
                "composer",
                "install",
                "--prefer-dist",
                "--no-interaction",
            ])
            ->directory("/app/vendor");
    }

    #[DaggerFunction("check-coding-standards")]
    #[Doc("Check coding standards")]
    public function checkCodingStandards(
        #[DefaultPath("."), Ignore("**/vendor", "docs")]
        Directory $source
    ): Container {
        return dag()
            ->container()
            ->from("php:8.3-cli")
            ->withMountedDirectory("/app", $source)
            ->withDirectory("/app/vendor", $this->vendors($source))
            ->withWorkdir("/app")
            ->withExec([
                "./vendor/bin/ecs",
                "check",
                "--no-progress-bar",
                "--ansi",
            ]);
    }

    #[DaggerFunction("test")]
    #[Doc("Run test-suite")]
    public function test(
        #[DefaultPath("."), Ignore("**/vendor", "docs")]
        Directory $source,
        string|null $testSuite = null
    ): Container {
        $phpunit = ["./vendor/bin/phpunit"];
        if ($testSuite !== null) {
            $phpunit[] = "--testsuite={$testSuite}";
        }

        return dag()
            ->container()
            ->from("php:8.3-cli")
            ->withMountedDirectory("/app", $source)
            ->withDirectory("/app/vendor", $this->vendors($source))
            ->withWorkdir("/app")
            ->withExec($phpunit);
    }
}
