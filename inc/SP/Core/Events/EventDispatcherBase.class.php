<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Events;

use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\InvalidClassException;
use SP\Core\Exceptions\SPException;
use SplObserver;

/**
 * Class EventDispatcherBase
 *
 * @package SP\Core\Events
 */
abstract class EventDispatcherBase implements EventDispatcherInterface
{
    /**
     * @var EventReceiver[]
     */
    protected $observers = [];

    /**
     * Attach an SplObserver
     *
     * @link  http://php.net/manual/en/splsubject.attach.php
     * @param SplObserver $observer <p>
     *                              The <b>SplObserver</b> to attach.
     *                              </p>
     * @since 5.1.0
     */
    public function attach(SplObserver $observer)
    {
        $observerClass = get_class($observer);

        if (array_key_exists($observerClass, $this->observers)) {
            return;
//            throw new InvalidClassException(sprintf(__('Observador ya inicializado "%s"'), $observerClass));
        }

        $this->observers[$observerClass] = $observer;
    }

    /**
     * Detach an observer
     *
     * @link  http://php.net/manual/en/splsubject.detach.php
     * @param SplObserver $observer <p>
     *                              The <b>SplObserver</b> to detach.
     *                              </p>
     * @throws InvalidClassException
     * @since 5.1.0
     */
    public function detach(SplObserver $observer)
    {
        $observerClass = get_class($observer);

        if (!array_key_exists($observerClass, $this->observers)) {
            throw new InvalidClassException(__('Observador no inicializado'));
        }

        unset($this->observers[$observerClass]);
    }

    /**
     * Notify an observer
     *
     * @link  http://php.net/manual/en/splsubject.notify.php
     * @return void
     * @since 5.1.0
     */
    public function notify()
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }

    /**
     * Notificar un evento
     *
     * @param string $event
     * @param mixed  $object
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function notifyEvent($event, $object)
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException(SPException::SP_ERROR, __('Es necesario un objeto'));
        }

        foreach ($this->observers as $observer) {
            if (in_array($event, $observer->getEvents(), true)) {
                $observer->updateEvent($event, $object);
            }
        }
    }
}