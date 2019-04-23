<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Providers\Log;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Monolog\Logger;
use SP\Core\Events\Event;
use SP\Core\Events\EventReceiver;
use SP\Core\Exceptions\InvalidClassException;
use SP\Core\Language;
use SP\Http\Request;
use SP\Providers\EventsTrait;
use SP\Providers\Provider;

/**
 * Class LoggerBase
 *
 * @package SP\Providers\Log
 */
abstract class LoggerBase extends Provider implements EventReceiver
{
    use EventsTrait;

    const MESSAGE_FORMAT = 'event="%s";address="%s";user="%s";message="%s"';
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var string
     */
    protected $events;
    /**
     * @var Language
     */
    protected $language;

    /**
     * Evento de actualización
     *
     * @param string $eventType Nombre del evento
     * @param Event  $event     Objeto del evento
     *
     * @throws InvalidClassException
     */
    public function updateEvent($eventType, Event $event)
    {
        $this->language->setAppLocales();

        $userLogin = $this->context->getUserData()->getLogin() ?: 'N/A';
        $source = $event->getSource();

        if ($source instanceof Exception) {
            /** @var Exception $source */
            $this->logger->error($eventType,
                $this->formatContext(
                    __($source->getMessage()),
                    $this->request->getClientAddress(true),
                    $userLogin));
        } elseif (($eventMessage = $event->getEventMessage()) !== null) {
            $this->logger->debug($eventType,
                $this->formatContext(
                    $eventMessage->composeText(' | '),
                    $this->request->getClientAddress(true),
                    $userLogin));
        } else {
            $this->logger->debug($eventType,
                $this->formatContext(
                    'N/A',
                    $this->request->getClientAddress(true),
                    $userLogin));
        }

        $this->language->unsetAppLocales();
    }

    /**
     * @param $message
     * @param $address
     * @param $user
     *
     * @return array
     */
    final protected function formatContext($message, $address, $user): array
    {
        return [
            'message' => trim($message),
            'user' => trim($user),
            'address' => trim($address),
            'caller' => getLastCaller(4)
        ];
    }

    /**
     * @param Container $dic
     *
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function initialize(Container $dic)
    {
        $this->language = $dic->get(Language::class);
        $this->request = $dic->get(Request::class);

        $configEvents = $this->config->getConfigData()->getLogEvents();

        if (empty($configEvents)) {
            $this->events = $this->parseEventsToRegex(LogInterface::EVENTS_FIXED);
        } else {
            $this->events = $this->parseEventsToRegex(array_merge($configEvents, LogInterface::EVENTS_FIXED));
        }

        $this->logger = $dic->get(Logger::class);
    }
}