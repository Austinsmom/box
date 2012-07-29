<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Console\Command;

use InvalidArgumentException;
use KevinGH\Box\Box;
use Phar;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command that creates the PHAR.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Create extends Command
{
    /**
     * The files added counter.
     *
     * @type integer
     */
    private $counter = 0;

    /**
     * The output instance.
     *
     * @type OutputInterface
     */
    private $output;

    /**
     * The verbosity level.
     *
     * @type boolean
     */
    private $verbose = false;

    /** {@inheritDoc} */
    public function configure()
    {
        $this->setName('create')
             ->setDescription('Creates a new PHAR.');

        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'The configuration file path.'
        );
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (true == ini_get('phar.readonly')) {
            throw new RuntimeException('PHAR writing has been disabled by "phar.readonly".');
        }

        $this->output = $output;

        $this->verbose = (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity());

        if ($this->verbose) {
            $output->writeln('Creating PHAR...');
        } else {
            $output->write('Creating PHAR...');
        }

        $config = $this->getHelper('config');

        $config->load($config->find($input->getOption('config')));

        if (true === $config['key-pass']) {
            $dialog = $this->getHelper('dialog');

            if ('' == ($config['key-pass'] = trim($dialog->ask($output, 'Private key password: ')))) {
                throw new InvalidArgumentException('Your private key password is required for signing.');
            }
        }

        $box = $this->start();

        $this->add($box);
        $this->add($box, true);

        $this->end($box);

        unset($box);

        if (null !== $config['chmod']) {
            if (false === @ chmod(
                $config['base-path'] . DIRECTORY_SEPARATOR . $config['output'],
                intval($config['chmod'], 8)
            )) {
                $error = error_get_last();

                throw new RuntimeException(sprintf(
                    'The PHAR could not be chmodded to "%s": %s',
                    $config['chmod'],
                    $error['message']
                ));
            }
        }

        if ($this->verbose) {
            $output->writeln('Done.');
        } else {
            if (0 < $this->counter) {
                $output->writeln(' done.');
            } else {
                $output->writeln(' no files found.');
            }
        }
    }

    /**
     * Adds files to the PHAR.
     *
     * @param Box     $box The Box instance.
     * @param boolean $bin Binary safe adding of files?
     *
     * @return integer The number of files added.
     */
    protected function add(Box $box, $bin = false)
    {
        if ($this->verbose) {
            $this->output->writeln(
                '    - Adding files' . ($bin ? ' (binary safe)' : '')
            );
        }

        $config = $this->getHelper('config');

        foreach ($config->getFiles($bin) as $file) {
            $relative = $config->relativeOf($file);

            if ($this->verbose) {
                $this->output->writeln("        - $relative");
            }

            if ($bin) {
                $box->addFile($file, $relative);
            } else {
                $box->importFile($relative, $file);
            }

            $this->counter++;
        }

        if ($this->verbose && (0 == $this->counter)) {
            $this->output->writeln('        - No files found');
        }
    }

    /**
     * Ends by finishing the PHAR.
     *
     * @param Box $box The Box instance.
     *
     * @throws InvalidArgumentException If a file does not exist.
     * @throws RuntimeException         If a file could not be read.
     */
    protected function end(Box $box)
    {
        $config = $this->getHelper('config');

        $cwd = $config->getCurrentDir();

        chdir($config['base-path']);

        if ($config['main']) {
            $this->verbose('    - Adding main script');

            if (false === ($real = realpath($config['main']))) {
                throw new InvalidArgumentException('The main file does not exist.');
            }

            $box->importFile($relative = $config->relativeOf($real), $real, true);

            if ($config['compression'] && (false === isset($box['index.php']))) {
                $box->addFromString('index.php', $box[$relative]->getContent());
            }

            $this->counter++;
        }

        if (true === $config['stub']) {
            $this->verbose('    - Generating new stub');

            $box->setStub($box->createStub());
        } elseif ($config['stub']) {
            $this->verbose('    - Adding existing stub');

            if (false === file_exists($config['stub'])) {
                throw new InvalidArgumentException('The stub file does not exist.');
            }

            if (false === ($stub = @ file_get_contents($config['stub']))) {
                $error = error_get_last();

                throw new RuntimeException(sprintf(
                    'The stub file could not be read: %s',
                    $error['message']
                ));
            }

            $box->setStub($stub);
        }

        $box->stopBuffering();

        if ($config['compression']) {
            if (isset($config['stub'])) {
                $this->verbose('    - <comment>Enabling compression overrides the stub.</comment>');
            }

            $box->compress($config['compression']);
        }

        if ($config['key']) {
            $this->verbose('    - Signing with private key');

            $box->usePrivateKeyFile($config['key'], $config['key-pass']);
        } else {
            $this->verbose('    - Signing without private key');

            $box->setSignatureAlgorithm($config['algorithm']);
        }

        chdir($cwd);
    }

    /**
     * Writes to output only if verbose.
     *
     * @param string|array $message The message as an array of lines of a single string
     * @param integer      $type    The type of output
     */
    protected function verbose($message, $type = 0)
    {
        if ($this->verbose) {
            $this->output->writeln($message, $type);
        }
    }

    /**
     * Starts a new PHAR.
     *
     * @return Box The Box instance.
     */
    protected function start()
    {
        $config = $this->getHelper('config');

        $path = $config['base-path'] . DIRECTORY_SEPARATOR . $config['output'];

        foreach (array('', '.bz2', '.gz', '.tar', '.zip') as $ext) {
            if (file_exists($path . $ext)) {
                if (false === @ unlink($path . $ext)) {
                    $error = error_get_last();

                    throw new RuntimeException(sprintf(
                        'The old PHAR "%s" could not be deleted: %s',
                        $path . $ext,
                        $error['message']
                    ));
                }
            }
        }

        $box = new Box($path, 0, $config['alias']);

        if ($config['intercept']) {
            $this->verbose('    - Enabling file function intercept');

            $box->setIntercept(true);
        }

        if (null !== $config['metadata']) {
            $this->verbose('    - Setting metadata');

            $box->setMetadata($config['metadata']);
        }

        if ($config['replacements']) {
            $this->verbose('    - Setting replacement values');

            $box->setReplacements($config['replacements']);
        }

        $box->startBuffering();

        return $box;
    }
}