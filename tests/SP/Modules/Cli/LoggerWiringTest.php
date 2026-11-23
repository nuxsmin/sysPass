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

namespace SP\Tests\Modules\Cli;

use DateTimeImmutable;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

/**
 * Class LoggerWiringTest
 */
#[Group('unitary')]
class LoggerWiringTest extends CliTestCase
{
    #[Test]
    public function loggerWiresMonolog3StreamHandlerAndKeepsOutputFormat(): void
    {
        $logger = self::$dic->get(LoggerInterface::class);

        self::assertInstanceOf(Logger::class, $logger);

        $handlers = $logger->getHandlers();
        self::assertCount(1, $handlers);
        self::assertInstanceOf(StreamHandler::class, $handlers[0]);
        self::assertSame(Level::Debug, $handlers[0]->getLevel());

        $formatter = $handlers[0]->getFormatter();
        self::assertInstanceOf(LineFormatter::class, $formatter);

        $record = new LogRecord(
            new DateTimeImmutable('2026-11-02 11:00:00'),
            'syspass',
            Level::Warning,
            'cli message',
            ['source' => 'cli']
        );

        self::assertMatchesRegularExpression(
            '/^\[2026-11-02 11:00:00\] syspass\.WARNING: cli message \{"source":"cli"\} \[\]\n$/',
            $formatter->format($record)
        );
    }
}
