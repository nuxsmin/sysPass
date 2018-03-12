<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\ApiService;

use SP\Services\Auth\AuthException;
use SP\Services\AuthToken\AuthTokenService;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\Track\TrackService;

/**
 * Class ApiService
 * @package SP\Services\ApiService
 */
class ApiService extends Service
{
    /**
     * @var AuthTokenService
     */
    protected $authTokenService;
    /**
     * @var TrackService
     */
    protected $trackService;

    /**
     * @param $actionId
     * @param $authToken
     * @throws ServiceException
     * @throws AuthException
     */
    public function authenticate($actionId, $authToken)
    {
        if (($authToken = $this->authTokenService->getTokenByToken($actionId, $authToken)) === false) {
            $this->addTracking();

            throw new ServiceException(__u('Acceso no permitido'));
        }


//        $this->data = $data;
//
//        $this->userId = $this->ApiTokenData->getUserId();
//
//        $this->loadUserData();
//
//        if ($this->passIsNeeded()) {
//            $this->doAuth();
//        }
//
//        SessionFactory::setSessionType(SessionFactory::SESSION_API);
//
//        $this->Log = new Log();
    }

    /**
     * Añadir un seguimiento
     *
     * @throws AuthException
     */
    protected function addTracking()
    {
        try {
            $this->trackService->add(TrackService::getTrackRequest('api'));
        } catch (\Exception $e) {
            throw new AuthException(
                __u('Error interno'),
                AuthException::ERROR,
                null,
                -32601
            );
        }
    }

    protected function initialize()
    {
        $this->authTokenService = $this->dic->get(AuthTokenService::class);
        $this->trackService = $this->dic->get(TrackService::class);
    }
}