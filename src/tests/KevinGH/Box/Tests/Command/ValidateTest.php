<?php

namespace KevinGH\Box\Tests\Command;

use Exception;
use KevinGH\Box\Command\Validate;
use KevinGH\Box\Test\CommandTestCase;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateTest extends CommandTestCase
{
    public function testExecute()
    {
        file_put_contents('test.json', '{}');

        $tester = $this->getTester();
        $tester->execute(array(
            'command' => 'validate',
            'file' => 'test.json'
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ));

        $this->assertEquals(
            <<<OUTPUT
Validating the Box configuration file...
Found: test.json
The configuration file passed validation.

OUTPUT
            ,
            $this->getOutput($tester)
        );
    }

    public function testExecuteNotFound()
    {
        $tester = $this->getTester();

        $this->assertEquals(1, $tester->execute(array('command' => 'validate')));
        $this->assertEquals(
            <<<OUTPUT
No configuration file could be found.

OUTPUT
            ,
            $this->getOutput($tester)
        );
    }

    public function testExecuteFailed()
    {
        file_put_contents('box.json.dist', '{');

        $tester = $this->getTester();
        $exit = $tester->execute(array('command' => 'validate'));

        $this->assertEquals(1, $exit);
        $this->assertEquals(
            <<<OUTPUT
The configuration file failed validation.

OUTPUT
            ,
            $this->getOutput($tester)
        );
    }

    public function testExecuteFailedVerbose()
    {
        file_put_contents('box.json', '{');

        $tester = $this->getTester();

        try {
            $tester->execute(array(
                'command' => 'validate'
            ), array(
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE
            ));
        } catch (Exception $exception) {
        }

        $this->assertTrue(isset($exception));
        $this->assertEquals(
            <<<OUTPUT
Validating the Box configuration file...
Found: box.json
The configuration file failed validation.

OUTPUT
            ,
            $this->getOutput($tester)
        );
    }

    protected function getCommand()
    {
        return new Validate();
    }
}