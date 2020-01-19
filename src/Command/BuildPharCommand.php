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
    protected const PHAR_FILENAME = 'philou.phar';

    protected static $defaultName = 'phar:build';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $pharFilename = \dirname(__DIR__, 2) . '/' . static::PHAR_FILENAME;

        if (true === \file_exists($pharFilename)) {
            \unlink($pharFilename);
        }

        $phar = new \Phar($pharFilename, 0, static::PHAR_FILENAME);
        $phar->startBuffering();

        $finder = new Finder();
        $finder
            ->files()
            ->in(\dirname(__DIR__, 2) . '/skeleton')
            ->ignoreDotFiles(false)
        ;

        foreach ($finder as $file) {
            $phar->addFile($file->getRealPath(), 'skeleton/' . $file->getRelativePathname());
        }

        $finder = new Finder();
        $finder
            ->files()
            ->in(\dirname(__DIR__, 2))
            ->name('*.php')
            ->notPath('/skeleton/')
        ;

        foreach ($finder as $file) {
            $phar->addFile($file->getRealPath(), $file->getRelativePathname());
        }

        $phar->addFromString(
            'bin/console',
            \preg_replace(
                '{^#!/usr/bin/env php\s*}',
                '',
                \file_get_contents(\dirname(__DIR__, 2) . '/bin/console')
            )
        );

        $stub = '#!/usr/bin/env php' . PHP_EOL;
        $stub .= '<?php' . PHP_EOL . PHP_EOL;
        $stub .= 'Phar::mapPhar(\'' . static::PHAR_FILENAME . '\');'. PHP_EOL . PHP_EOL;
        $stub .= 'require \'phar://' . static::PHAR_FILENAME . '/bin/console\';'. PHP_EOL . PHP_EOL;
        $stub .= '__HALT_COMPILER();'. PHP_EOL;

        $phar->setStub($stub);
        $phar->stopBuffering();

        \chmod($pharFilename, 0755);

        $style->success($pharFilename . ' successfully created');

        return 0;
    }
}
