<?php

namespace KevinGH\Box\Tests;

use Herrera\Box\Compactor\CompactorInterface;
use Herrera\PHPUnit\TestCase;
use KevinGH\Box\Configuration;
use Phar;

class ConfigurationTest extends TestCase
{
    /**
     * @var Configuration
     */
    private $config;

    private $cwd;
    private $dir;
    private $file;

    public function testGetAlias()
    {
        $this->assertEquals('default.phar', $this->config->getAlias());
    }

    public function testGetAliasSet()
    {
        $this->setConfig(array('alias' => 'test.phar'));

        $this->assertEquals('test.phar', $this->config->getAlias());
    }

    public function testGetBasePath()
    {
        $this->assertEquals($this->dir, $this->config->getBasePath());
    }

    public function testGetBasePathSet()
    {
        mkdir($this->dir . DIRECTORY_SEPARATOR . 'test');

        $this->setConfig(array(
            'base-path' => $this->dir . DIRECTORY_SEPARATOR . 'test'
        ));

        $this->assertEquals(
            $this->dir . DIRECTORY_SEPARATOR . 'test',
            $this->config->getBasePath()
        );
    }

    public function testGetBasePathNotExist()
    {
        $this->setConfig(array(
            'base-path' => $this->dir . DIRECTORY_SEPARATOR . 'test'
        ));

        $this->setExpectedException(
            'InvalidArgumentException',
            'The base path "'
                . $this->dir
                . DIRECTORY_SEPARATOR
                . 'test" is not a directory or does not exist.'
        );

        $this->config->getBasePath();
    }

    public function testGetBinaryDirectories()
    {
        $this->assertSame(array(), $this->config->getBinaryDirectories());
    }

    public function testGetBinaryDirectoriesSet()
    {
        mkdir($this->dir . DIRECTORY_SEPARATOR . 'test');

        $this->setConfig(array(
            'directories-bin' => $this->dir . DIRECTORY_SEPARATOR . 'test'
        ));

        $this->assertEquals(
            array($this->dir . DIRECTORY_SEPARATOR . 'test'),
            $this->config->getBinaryDirectories()
        );
    }

    public function testGetBinaryFiles()
    {
        $this->assertSame(array(), $this->config->getBinaryFiles());
    }

    public function testGetBinaryFilesSet()
    {
        mkdir($this->dir . DIRECTORY_SEPARATOR . 'test');

        $this->setConfig(array(
            'files-bin' => $this->dir . DIRECTORY_SEPARATOR . 'test'
        ));

        $this->assertEquals(
            array($this->dir . DIRECTORY_SEPARATOR . 'test'),
            $this->config->getBinaryFiles()
        );
    }

    public function testGetBinaryFinders()
    {
        $this->assertSame(array(), $this->config->getBinaryFinders());
    }

    public function testGetBinaryFindersSet()
    {
        touch('test.jpg');
        touch('test.png');
        touch('test.php');

        $this->setConfig(array(
            'finder-bin' => array(
                array(
                    'name' => '*.png',
                    'in' => $this->dir
                ),
                array(
                    'name' => '*.jpg',
                    'in' => $this->dir
                )
            )
        ));

        /** @var $results \SplFileInfo[] */
        $results = array();
        $finders = $this->config->getBinaryFinders();

        foreach ($finders as $finder) {
            foreach ($finder as $result) {
                $results[] = $result;
            }
        }

        $this->assertEquals('test.png', $results[0]->getBasename());
        $this->assertEquals('test.jpg', $results[1]->getBasename());
    }

    public function testGetBlacklist()
    {
        $this->assertSame(array(), $this->config->getBlacklist());
    }

    public function testGetBlacklistSet()
    {
        $this->setConfig(array(
            'blacklist' => array('test')
        ));

        $this->assertEquals(array('test'), $this->config->getBlacklist());
    }

    public function testGetCompactors()
    {
        $this->assertSame(array(), $this->config->getCompactors());
    }

    public function testGetCompactorsSet()
    {
        $this->setConfig(array(
            'compactors' => array(
                'Herrera\\Box\\Compactor\\Composer',
                __NAMESPACE__ . '\\TestCompactor'
            )
        ));

        $compactors = $this->config->getCompactors();

        $this->assertInstanceof(
            'Herrera\\Box\\Compactor\\Composer',
            $compactors[0]
        );
        $this->assertInstanceof(
            __NAMESPACE__ . '\\TestCompactor',
            $compactors[1]
        );
    }

