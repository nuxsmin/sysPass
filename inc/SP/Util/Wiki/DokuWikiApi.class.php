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

namespace SP\Util\Wiki;

use SP\Config\Config;
use SP\Core\Exceptions\SPException;
use SP\Core\Session;
use SP\Log\Log;
use SP\Log\LogLevel;

defined('APP_ROOT') || die();

/**
 * Class DokuWikiApi para realizar consultas a la API de DokuWiki
 *
 * @package SP\Util\Wiki
 */
class DokuWikiApi extends DokuWikiApiBase
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * Constructor
     *
     * @param string $url La URL de conexión
     * @param string $user El usuario de conexión
     * @param string $pass La clave de conexión
     * @throws \SP\Core\Exceptions\SPException
     */
    public function __construct($url = null, $user = null, $pass = null)
    {
        try {
            $this->setConnectionData($url, $user, $pass);

            if (!empty($this->apiUser) && Session::getDokuWikiSession() === false) {
                $Res = new DokuWikiApiParse($this->doLogin());
                $this->catchError($Res);
                $resLogin = $Res->parseParams();

                Session::setDokuWikiSession($resLogin[0]);

                if ($resLogin[0] === false) {
                    throw new SPException(SPException::SP_WARNING, __('Fallo de autentificación', false));
                }
            }

            $this->namespace = Config::getConfig()->getDokuwikiNamespace();
        } catch (SPException $e) {
            $this->logException($e);
            throw $e;
        } catch (\InvalidArgumentException $e) {
            Log::writeNewLog('DokuWiki API', $e->getMessage(), LogLevel::ERROR);
            throw new SPException(SPException::SP_WARNING, $e->getMessage());
        }
    }

    /**
     * Comprobar la conexión a DokuWiki
     *
     * @param string $url La URL de conexión
     * @param string $user El usuario de conexión
     * @param string $pass La clave de conexión
     * @return DokuWikiApi
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function checkConnection($url = null, $user = null, $pass = null)
    {
        try {
            // Reinicializar la cookie de DokuWiki
            Session::setDokuWikiSession(false);

            return new DokuWikiApi($url, $user, $pass);
        } catch (SPException $e) {
            throw $e;
        }
    }

    /**
     * Obtener el listado de páginas de la Wiki
     *
     * @return bool
     */
    public function getPageList()
    {
        try {
            $this->createMsg('dokuwiki.getPagelist');
            $this->addParam($this->namespace);
            $this->addParam(['depth' => 0]);
            $Res = new DokuWikiApiParse($this->callWiki());
            $this->catchError($Res);

            return $Res->parseParams();
        } catch (SPException $e) {
            $this->logException($e, __FUNCTION__);
            return false;
        }
    }

    /**
     * Realizar una búsqueda en la Wiki
     *
     * @param string $search El texto a buscar
     * @return array|bool
     */
    public function getSearch($search)
    {
        try {
            $this->createMsg('dokuwiki.search');
            $this->addParam($search);
            $Res = new DokuWikiApiParse($this->callWiki());
            $this->catchError($Res);

            return $Res->parseParams();
        } catch (SPException $e) {
            $this->logException($e, __FUNCTION__);
            return false;
        }
    }

    /**
     * Obtener una página de la Wiki
     *
     * @param string $page El nombre de la página a obtener
     * @return array|bool
     */
    public function getPage($page)
    {
        if (!empty($this->namespace)) {
            $page = $this->namespace . ':' . $page;
        }

        try {
            $this->createMsg('wiki.getPageHTML');
            $this->addParam($page);
            $Res = new DokuWikiApiParse($this->callWiki());
            $this->catchError($Res);

            return $Res->parseParams();
        } catch (SPException $e) {
            $this->logException($e, __FUNCTION__);
            return false;
        }
    }

    /**
     * Obtener una página de la Wiki en formato original
     *
     * @param string $page El nombre de la página a obtener
     * @return array|bool
     */
    public function getRawPage($page)
    {
        try {
            $this->createMsg('wiki.getPage');
            $this->addParam($page);
            $Res = new DokuWikiApiParse($this->callWiki());
            $this->catchError($Res);

            return $Res->parseParams();
        } catch (SPException $e) {
            $this->logException($e, __FUNCTION__);
            return false;
        }
    }

    /**
     * Obtener la información de una página de la Wiki
     *
     * @param string $page El nombre de la página a obtener
     * @return array|bool
     */
    public function getPageInfo($page)
    {
        if (!empty($this->namespace)) {
            $page = $this->namespace . ':' . $page;
        }

        try {
            $this->createMsg('wiki.getPageInfo');
            $this->addParam($page);
            $Res = new DokuWikiApiParse($this->callWiki());
            $this->catchError($Res);

            return $Res->parseParams();
        } catch (SPException $e) {
            $this->logException($e, __FUNCTION__);
            return false;
        }
    }

    /**
     * Obtener la versión de DokuWiki
     *
     * @return array|bool
     */
    public function getVersion()
    {
        try {
            $this->createMsg('dokuwiki.getVersion');
            $Res = new DokuWikiApiParse($this->callWiki());
            $this->catchError($Res);

            return $Res->parseParams();
        } catch (SPException $e) {
            $this->logException($e, __FUNCTION__);
            return false;
        }
    }

    /**
     * Obtener el nombre de la Wiki
     *
     * @return array|bool
     */
    public function getTitle()
    {
        try {
            $this->createMsg('dokuwiki.getTitle');
            $Res = new DokuWikiApiParse($this->callWiki());
            $this->catchError($Res);

            return $Res->parseParams();
        } catch (SPException $e) {
            $this->logException($e, __FUNCTION__);
            return false;
        }
    }

    /**
     * Obtener los permisos de la página
     *
     * @param $page
     * @return array|bool
     */
    public function getAcl($page)
    {
        try {
            $this->createMsg('wiki.aclCheck');
            $this->addParam($page);
            $Res = new DokuWikiApiParse($this->callWiki());
            $this->catchError($Res);

            return $Res->parseParams();
        } catch (SPException $e) {
            $this->logException($e, __FUNCTION__);
            return false;
        }
    }
}