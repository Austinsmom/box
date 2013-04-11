<?php

namespace KevinGH\Box\Tests\Command;

use KevinGH\Box\Command\Info;
use KevinGH\Box\Test\CommandTestCase;
use Phar;

class InfoTest extends CommandTestCase
{
    public function testGetInfo()
    {
        $tester = $this->getTester();
        $tester->execute(
            array(
                'command' => 'info'
            )
        );

        $version = Phar::apiVersion();
        $compression = '  - ' . join("\n  - ", Phar::getSupportedCompression());
        $signatures = '  - ' . join("\n  - ", Phar::getSupportedSignatures());
        $expected = <<<OUTPUT
API Version: $version

Supported Compression:
$compression

Supported Signatures:
$signatures

OUTPUT;

        $this->assertEquals($expected, $this->getOutput($tester));
    }

    public function testGetInfoPhar()
    {
        $phar = new Phar('test.phar');
        $phar->addFromString('a/b/c/d.php', '<?php echo "Hello!\n";');

        $version = $phar->getVersion();
        $signature = $phar->getSignature();

        unset($phar);

        $tester = $this->getTester();
        $tester->execute(
            array(
                'command' => 'info',
                'phar' => 'test.phar'
            )
        );

        $expected = <<<OUTPUT
API Version: $version

Compression: None

Signature: {$signature['hash_type']}

Signature Hash: {$signature['hash']}

OUTPUT;

        $this->assertEquals($expected, $this->getOutput($tester));
    }

    public function testGetInfoPharList()
    {
        $phar = new Phar('test.phar');
        $phar->addFromString('a/b/c/d.php', '<?php echo "Hello!\n";');

        $version = $phar->getVersion();
        $signature = $phar->getSignature();

        unset($phar);

        $tester = $this->getTester();
        $tester->execute(
            array(
                'command' => 'info',
                'phar' => 'test.phar',
                '--list' => true
            )
        );

        $expected = <<<OUTPUT
API Version: $version

Compression: None

Signature: {$signature['hash_type']}

Signature Hash: {$signature['hash']}

Contents:
a/
  b/
    c/
      d.php

OUTPUT;

        $this->assertEquals($expected, $this->getOutput($tester));
    }

    public function testGetInfoPharListFlat()
    {
        $phar = new Phar('test.phar');
        $phar->addFromString('a/b/c/d.php', '<?php echo "Hello!\n";');

        $version = $phar->getVersion();
        $signature = $phar->getSignature();

        unset($phar);

        $tester = $this->getTester();
        $tester->execute(
            array(
                'command' => 'info',
                'phar' => 'test.phar',
                '--mode' => 'flat',
                '--list' => true
            )
        );

        $ds = DIRECTORY_SEPARATOR;
        $expected = <<<OUTPUT
API Version: $version

Compression: None

Signature: {$signature['hash_type']}

Signature Hash: {$signature['hash']}

Contents:
a
a{$ds}b
a{$ds}b{$ds}c
a{$ds}b{$ds}c{$ds}d.php

OUTPUT;

        $this->assertEquals($expected, $this->getOutput($tester));
    }

    protected function getCommand()
    {
        return new Info();
    }
}
