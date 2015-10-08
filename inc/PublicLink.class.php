<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class PublicLink para la creación de enlaces públicos
 *
 * @package SP
 */
class PublicLink extends PublicLinkBase
{
    /**
     * Tipos de enlaces
     */
    const TYPE_ACCOUNT = 1;

    /**
     * Obtener los datos de un enlace mediante el Id
     *
     * @param int $linkId El Id del enlace
     * @return bool|PublicLink
     */
    public static function getLinkById($linkId)
    {
        $query = 'SELECT publicLink_id, publicLink_hash, publicLink_linkData ' .
            'FROM publicLinks ' .
            'WHERE publicLink_id = :id LIMIT 1';

        $data['id'] = $linkId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        /**
         * @var $PublicLink PublicLink
         */
        $PublicLink = unserialize($queryRes->publicLink_linkData);
        $PublicLink->setId($queryRes->publicLink_id);

        return $PublicLink;
    }

    /**
     * Obtener los datos de un enlace mediante el Hash
     *
     * @param int $linkHash El Id del enlace
     * @return bool|PublicLink
     */
    public static function getLinkByHash($linkHash)
    {
        $query = 'SELECT publicLink_id, publicLink_hash, publicLink_linkData ' .
            'FROM publicLinks ' .
            'WHERE publicLink_hash = :hash LIMIT 1';

        $data['hash'] = $linkHash;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false || DB::$lastNumRows === 0) {
            return false;
        }

        /**
         * @var $PublicLink PublicLink
         */
        $PublicLink = unserialize($queryRes->publicLink_linkData);
        $PublicLink->setId($queryRes->publicLink_id);

        return $PublicLink;
    }

    /**
     * Inicializar y crear un nuevo enlace
     *
     * @return bool
     * @throws SPException
     * @throws \Exception
     */
    public function newLink()
    {
        if ($this->checkLinkByItemId()) {
            throw new SPException(SPException::SP_WARNING, _('Enlace ya creado'));
        }

        $this->_dateAdd = time();
        $this->_userId = Session::getUserId();
        $this->_maxCountViews = Config::getValue('publinks_maxviews', 3);

        try {
            $this->calcDateExpire();
            $this->createLinkHash();
            $this->createLinkPass();
            $this->createLink();
        } catch (SPException $e) {
            throw $e;
        }

        $Log = new Log(_('Nuevo Enlace'));
        $Log->addDescription(sprintf('%s : %s', Html::strongText(_('Tipo')), $this->_typeId));
        $Log->addDescription(sprintf('%s : %s', Html::strongText(_('Cuenta')), $this->_itemId));
        $Log->addDescription(sprintf('%s : %s', Html::strongText(_('Usuario')), UserUtil::getUserLoginById($this->_userId)));
        $Log->writeLog();

        Email::sendEmail($Log);

        return true;
    }

    /**
     * Comprobar si un enlace existe para un elemento
     *
     * @return bool
     */
    public function checkLinkByItemId()
    {
        $query = 'SELECT publicLink_id FROM publicLinks WHERE publicLink_itemId = :itemid LIMIT 1';

        $data['itemid'] = $this->_itemId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return (DB::$lastNumRows === 1);
    }

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @return bool
     */
    public function addLinkView()
    {
        $this->_countViews++;
        $this->updateUseInfo($_SERVER['REMOTE_ADDR']);

        $Log = new Log(_('Ver Enlace Público'));
        $Log->addDescription(sprintf('%s : %s', Html::strongText(_('Tipo')), $this->_typeId));
        $Log->addDescription(sprintf('%s : %s', Html::strongText(_('Cuenta')), AccountUtil::getAccountNameById($this->_itemId)));
        $Log->addDescription(sprintf('%s : %s', Html::strongText(_('Usuario')), UserUtil::getUserLoginById($this->_userId)));
        $Log->writeLog();

        if ($this->isNotify()) {
            Email::sendEmail($Log);
        }

        return $this->updateLink();
    }

    /**
     * Renovar un enlace
     *
     * @return bool
     * @throws SPException
     * @throws \Exception
     */
    public function refreshLink()
    {
        $this->_maxCountViews += Config::getValue('publinks_maxviews', 3);

        try {
            $this->calcDateExpire();
            $this->createLinkHash(true);
            $this->createLinkPass();
            $this->updateLink();
        } catch (SPException $e) {
            throw $e;
        }

        $Log = new Log(_('Actualizar Enlace'));
        $Log->addDescription(sprintf('%s : %s', Html::strongText(_('Tipo')), $this->_typeId));
        $Log->addDescription(sprintf('%s : %s', Html::strongText(_('Cuenta')), AccountUtil::getAccountNameById($this->_itemId)));
        $Log->addDescription(sprintf('%s : %s', Html::strongText(_('Usuario')), UserUtil::getUserLoginById($this->_userId)));
        $Log->writeLog();

        Email::sendEmail($Log);

        return true;
    }
}