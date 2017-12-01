<?php

declare(strict_types=1);

/*
 * This file is part of the box project.
 *
 * (c) Kevin Herrera <kevin@herrera.io>
 *     Théo Fidry <theo.fidry@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace KevinGH\Box;

use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

function canonicalize(string $path): string
{
    $lastChar = substr($path, -1);

    $canonical = Path::canonicalize($path);

    return '/' === $lastChar ? $canonical.$lastChar : $canonical;
}

function is_absolute(string $path): bool
{
    static $fileSystem;

    if (null === $fileSystem) {
        $fileSystem = new Filesystem();
    }

    return $fileSystem->isAbsolutePath($path);
}

function register_aliases(): void
{
    class_alias(\KevinGH\Box\Compactor\Javascript::class, \Herrera\Box\Compactor\Javascript::class);
    class_alias(\KevinGH\Box\Compactor\Json::class, \Herrera\Box\Compactor\Json::class);
    class_alias(\KevinGH\Box\Compactor\Php::class, \Herrera\Box\Compactor\Php::class);
}