    public function testGetCompactorsNoSuchClass()
    {
        $this->setConfig(array('compactors' => array('NoSuchClass')));

        $this->setExpectedException(
            'InvalidArgumentException',
            'The compactor class "NoSuchClass" does not exist.'
        );

        $this->config->getCompactors();
    }

    public function testGetCompactorsInvalidClass()
    {
        $this->setConfig(array('compactors' => array(
            __NAMESPACE__ . '\\InvalidCompactor'
        )));

        $this->setExpectedException(
            'InvalidArgumentException',
            'The class "'
                . __NAMESPACE__
                . '\\InvalidCompactor" is not a compactor class.'
        );

        $this->config->getCompactors();
    }

    public function testGetCompressionAlgorithm()
    {
        $this->assertNull($this->config->getCompressionAlgorithm());
    }

    public function testGetCompressionAlgorithmSet()
    {
        $this->setConfig(array('compression' => Phar::BZ2));

        $this->assertEquals(Phar::BZ2, $this->config->getCompressionAlgorithm());
    }

    public function testGetCompressionAlgorithmSetString()
    {
        $this->setConfig(array('compression' => 'BZ2'));

        $this->assertEquals(Phar::BZ2, $this->config->getCompressionAlgorithm());
    }

    public function testGetCompressionAlgorithmInvalidString()
    {
        $this->setConfig(array('compression' => 'INVALID'));

        $this->setExpectedException(
            'InvalidArgumentException',
            'The compression algorithm "INVALID" is not supported.'
        );

        $this->config->getCompressionAlgorithm();
    }

    public function testGetDirectories()
    {
        $this->assertSame(array(), $this->config->getDirectories());
    }

    public function testGetDirectoriesSet()
    {
        $this->setConfig(array('directories' => array('test')));

        $this->assertEquals(array('test'), $this->config->getDirectories());
    }

    public function testGetFileMode()
    {
        $this->assertNull($this->config->getFileMode());
    }

    public function testGetFileModeSet()
    {
        $this->setConfig(array('chmod' => '0755'));

        $this->assertEquals(0755, $this->config->getFileMode());
    }

    public function testGetFiles()
    {
        $this->assertSame(array(), $this->config->getFiles());
    }

    public function testGetFilesSet()
    {
        $this->setConfig(array('files' => array('test')));

        $this->assertEquals(array('test'), $this->config->getFiles());
    }

    public function testGetFinders()
    {
        $this->assertSame(array(), $this->config->getFinders());
    }

    public function testGetFindersSet()
    {
        touch('test.html');
        touch('test.txt');
        touch('test.php');

        $this->setConfig(array(
            'finder' => array(
                array(
                    'name' => '*.php',
                    'in' => $this->dir
                ),
                array(
                    'name' => '*.html',
                    'in' => $this->dir
                )
            )
        ));

        /** @var $results \SplFileInfo[] */
        $results = array();
        $finders = $this->config->getFinders();

        foreach ($finders as $finder) {
            foreach ($finder as $result) {
                $results[] = $result;
            }
        }

        $this->assertEquals('test.php', $results[0]->getBasename());
        $this->assertEquals('test.html', $results[1]->getBasename());
    }

    public function testGetVersionPlaceholder()
    {
        $this->assertNull($this->config->getGitVersionPlaceholder());
    }

    public function testGetVersionPlaceholderSet()
    {
        $this->setConfig(array('git-version' => 'git_version'));

        $this->assertEquals(
            'git_version',
            $this->config->getGitVersionPlaceholder()
        );
    }

    public function testGetMainScriptPath()
    {
        $this->assertNull($this->config->getMainScriptPath());
    }

    public function testGetMainScriptPathSet()
    {
        $this->setConfig(array('main' => 'test.php'));

        $this->assertEquals('test.php', $this->config->getMainScriptPath());
    }

    public function testGetMetadata()
    {
        $this->assertNull($this->config->getMetadata());
    }

    public function testGetMetadataSet()
    {
        $this->setConfig(array('metadata' => 123));

        $this->assertSame(123, $this->config->getMetadata());
    }

    public function testGetOutputPath()
    {
        $this->assertEquals('default.phar', $this->config->getOutputPath());
    }

    public function testGetOutputPathSet()
    {
        $this->setConfig(array('output' => 'test.phar'));

        $this->assertEquals('test.phar', $this->config->getOutputPath());
    }

    public function testGetPrivateKeyPassphrase()
    {
        $this->assertNull($this->config->getPrivateKeyPassphrase());
    }

    public function testGetPrivateKeyPassphraseSet()
    {
        $this->setConfig(array('key-pass' => 'test'));

        $this->assertEquals('test', $this->config->getPrivateKeyPassphrase());
    }

