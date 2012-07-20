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

    use InvalidArgumentException,
        KevinGH\Box\Box,
        Phar,
        RuntimeException,
        Symfony\Component\Console\Command\Command,
        Symfony\Component\Console\Input\InputInterface,
        Symfony\Component\Console\Input\InputOption,
        Symfony\Component\Console\Output\OutputInterface;

    /**
     * The command that creates the PHAR.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class Create extends Command
    {
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
            if (true == ini_get('phar.readonly'))
            {
                throw new RuntimeException('PHAR writing has been disabled by "phar.readonly".');
            }

            $this->output = $output;

            $this->verbose = (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity());

            if ($this->verbose)
            {
                $output->writeln('Creating PHAR...');
            }

            else
            {
                $output->write('Creating PHAR...');
            }

            $config = $this->getHelper('config');

            $config->load($config->find($input->getOption('config')));

            if (true === $config['key-pass'])
            {
                $dialog = $this->getHelper('dialog');

                if ('' == ($config['key-pass'] = trim($dialog->ask($output, 'Private key password: '))))
                {
                    throw new InvalidArgumentException('Your private key password is required for signing.');
                }
            }

            $box = $this->start();

            $count = $this->add($box);
            $count += $this->add($box, true);

            $this->end($box);

            if ($this->verbose)
            {
                $output->writeln('Done.');
            }

            else
            {
                if (0 < $count)
                {
                    $output->writeln(' done.');
                }

                else
                {
                    $output->writeln(' no files found.');
                }
            }
        }

        /**
         * Adds files to the PHAR.
         *
         * @param Box $box The Box instance.
         * @param boolean $bin Binary safe adding of files?
         * @return integer The number of files added.
         */
        protected function add(Box $box, $bin = false)
        {
            if ($this->verbose)
            {
                $this->output->writeln(
                    $bin ? '    - Adding files (binary safe)'
                         : '    - Adding files'
                );
            }

            $counter = 0;

            $config = $this->getHelper('config');

            foreach ($config->getFiles($bin) as $file)
            {
                $relative = $config->relativeOf($file);

                if ($this->verbose)
                {
                    $this->output->writeln("        - $relative");
                }

                if ($bin)
                {
                    $box->addFile($file, $relative);
                }

                else
                {
                    $box->importFile($relative, $file);
                }

                $counter++;
            }

            if ($this->verbose && (0 == $counter))
            {
                $this->output->writeln('        - No files found');
            }

            return $counter;
        }

        /**
         * Ends by finishing the PHAR.
         *
         * @throws InvalidArgumentException If a file does not exist.
         * @throws RuntimeException If a file could not be read.
         * @param Box $box The Box instance.
         */
        protected function end(Box $box)
        {
            $config = $this->getHelper('config');

            $cwd = $config->getCurrentDir();

            chdir($config['base-path']);

            if ($config['main'])
            {
                $this->verbose('    - Adding main script');

                if (false === ($real = realpath($config['main'])))
                {
                    throw new InvalidArgumentException('The main file does not exist.');
                }

                $box->importFile($config->relativeOf($real), $real, true);
            }

            if (true === $config['stub'])
            {
                $this->verbose('    - Generating new stub');

                $box->setStub($box->createStub());
            }

            elseif ($config['stub'])
            {
                $this->verbose('    - Adding existing stub');

                if (false === file_exists($config['stub']))
                {
                    throw new InvalidArgumentException('The stub file does not exist.');
                }

                if (false === ($stub = @ file_get_contents($config['stub'])))
                {
                    $error = error_get_last();

                    throw new RuntimeException(sprintf(
                        'The stub file could not be read: %s',
                        $error['message']
                    ));
                }

                $box->setStub($stub);
            }

            $box->stopBuffering();

            if ($config['key'])
            {
                $this->verbose('    - Signing with private key');

                $box->usePrivateKeyFile($config['key'], $config['key-pass']);
            }

            else
            {
                $this->verbose('    - Signing without private key');

                $box->setSignatureAlgorithm($config['algorithm']);
            }

            chdir($cwd);
        }

        /**
         * Writes to output only if verbose.
         *
         * @param string|array $messages The message as an array of lines of a single string
         * @param integer $type The type of output
         */
        protected function verbose($message, $type = 0)
        {
            if ($this->verbose)
            {
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

            $box = new Box(
                $config['base-path'] . DIRECTORY_SEPARATOR . $config['output'],
                0,
                $config['alias']
            );

            if ($config['intercept'])
            {
                $this->verbose('    - Enabling file function intercept');

                $box->setIntercept(true);
            }

            if (null !== $config['metadata'])
            {
                $this->verbose('    - Setting metadata');

                $box->setMetadata($config['metadata']);
            }

            if ($config['replacements'])
            {
                $this->verbose('    - Setting replacement values');

                $box->setReplacements($config['replacements']);
            }

            $box->startBuffering();

            return $box;
        }
    }