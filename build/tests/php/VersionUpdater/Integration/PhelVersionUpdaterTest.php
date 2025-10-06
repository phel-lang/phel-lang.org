<?php

declare(strict_types=1);

namespace PhelWebTests\VersionUpdater\Integration;

use Phel\Shared\Facade\ConsoleFacadeInterface;
use PhelWeb\VersionUpdater\Infrastructure\PhelVersionUpdater;
use PHPUnit\Framework\TestCase;

final class PhelVersionUpdaterTest extends TestCase
{
    /** @var false|resource */
    private $file;

    protected function setUp(): void
    {
        $this->file = tmpfile();
        fwrite($this->file, 'phel_version = "v.0.9"');
    }

    protected function tearDown(): void
    {
        fclose($this->file);
    }

    public function test_update_phel_version(): void
    {
        $consoleFacade = $this->createAnonConsoleFacade();

        $path = stream_get_meta_data($this->file)['uri'];
        $phelVersionUpdater = new PhelVersionUpdater($consoleFacade, $path);
        $phelVersionUpdater->update();

        $content = file_get_contents($path);

        $expected = 'phel_version = "v1.0"';

        self::assertSame($expected, $content);
    }

    private function createAnonConsoleFacade(): ConsoleFacadeInterface
    {
        return new class implements ConsoleFacadeInterface {

            public function getVersion(): string
            {
                return 'v1.0';
            }

            public function runConsole(): void
            {
                // ignore
            }
        };
    }
}
