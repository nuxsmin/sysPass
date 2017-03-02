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

namespace Plugins\Authenticator;

use SP\Controller\ItemControllerInterface;
use SP\Controller\RequestControllerTrait;
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
            $this->JsonResponse->setDescription($e->getMessage());
            Json::returnJson($this->JsonResponse);
        }
    }

    /**
     * Guardar los datos del plugin
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \InvalidArgumentException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    protected function save()
    {
        $pin = Request::analyze('security_pin');
        $twofa_enabled = Request::analyze('security_2faenabled', 0, false, 1);

        $AuthenticatorData = Session::getUserData();

        $twoFa = new Authenticator($this->itemId, CoreSession::getUserData()->getUserLogin(), $AuthenticatorData->getIV());

        if (!$twoFa->verifyKey($pin)) {
            $this->JsonResponse->setDescription(_t('authenticator', 'Código incorrecto'));
            Json::returnJson($this->JsonResponse);
        }

        if (Checks::demoIsEnabled()) {
            $this->JsonResponse->setDescription(_t('authenticator', 'Ey, esto es una DEMO!!'));
            Json::returnJson($this->JsonResponse);
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

        $this->JsonResponse->setStatus(0);
        $this->JsonResponse->setDescription(_t('authenticator', 'Preferencias actualizadas'));

        Json::returnJson($this->JsonResponse);
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
        $pin = Request::analyze('security_pin');

        // Buscar al usuario en los datos del plugin
        /** @var AuthenticatorData $AuthenticatorData */
        $AuthenticatorData = ArrayUtil::searchInObject($this->Plugin->getData(), 'userId', $userId, new AuthenticatorData());

        $TwoFa = new Authenticator($userId, CoreSession::getUserData()->getUserLogin(), $AuthenticatorData->getIV());

        if ($userId
            && $pin
            && $TwoFa->verifyKey($pin)
        ) {
            Session::setTwoFApass(true);
            CoreSession::setAuthCompleted(true);

            $this->JsonResponse->setDescription(_t('authenticator', 'Código correcto'));
            $this->JsonResponse->setStatus(0);
        } else {
            Session::setTwoFApass(false);
            CoreSession::setAuthCompleted(false);

            $this->JsonResponse->setDescription(_t('authenticator', 'Código incorrecto'));
        }

        Json::returnJson($this->JsonResponse);
    }
}