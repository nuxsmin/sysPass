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

namespace SP\Controller;

use SP\Core\Init;
use SP\Core\Messages\LogMessage;
use SP\Core\SessionUtil;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Util\Checks;
use SP\Util\Json;
use SP\Util\Util;

/**
 * Class RequestControllerTrait
 *
 * @package SP\Controller
 */
trait RequestControllerTrait
{
    /**
     * @var int
     */
    protected $actionId;
    /**
     * @var int|array
     */
    protected $itemId;
    /**
     * @var int
     */
    protected $activeTab;
    /**
     * @var JsonResponse
     */
    protected $JsonResponse;
    /**
     * @var string
     */
    protected $sk;
    /**
     * @var LogMessage
     */
    protected $LogMessage;

    /**
     * inicializar las propiedades
     *
     * @internal param array $checKItems Lista de elementos a analizar
     */
    protected function init()
    {
        $this->JsonResponse = new JsonResponse();

        $this->checkSession();
        $this->analyzeRequest();
        $this->preActionChecks();
    }

    /**
     * Analizar la petición HTTP y establecer las propiedades del elemento
     */
    protected function analyzeRequest()
    {
        $this->sk = Request::analyze('sk');
        $this->actionId = Request::analyze('actionId', 0);
        $this->itemId = Request::analyze('itemId', 0);
        $this->activeTab = Request::analyze('activeTab', 0);
    }

    /**
     * Comprobaciones antes de realizar una acción
     */
    protected function preActionChecks()
    {
        if (!$this->sk || !$this->actionId || !SessionUtil::checkSessionKey($this->sk)) {
            $this->invalidAction();
        }
    }

    /**
     * Acción no disponible
     */
    protected function invalidAction()
    {
        $this->JsonResponse->setDescription(__('Acción Inválida', false));
        Json::returnJson($this->JsonResponse);
    }

    /**
     * Comprobar si la sesión está activa
     */
    protected function checkSession()
    {
        if (!Init::isLoggedIn()) {
            if (Checks::isJson()) {
                $this->JsonResponse->setDescription(__('La sesión no se ha iniciado o ha caducado', false));
                $this->JsonResponse->setStatus(10);
                Json::returnJson($this->JsonResponse);
            } else {
                Util::logout();
            }
        }
    }
}