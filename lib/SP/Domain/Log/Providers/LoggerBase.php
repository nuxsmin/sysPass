<?php

declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Log\Providers;

use Exception;
use Psr\Log\LoggerInterface;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Domain\Common\Providers\EventsTrait;
use SP\Domain\Common\Providers\Provider;
use SP\Domain\Core\Events\EventReceiver;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Http\Ports\RequestService;

use function SP\__;
use function SP\getLastCaller;

/**
 * Class LoggerBase
 */
abstract class LoggerBase extends Provider implements EventReceiver
{
    use EventsTrait;

    protected readonly string $events;

    public function __construct(
        Application                          $application,
        protected readonly LoggerInterface $logger,
        protected readonly LanguageInterface $language,
        protected readonly RequestService  $request
    ) {
        parent::__construct($application);

        $configEvents = $this->config->getConfigData()->getLogEvents();

        if (count($configEvents) === 0) {
            $this->events = $this->parseEventsToRegex(LogInterface::EVENTS_FIXED);
        } else {
            $this->events = $this->parseEventsToRegex(array_merge($configEvents, LogInterface::EVENTS_FIXED));
        }
    }

    /**
     * Evento de actualización
     *
     * @param string $eventType Nombre del evento
     * @param Event $event Objeto del evento
     *
     * @throws InvalidClassException
     */
    public function update(string $eventType, Event $event): void
    {
        $this->language->setAppLocales();

        $userLogin = 'N/A';

        if ($this->context->isInitialized()) {
            $userLogin = $this->context->getUserData()->login ?? 'N/A';
        }

        $source = $event->getSource();

        if ($source instanceof Exception) {
            $this->logger->error(
                $eventType,
                $this->formatContext(
                    __($source->getMessage()),
                    $this->request->getClientAddress(true),
                    $userLogin
                )
            );
        } elseif (($eventMessage = $event->getEventMessage()) !== null) {
            $this->logger->debug(
                $eventType,
                $this->formatContext(
                    $eventMessage->composeText(' | '),
                    $this->request->getClientAddress(true),
                    $userLogin
                )
            );
        } else {
            $this->logger->debug(
                $eventType,
                $this->formatContext(
                    'N/A',
                    $this->request->getClientAddress(true),
                    $userLogin
                )
            );
        }

        $this->language->unsetAppLocales();
    }

    /**
     * @param string $message
     * @param string $address
     * @param string $user
     *
     * @return array
     */
    final protected function formatContext(string $message, string $address, string $user): array
    {
        return [
            'message' => trim($message),
            'user' => trim($user),
            'address' => trim($address),
            'caller' => getLastCaller(4),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getEvents(): ?string
    {
        return $this->events;
    }
}
