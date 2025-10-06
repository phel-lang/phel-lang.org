<?php

declare(strict_types=1);

namespace PhelWebTests\VersionUpdater\Integration;

use Phel\Shared\Facade\ConsoleFacadeInterface;
use PhelWeb\VersionUpdater\Infrastructure\PhelVersionUpdater;
use PHPUnit\Framework\TestCase;

final class PhelVersionUpdaterTest extends TestCase
{
    /** @var resource */
    private $file;

    protected function setUp(): void
    {
        $this->file = tmpfile();
        fwrite(
            $this->file,
            <<<TXT
Some random directives
phel_version = "v.0.9"
And more here
TXT
        );
    }

    protected function tearDown(): void
    {
        fclose($this->file);
    }

    public function test_update_phel_version(): void
    {
        $consoleFacade = self::createStub(ConsoleFacadeInterface::class);
        $consoleFacade->method('getVersion')->willReturn('v1.0');

        $path = stream_get_meta_data($this->file)['uri'];
        $phelVersionUpdater = new PhelVersionUpdater($consoleFacade, $path);
        $phelVersionUpdater->update();

        $content = file_get_contents($path);
        $expected = <<<TXT
Some random directives
phel_version = "v1.0"
And more here
TXT;
        self::assertSame($expected, $content);
    }
}
