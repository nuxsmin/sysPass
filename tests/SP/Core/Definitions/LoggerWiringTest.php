<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace SP\Tests\Core\Definitions;

use DI\ContainerBuilder;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Core\Definitions\CoreDefinitions;
use SP\Domain\Config\Ports\ConfigDataInterface;

/**
 * Class LoggerWiringTest
 */
#[Group('unitary')]
class LoggerWiringTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logFile = tempnam(sys_get_temp_dir(), 'syspass-log-') ?: '';
    }

    protected function tearDown(): void
    {
        if ($this->logFile !== '' && is_file($this->logFile)) {
            unlink($this->logFile);
        }

        parent::tearDown();
    }

    #[Test]
    public function webLoggerWiresMonolog3HandlersAndKeepsOutputFormat(): void
    {
        $logger = $this->buildCoreLogger(false, false, false);

        self::assertInstanceOf(Logger::class, $logger);

        $handlers = $logger->getHandlers();
        self::assertCount(1, $handlers);
        self::assertInstanceOf(StreamHandler::class, $handlers[0]);
        self::assertSame(Level::Debug, $handlers[0]->getLevel());
        self::assertInstanceOf(LineFormatter::class, $handlers[0]->getFormatter());

        $testHandler = new TestHandler();
        $logger->pushHandler($testHandler);

        $logger->info('web message', ['key' => 'value']);

        $records = $testHandler->getRecords();
        self::assertCount(1, $records);
        self::assertSame(Level::Info, $records[0]->level);
        self::assertSame('web message', $records[0]->message);

        self::assertMatchesRegularExpression(
            '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] syspass\.INFO: web message \{"key":"value"\} \[\]\n$/',
            (string)file_get_contents($this->logFile)
        );
    }

    #[Test]
    public function webLoggerWiresLocalSyslogHandlerWithDebugLevel(): void
    {
        $logger = $this->buildCoreLogger(true, false, true);

        $handlers = $logger->getHandlers();

        self::assertCount(2, $handlers);
        self::assertInstanceOf(StreamHandler::class, $handlers[0]);
        self::assertInstanceOf(SyslogHandler::class, $handlers[1]);
        self::assertSame(Level::Debug, $handlers[1]->getLevel());
    }

    #[Test]
    public function webLoggerWiresRemoteSyslogHandlerWithDebugLevel(): void
    {
        if (!extension_loaded('sockets')) {
            self::markTestSkipped('The sockets extension is required to instantiate SyslogUdpHandler');
        }

        $logger = $this->buildCoreLogger(true, true, false);

        $handlers = $logger->getHandlers();

        self::assertCount(2, $handlers);
        self::assertInstanceOf(StreamHandler::class, $handlers[0]);
        self::assertInstanceOf(SyslogUdpHandler::class, $handlers[1]);
        self::assertSame(Level::Debug, $handlers[1]->getLevel());
    }

    private function buildCoreLogger(bool $installed, bool $syslogRemote, bool $syslog): LoggerInterface
    {
        $configData = $this->createStub(ConfigDataInterface::class);
        $configData->method('isInstalled')->willReturn($installed);
        $configData->method('isSyslogRemoteEnabled')->willReturn($syslogRemote);
        $configData->method('getSyslogServer')->willReturn('127.0.0.1');
        $configData->method('getSyslogPort')->willReturn(514);
        $configData->method('isSyslogEnabled')->willReturn($syslog);

        $paths = new PathsContext();
        $paths->addPath(Path::LOG_FILE, $this->logFile);

        $builder = new ContainerBuilder();
        $builder->addDefinitions(
            CoreDefinitions::getDefinitions(TEST_ROOT, 'test'),
            [
                ConfigDataInterface::class => $configData,
                PathsContext::class => $paths,
            ]
        );

        return $builder->build()->get(LoggerInterface::class);
    }
}
