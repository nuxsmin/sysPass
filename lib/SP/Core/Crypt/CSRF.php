<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2020, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Crypt;


use SP\Config\ConfigData;
use SP\Core\Context\ContextInterface;
use SP\Core\Context\SessionContext;
use SP\Http\Request;

/**
 * Class CSRF
 *
 * @package SP\Core\Crypt
 */
final class CSRF
{
    /**
     * @var SessionContext
     */
    private $context;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var ConfigData
     */
    private $configData;

    /**
     * CSRF constructor.
     *
     * @param ContextInterface $context
     * @param Request          $request
     * @param ConfigData       $configData
     */
    public function __construct(ContextInterface $context, Request $request, ConfigData $configData)
    {
        $this->context = $context;
        $this->request = $request;
        $this->configData = $configData;
    }

    /**
     * Check for CSRF token on POST requests
     */
    public function check()
    {
        $method = strtoupper($this->request->getMethod());
        $with = $this->request->getHeader('X-Requested-With');

        if ($this->context->isLoggedIn()
            && $this->context->getCSRF() !== null
            && ($method === 'POST'
                || ($method === 'GET' && $with === 'XMLHttpRequest'))
        ) {
            $token = $this->request->getHeader('X-CSRF');

            if (empty($token)
                || !Hash::checkMessage($this->getKey(), $this->configData->getPasswordSalt(), $token)
            ) {
                logger(sprintf('Invalid CSRF token: %s', $token), 'ERROR');

                return false;
            }

            logger('CSRF token OK');
        }

        return true;
    }

    /**
     * Devolver la llave de cifrado para los datos de la cookie
     *
     * @return string
     */
    private function getKey()
    {
        return sha1($this->request->getHeader('User-Agent') . $this->request->getClientAddress());
    }

    /**
     * Initialize the CSRF key
     *
     * @return void
     */
    public function initialize()
    {
        if ($this->context->isLoggedIn()
            && $this->context->getCSRF() === null
        ) {
            $key = Hash::signMessage($this->getKey(), $this->configData->getPasswordSalt());

            $this->context->setCSRF($key);

            logger(sprintf('CSRF key (set): %s', $this->context->getCSRF()));
        }
    }
}