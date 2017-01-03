<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016, Rubén Domínguez nuxsmin@$syspass.org
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

namespace Plugins\Authenticator;

use SP\Controller\ItemControllerInterface;
use SP\Controller\RequestControllerTrait;
use SP\Core\Exceptions\SPException;
use SP\Core\Plugin\PluginUtil;
use SP\Core\Session;
use SP\DataModel\PluginData;
use SP\Http\Request;
use SP\Mgmt\Plugins\Plugin;
use SP\Util\Json;

/**
 * Class ActionController
 *
 * @package Plugins\Authenticator
 */
class ActionController implements ItemControllerInterface
{
    const ACTION_TWOFA_SAVE = 1;
    const ACTION_TWOFA_CHECK = 1;

    use RequestControllerTrait;

    /**
     * @var AuthenticatorPlugin
     */
    protected $Plugin;

    public function __construct()
    {
        $this->Plugin = PluginUtil::getPluginData('Authenticator');

        $this->init();
    }

    /**
     * Guardar los datos del plugin
     */
    protected function save()
    {
        $pin = Request::analyze('security_pin', 0);

        $twoFa = new Authenticator($this->itemId, Session::getUserData()->getUserLogin());

        if (!$twoFa->verifyKey($pin)) {
            $this->jsonResponse->setDescription(_('Código incorrecto'));
            Json::returnJson($this->jsonResponse);
        }

        try {
            $data = $this->Plugin->getData();

            if (!isset($data[$this->itemId])) {
                $data[$this->itemId] = new AuthenticatorData();
            }

            /** @var AuthenticatorData $AuthenticatorData */
            $AuthenticatorData = $data[$this->itemId];
            $AuthenticatorData->setUserId($this->itemId);
            $AuthenticatorData->setTwofaEnabled(Request::analyze('security_2faenabled', 0, false, 1));

            $PluginData = new PluginData();
            $PluginData->setPluginName($this->Plugin->getName());
            $PluginData->setPluginEnabled(1);
            $PluginData->setPluginData(serialize($data));

            Plugin::getItem($PluginData)->update();

            $this->jsonResponse->setStatus(0);
            $this->jsonResponse->setDescription(_('Preferencias actualizadas'));
        } catch (SPException $e) {
            $this->jsonResponse->setDescription($e->getMessage());
        }

        Json::returnJson($this->jsonResponse);
    }

    /**
     * Realizar la acción solicitada en la la petición HTTP
     */
    public function doAction()
    {
        switch ($this->actionId) {
            case ActionController::ACTION_TWOFA_SAVE:
                $this->save();
                break;
            case ActionController::ACTION_TWOFA_CHECK:
                break;
            default:
        }
    }
}