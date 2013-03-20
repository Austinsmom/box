<?php

namespace KevinGH\Box\Command;

use Phar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Extracts files from a Phar.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Extract extends Command
{
    /**
     * @override
     */
    protected function configure()
    {
        $this->setName('extract');
        $this->setDescription('Extracts files from a Phar.');
        $this->addArgument(
            'phar',
            InputArgument::REQUIRED,
            'The Phar to extract from.'
        );
        $this->addOption(
            'pick',
            'p',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'The file or directory to cherry pick.'
        );
        $this->addOption(
            'out',
            'o',
            InputOption::VALUE_REQUIRED,
            'The alternative output directory. (default: name.phar-contents)'
        );
    }

    /**
     * @override
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $verbose = (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity());
        $phar = $input->getArgument('phar');

        if ($verbose) {
            $output->writeln('Extracting files from the Phar...');
        }

        if (false === is_file($phar)) {
            $output->writeln(sprintf(
                    '<error>The path "%s" is not a file or does not exist.</error>',
                    $phar
                ));

            return 1;
        }

        if (null === ($out = $input->getOption('out'))) {
            $out = $phar . '-contents';
        }

        $phar = new Phar($phar);
        $files = $input->getOption('pick') ?: null;

        // backslash paths causes segfault
        if ($files) {
            $files = (array) $files;

            array_walk($files, function (&$file) {
                $file = str_replace(
                    '\\',
                    '/',
                    canonical_path($file)
                );
            });
        }

        $phar->extractTo($out, $files, true);

        if ($verbose) {
            $output->writeln('Done.');
        }
    }
}