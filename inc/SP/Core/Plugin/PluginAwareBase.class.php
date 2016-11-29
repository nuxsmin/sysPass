<?php

namespace SP\Core\Plugin;

use SP\Core\Exceptions\InvalidClassException;
use SP\Core\Exceptions\SPException;
use SplObserver;
use SplSubject;

abstract class PluginAwareBase implements SplSubject
{
    /**
     * @var SplObserver[]
     */
    protected $observers = [];

    /**
     * Attach an SplObserver
     * @link http://php.net/manual/en/splsubject.attach.php
     * @param SplObserver $observer <p>
     * The <b>SplObserver</b> to attach.
     * </p>
     * @throws InvalidClassException
     * @since 5.1.0
     */
    public function attach(SplObserver $observer)
    {
        $observerClass = get_class($observer);

        if (array_key_exists($observerClass, $this->observers)){
            throw new InvalidClassException(SPException::SP_ERROR, _('Plugin ya inicializado'));
        }

        $this->observers[$observerClass] = $observer;
    }

    /**
     * Detach an observer
     * @link http://php.net/manual/en/splsubject.detach.php
     * @param SplObserver $observer <p>
     * The <b>SplObserver</b> to detach.
     * </p>
     * @throws InvalidClassException
     * @since 5.1.0
     */
    public function detach(SplObserver $observer)
    {
        $observerClass = get_class($observer);

        if (!array_key_exists($observerClass, $this->observers)){
            throw new InvalidClassException(SPException::SP_ERROR, _('Plugin no inicializado'));
        }

        unset($this->observers[$observerClass]);
    }

    /**
     * Notify an observer
     * @link http://php.net/manual/en/splsubject.notify.php
     * @return void
     * @since 5.1.0
     */
    public function notify()
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }
}