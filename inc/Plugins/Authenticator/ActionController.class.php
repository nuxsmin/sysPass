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
use SP\Core\ActionsInterface;
use SP\Core\Plugin\PluginDataStore;
use SP\Core\Session as CoreSession;
use SP\DataModel\PluginData;
use SP\Http\Request;
use SP\Mgmt\Plugins\Plugin;
use SP\Util\ArrayUtil;
use SP\Util\Checks;
use SP\Util\Json;

/**
 * Class ActionController
 *
 * @package Plugins\Authenticator
 */
class ActionController implements ItemControllerInterface
{
    const ACTION_TWOFA_SAVE = 1;
    const ACTION_TWOFA_CHECKCODE = 2;

    use RequestControllerTrait;

    /**
     * @var AuthenticatorPlugin
     */
    protected $Plugin;

    /**
     * ActionController constructor.
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function __construct()
    {
        $this->Plugin = new AuthenticatorPlugin();

        PluginDataStore::load($this->Plugin);

        $this->init();
    }

    /**
     * Realizar la acción solicitada en la la petición HTTP
     *
     * @throws \InvalidArgumentException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function doAction()
    {
        try {
            switch ($this->actionId) {
                case ActionController::ACTION_TWOFA_SAVE:
                    $this->save();
                    break;
                case ActionController::ACTION_TWOFA_CHECKCODE:
                    $this->checkCode();
                    break;
                default:
                    $this->invalidAction();
            }
        } catch (\Exception $e) {
            $this->jsonResponse->setDescription($e->getMessage());
            Json::returnJson($this->jsonResponse);
        }
    }

    /**
     * Guardar los datos del plugin
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \InvalidArgumentException
     */
    protected function save()
    {
        $pin = Request::analyze('security_pin', 0);
        $twofa_enabled = Request::analyze('security_2faenabled', 0, false, 1);

        $AuthenticatorData = Session::getUserData();

        $twoFa = new Authenticator($this->itemId, CoreSession::getUserData()->getUserLogin(), $AuthenticatorData->getIV());

        if (!$twoFa->verifyKey($pin)) {
            $this->jsonResponse->setDescription(_('Código incorrecto'));
            Json::returnJson($this->jsonResponse);
        }

        if (Checks::demoIsEnabled()) {
            $this->jsonResponse->setDescription(_('Ey, esto es una DEMO!!'));
            Json::returnJson($this->jsonResponse);
        }

        $data = $this->Plugin->getData();

        if ($twofa_enabled) {
            /** @var AuthenticatorData $AuthenticatorData */
            $AuthenticatorData->setUserId($this->itemId);
            $AuthenticatorData->setTwofaEnabled($twofa_enabled);
            $AuthenticatorData->setExpireDays(Request::analyze('expiredays', 0));
            $AuthenticatorData->setDate(time());

            $data[$this->itemId] = $AuthenticatorData;
        } elseif (!$twofa_enabled) {
            unset($data[$this->itemId]);
        }

        $PluginData = new PluginData();
        $PluginData->setPluginName($this->Plugin->getName());
        $PluginData->setPluginEnabled(1);
        $PluginData->setPluginData(serialize($data));

        Plugin::getItem($PluginData)->update();

        $this->jsonResponse->setStatus(0);
        $this->jsonResponse->setDescription(_('Preferencias actualizadas'));

        Json::returnJson($this->jsonResponse);
    }

    /**
     * Comprobar el código 2FA
     *
     * @throws \InvalidArgumentException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function checkCode()
    {
        $userId = Request::analyze('itemId', 0);
        $pin = Request::analyze('security_pin', 0);

        // Buscar al usuario en los datos del plugin
        /** @var AuthenticatorData $AuthenticatorData */
        $AuthenticatorData = ArrayUtil::searchInObject($this->Plugin->getData(), 'userId', $userId, new AuthenticatorData());

        $TwoFa = new Authenticator($userId, null, $AuthenticatorData->getIV());

        if ($userId
            && $pin
            && $TwoFa->verifyKey($pin)
        ) {
            Session::setTwoFApass(true);
            CoreSession::setAuthCompleted(true);

            $this->jsonResponse->setDescription(_('Código correcto'));
            $this->jsonResponse->setStatus(0);
        } else {
            Session::setTwoFApass(false);
            CoreSession::setAuthCompleted(false);

            $this->jsonResponse->setDescription(_('Código incorrecto'));
        }

        Json::returnJson($this->jsonResponse);
    }
}