    public function testGetPrivateKeyPassphraseSetBoolean()
    {
        $this->setConfig(array('key-pass' => true));

        $this->assertNull($this->config->getPrivateKeyPassphrase());
    }

    public function testGetPrivateKeyPath()
    {
        $this->assertNull($this->config->getPrivateKeyPath());
    }

    public function testGetPrivateKeyPathSet()
    {
        $this->setConfig(array('key' => 'test.pem'));

        $this->assertEquals('test.pem', $this->config->getPrivateKeyPath());
    }

    public function testGetReplacements()
    {
        $this->assertSame(array(), $this->config->getReplacements());
    }

    public function testGetReplacementsSet()
    {
        $replacements = array('rand' => rand());

        $this->setConfig(array('replacements' => (object) $replacements));

        $this->assertEquals($replacements, $this->config->getReplacements());
    }

    public function testGetSigningAlgorithm()
    {
        $this->assertSame(Phar::SHA1, $this->config->getSigningAlgorithm());
    }

    public function testGetSigningAlgorithmSet()
    {
        $this->setConfig(array('algorithm' => Phar::MD5));

        $this->assertEquals(Phar::MD5, $this->config->getSigningAlgorithm());
    }

    public function testGetSigningAlgorithmSetString()
    {
        $this->setConfig(array('algorithm' => 'MD5'));

        $this->assertEquals(Phar::MD5, $this->config->getSigningAlgorithm());
    }

    public function testGetSigningAlgorithmInvalidString()
    {
        $this->setConfig(array('algorithm' => 'INVALID'));

        $this->setExpectedException(
            'InvalidArgumentException',
            'The signing algorithm "INVALID" is not supported.'
        );

        $this->config->getSigningAlgorithm();
    }

    public function testGetStubPath()
    {
        $this->assertNull($this->config->getStubPath());
    }

    public function testGetStubPathSet()
    {
        $this->setConfig(array('stub' => 'test.php'));

        $this->assertEquals('test.php', $this->config->getStubPath());
    }

    public function testGetStubPathSetBoolean()
    {
        $this->setConfig(array('stub' => true));

        $this->assertNull($this->config->getStubPath());
    }

    public function testIsInterceptFileFuncs()
    {
        $this->assertFalse($this->config->isInterceptFileFuncs());
    }

    public function testIsInterceptFileFuncsSet()
    {
        $this->setConfig(array('intercept' => true));

        $this->assertTrue($this->config->isInterceptFileFuncs());
    }

    public function testIsPrivateKeyPrompt()
    {
        $this->assertFalse($this->config->isPrivateKeyPrompt());
    }

    public function testIsPrivateKeyPromptSet()
    {
        $this->setConfig(array('key-pass' => true));

        $this->assertTrue($this->config->isPrivateKeyPrompt());
    }

    public function testIsPrivateKeyPromptSetString()
    {
        $this->setConfig(array('key-pass' => 'test'));

        $this->assertFalse($this->config->isPrivateKeyPrompt());
    }

    public function testIsStubGenerated()
    {
        $this->assertFalse($this->config->isStubGenerated());
    }

    public function testIsStubGeneratedSet()
    {
        $this->setConfig(array('stub' => true));

        $this->assertTrue($this->config->isStubGenerated());
    }

    public function testIsStubGeneratedSetString()
    {
        $this->setConfig(array('stub' => 'test.php'));

        $this->assertFalse($this->config->isStubGenerated());
    }

    public function testProcessFindersInvalidMethod()
    {
        $this->setConfig(array('finder' => array(
            array('invalidMethod' => 'whargarbl')
        )));

        $this->setExpectedException(
            'InvalidArgumentException',
            'The method "Finder::invalidMethod" does not exist.'
        );

        $this->config->getFinders();
    }

    protected function tearDown()
    {
        chdir($this->cwd);

        parent::tearDown();
    }

    protected function setUp()
    {
        $this->cwd = getcwd();
        $this->dir = $this->createDir();
        $this->file = $this->dir . DIRECTORY_SEPARATOR . 'box.json';
        $this->config = new Configuration($this->file, (object) array());

        chdir($this->dir);
        touch($this->file);
    }

    private function setConfig(array $config)
    {
        $this->setPropertyValue($this->config, 'raw', (object) $config);
    }
}

class InvalidCompactor
{
}

class TestCompactor implements CompactorInterface
{
    public function compact($contents)
    {
    }

    public function supports($file)
    {
    }
}