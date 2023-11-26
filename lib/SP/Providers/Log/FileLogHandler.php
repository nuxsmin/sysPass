<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Providers\Log;


use Monolog\Handler\StreamHandler;
use SP\Core\Events\Event;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Providers\EventsTrait;
use SplSubject;

/**
 * Class FileLogHandler
 *
 * @package SP\Providers\Log
 */
final class FileLogHandler extends LoggerBase
{
    use EventsTrait;

    /**
     * Devuelve los eventos que implementa el observador
     *
     * @return array
     */
    public function getEvents(): array
    {
        return LogInterface::EVENTS;
    }

    /**
     * Devuelve los eventos que implementa el observador en formato cadena
     *
     * @return string
     */
    public function getEventsString(): string
    {
        return $this->events;
    }

    /**
     * Receive update from subject
     *
     * @link  http://php.net/manual/en/splobserver.update.php
     *
     * @param  SplSubject  $subject  <p>
     *                            The <b>SplSubject</b> notifying the observer of an update.
     *                            </p>
     *
     * @return void
     * @throws InvalidClassException
     * @since 5.1.0
     */
    public function update(SplSubject $subject): void
    {
        $this->update('update', new Event($subject));
    }

    public function initialize(): void
    {
        $this->logger->pushHandler(new StreamHandler(LOG_FILE));

        parent::initialize();
    }
}
