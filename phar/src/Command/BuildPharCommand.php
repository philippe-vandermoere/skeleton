<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\{
    Command\Command,
    Input\InputInterface,
    Output\OutputInterface,
    Style\SymfonyStyle
};
use Symfony\Component\Finder\Finder;

class BuildPharCommand extends Command
{
    protected static $defaultName = 'phar:build';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $pharFilename = \dirname(__DIR__, 3) . '/post_create_project.phar';
        if (true === \file_exists($pharFilename)) {
            \unlink($pharFilename);
        }

        $phar = new \Phar($pharFilename, 0, 'post_create_project.phar');
        $phar->startBuffering();

        $finder = new Finder();
        $finder->files()
            ->name('*.php')
            ->in(\dirname(__DIR__, 2))
        ;

        foreach ($finder as $file) {
            $phar->addFile($file->getRealPath(), $file->getRelativePathname());
        }

        $content = file_get_contents(\dirname(__DIR__, 2) . '/bin/console');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('bin/console', $content);

        $stub = <<<'EOF'
#!/usr/bin/env php
<?php

Phar::mapPhar('post_create_project.phar');

require 'phar://post_create_project.phar/bin/console';
__HALT_COMPILER();
EOF;

        $phar->setStub($stub);
        $phar->stopBuffering();

        \chmod($pharFilename, 0755);

        $style->success($pharFilename . ' successfully created');

        return 0;
    }
}
