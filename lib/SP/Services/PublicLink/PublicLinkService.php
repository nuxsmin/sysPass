<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Services\PublicLink;

use SP\Bootstrap;
use SP\Config\Config;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\SPException;
use SP\Core\Session\Session;
use SP\Core\Traits\InjectableTrait;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkData;
use SP\Http\Request;
use SP\Repositories\Account\AccountRepository;
use SP\Repositories\PublicLink\PublicLinkRepository;
use SP\Services\ServiceItemTrait;
use SP\Core\Crypt\Session as CryptSession;
use SP\Util\Checks;
use SP\Util\HttpUtil;
use SP\Util\Util;

/**
 * Class PublicLinkService
 *
 * @package SP\Services\PublicLink
 */
class PublicLinkService
{
    use InjectableTrait;
    use ServiceItemTrait;

    /**
     * @var PublicLinkRepository
     */
    protected $publicLinkRepository;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Session
     */
    protected $session;

    /**
     * CategoryService constructor.
     *
     * @throws \SP\Core\Dic\ContainerException
     */
    public function __construct()
    {
        $this->injectDependencies();
    }

    /**
     * Returns an HTTP URL for given hash
     *
     * @param $hash
     * @return string
     */
    public static function getLinkForHash($hash)
    {
        return Bootstrap::$WEBURI . '/index.php?r=account/viewLink/' . $hash;
    }

    /**
     * Generar el hash para el enlace
     *
     * @return string
     */
    public static function createLinkHash()
    {
        return hash('sha256', uniqid('sysPassPublicLink', true));
    }

    /**
     * @param PublicLinkRepository $publicLinkRepository
     * @param Config               $config
     * @param Session              $session
     */
    public function inject(PublicLinkRepository $publicLinkRepository, Config $config, Session $session)
    {
        $this->publicLinkRepository = $publicLinkRepository;
        $this->config = $config;
        $this->session = $session;
    }

    /**
     * @param ItemSearchData $itemSearchData
     * @return mixed
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->publicLinkRepository->search($itemSearchData);
    }

    /**
     * @param $id
     * @return \SP\DataModel\PublicLinkData
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getById($id)
    {
        return $this->publicLinkRepository->getById($id);
    }

    /**
     * @param $id
     * @return bool
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function refresh($id)
    {
        $salt = $this->config->getConfigData()->getPasswordSalt();
        $key = self::getNewKey($salt);

        $publicLinkData = $this->publicLinkRepository->getById($id);
        $publicLinkData->setHash(self::getHashForKey($key, $salt));
        $publicLinkData->setData($this->getSecuredLinkData($publicLinkData->getItemId(), $key));
        $publicLinkData->setDateExpire(self::calcDateExpire($this->config));
        $publicLinkData->setCountViews($this->config->getConfigData()->getPublinksMaxViews());

        return $this->publicLinkRepository->refresh($publicLinkData);
    }

    /**
     * @param string $salt
     * @return string
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public static function getNewKey($salt)
    {
        return $salt . Util::generateRandomBytes();
    }

    /**
     * Returns the hash from a composed key
     *
     * @param string $key
     * @return mixed
     */
    public static function getHashForKey($key, $salt)
    {
        return str_replace($salt, '', $key);
    }

    /**
     * Obtener los datos de una cuenta y encriptarlos para el enlace
     *
     * @param int    $itemId
     * @param string $linkKey
     * @return Vault
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    protected function getSecuredLinkData($itemId, $linkKey)
    {
        // Obtener los datos de la cuenta
        $accountService = new AccountRepository();
        $accountData = $accountService->getDataForLink($itemId);

        // Desencriptar la clave de la cuenta
        $key = CryptSession::getSessionKey();
        $securedKey = Crypt::unlockSecuredKey($accountData->getKey(), $key);
        $accountData->setPass(Crypt::decrypt($accountData->getPass(), $securedKey, $key));
        $accountData->setKey(null);

        $vault = new Vault();
        return serialize($vault->saveData(serialize($accountData), $linkKey));
    }

    /**
     * Devolver el tiempo de caducidad del enlace
     *
     * @param Config $config
     * @return int
     */
    public static function calcDateExpire(Config $config)
    {
        return time() + $config->getConfigData()->getPublinksMaxTime();
    }

    /**
     * @param $id
     * @return $this
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        if ($this->publicLinkRepository->delete($id) === 0) {
            throw new SPException(SPException::SP_INFO, __u('Enlace no encontrado'));
        }

        return $this;
    }

    /**
     * @param PublicLinkData $itemData
     * @return int
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create(PublicLinkData $itemData)
    {
        $itemData->setData($this->getSecuredLinkData($itemData->getItemId(), self::getKeyForHash($this->config->getConfigData()->getPasswordSalt(), $itemData)));
        $itemData->setDateExpire(self::calcDateExpire($this->config));
        $itemData->setMaxCountViews($this->config->getConfigData()->getPublinksMaxViews());
        $itemData->setUserId($this->session->getUserData()->getId());

        return $this->publicLinkRepository->create($itemData);
    }

    /**
     * @param string         $salt
     * @param PublicLinkData $publicLinkData
     * @return string
     */
    public static function getKeyForHash($salt, PublicLinkData $publicLinkData)
    {
        return $salt . $publicLinkData->getHash();
    }

    /**
     * Get all items from the service's repository
     *
     * @return array
     */
    public function getAllBasic()
    {
        return $this->publicLinkRepository->getAll();
    }

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @param PublicLinkData $publicLinkData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function addLinkView(PublicLinkData $publicLinkData)
    {
        /** @var array $useInfo */
        $useInfo = serialize($publicLinkData->getUseInfo());
        $useInfo[] = self::getUseInfo($publicLinkData->getHash());
        $publicLinkData->setUseInfo($useInfo);

        // FIXME
//        $Log = new Log();
//        $LogMessage = $Log->getLogMessage();
//        $LogMessage->setAction(__u('Ver Enlace Público'));
//        $LogMessage->addDescription(__u('Enlace visualizado'));
//        $LogMessage->addDetails(__u('Tipo'), $publicLinkData->getPublicLinkTypeId());
//        $LogMessage->addDetails(__u('Cuenta'), AccountUtil::getAccountNameById($publicLinkData->getPublicLinkItemId()));
//        $LogMessage->addDetails(__u('Usuario'), UserUtil::getUserLoginById($publicLinkData->getPublicLinkUserId()));
//        $Log->writeLog();
//
//        if ($publicLinkData->isPublicLinkNotify()) {
//            Email::sendEmail($LogMessage);
//        }

        return $this->publicLinkRepository->addLinkView($publicLinkData);
    }

    /**
     * Actualizar la información de uso
     *
     * @param $hash
     * @return array
     */
    public static function getUseInfo($hash)
    {
        return [
            'who' => HttpUtil::getClientAddress(true),
            'time' => time(),
            'hash' => $hash,
            'agent' => Request::getRequestHeaders('HTTP_USER_AGENT'),
            'https' => Checks::httpsEnabled()
        ];
    }

    /**
     * @param $hash string
     * @return bool|PublicLinkData
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getByHash($hash)
    {
        return $this->publicLinkRepository->getByHash($hash);
    }

    /**
     * Devolver el hash asociado a un elemento
     *
     * @param int $itemId
     * @return PublicLinkData
     * @throws SPException
     */
    public function getHashForItem($itemId)
    {
        return $this->publicLinkRepository->getHashForItem($itemId);
    }
}