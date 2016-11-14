<?php

namespace Auth\Ldap;

use SP\Config\Config;
use SP\Log\Log;

abstract class LdapBase implements LdapInterface
{
    /**
     * @var resource
     */
    protected $ldapHandler;
    /**
     * @var string
     */
    protected $server;
    /**
     * @var string
     */
    protected $searchBase;
    /**
     * @var string
     */
    protected $bindDn;
    /**
     * @var string
     */
    protected $bindPass;
    /**
     * @var string
     */
    protected $group;
    /**
     * @var array
     */
    protected $searchData;

    /**
     * Comprobar si los parámetros necesario de LDAP están establecidos.
     *
     * @return bool
     */
    public function checkParams()
    {
//        self::$isADS = Config::getConfig()->isLdapAds();
        $this->searchBase = Config::getConfig()->getLdapBase();
//        $this->server = (!self::$isADS) ? Config::getConfig()->getLdapServer() : LdapADS::getADServer(Config::getConfig()->getLdapServer());
        $this->server = $this->pickServer();
        $this->bindDn = Config::getConfig()->getLdapBindUser();
        $this->bindPass = Config::getConfig()->getLdapBindPass();
        $this->group = Config::getConfig()->getLdapGroup();

        if (!$this->searchBase || !$this->server || !$this->bindDn || !$this->bindPass) {
            Log::writeNewLog(__FUNCTION__, _('Los parámetros de LDAP no están configurados'));

            return false;
        }

        return true;
    }

    /**
     * Obtener el RDN del grupo.
     *
     * @throws \Exception
     * @return string con el RDN del grupo
     */
    protected function searchGroupDN()
    {
        $Log = new Log(__FUNCTION__);
        $filter = $this->searchGroupDN() ?: $this->group;
        $filter = '(cn=' . $filter . ')';
        $filterAttr = ["dn", "cn"];

        $searchRes = @ldap_search($this->ldapHandler, $this->searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar RDN de grupo'));
            $Log->addDetails(_('Grupo'), $filter);
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error($this->ldapHandler), ldap_errno($this->ldapHandler)));
            $Log->addDetails('LDAP FILTER', $filter);
            $Log->writeLog();

            throw new \Exception(_('Error al buscar RDN de grupo'));
        }

        if (@ldap_count_entries($this->ldapHandler, $searchRes) === 1) {
            $ldapSearchData = @ldap_get_entries($this->ldapHandler, $searchRes);

            if (!$ldapSearchData) {
                $Log->setLogLevel(Log::ERROR);
                $Log->addDescription(_('Error al buscar RDN de grupo'));
                $Log->addDetails(_('Grupo'), $filter);
                $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error($this->ldapHandler), ldap_errno($this->ldapHandler)));
                $Log->writeLog();

                throw new \Exception(_('Error al buscar RDN de grupo'));
            }

            return $ldapSearchData[0]["dn"];
        } else {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar RDN de grupo'));
            $Log->addDetails(_('Grupo'), $filter);
            $Log->addDetails('LDAP FILTER', $filter);
            $Log->writeLog();

            throw new \Exception(_('Error al buscar RDN de grupo'));
        }
    }

    /**
     * Obtener el nombre del grupo a partir del CN
     *
     * @return bool
     */
    protected function getGroupName()
    {
        if (isset($this->group) && preg_match('/^cn=([\w\s-]+),.*/i', $this->group, $groupName)) {
            return $groupName[1];
        }

        return false;
    }

    /**
     * Escapar carácteres especiales en el RDN de LDAP.
     *
     * @param string $dn con el RDN del usuario
     * @return string
     */
    protected function escapeLdapDN($dn)
    {
        $chars = array('/(,)(?!uid|cn|ou|dc)/i', '/(?<!uid|cn|ou|dc)(=)/i', '/(")/', '/(;)/', '/(>)/', '/(<)/', '/(\+)/', '/(#)/', '/\G(\s)/', '/(\s)(?=\s*$)/', '/(\/)/');
        return preg_replace($chars, '\\\$1', $dn);
    }

    /**
     * Realizar la desconexión del servidor de LDAP.
     */
    public function unbind()
    {
        @ldap_unbind($this->ldapHandler);
    }

    /**
     * Obtener el filtro para buscar el usuario
     *
     * @param string $userLogin Login del usuario
     * @return mixed
     */
    protected abstract function getUserDnFilter($userLogin);

    /**
     * Obtener el RDN del usuario que realiza el login.
     *
     * @param string $userLogin Login del usuario
     * @return array
     * @throws \Exception
     */
    public function getUserDN($userLogin)
    {
        $Log = new Log(__FUNCTION__);

//        if (self::$isADS === true) {
//            $filter = '(&(|(samaccountname=' . $userLogin . ')(cn=' . $userLogin . ')(uid=' . $userLogin . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))(objectCategory=person))';
//        } else {
//            $filter = '(&(|(samaccountname=' . $userLogin . ')(cn=' . $userLogin . ')(uid=' . $userLogin . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject)))';
//        }

        $filterAttr = array("dn", "displayname", "samaccountname", "mail", "memberof", "lockouttime", "fullname", "groupmembership", "mail");

        $searchRes = @ldap_search($this->ldapHandler, $this->searchBase, $this->getUserDnFilter($userLogin), $filterAttr);

        if (!$searchRes) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar el DN del usuario'));
            $Log->addDetails(_('Usuario'), $userLogin);
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error($this->ldapHandler), ldap_errno($this->ldapHandler)));
            $Log->addDetails('LDAP FILTER', $this->getUserDnFilter());
            $Log->writeLog();

            throw new \Exception(_('Error al buscar el DN del usuario'));
        }

        if (@ldap_count_entries($this->ldapHandler, $searchRes) === 1) {
            $ldapSearchData = @ldap_get_entries($this->ldapHandler, $searchRes);

            if (!$ldapSearchData) {
                $Log->setLogLevel(Log::ERROR);
                $Log->addDescription(_('Error al localizar el usuario en LDAP'));
                $Log->addDetails(_('Usuario'), $userLogin);
                $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error($this->ldapHandler), ldap_errno($this->ldapHandler)));
                $Log->writeLog();

                throw new \Exception(_('Error al localizar el usuario en LDAP'));
            }
        } else {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar el DN del usuario'));
            $Log->addDetails(_('Usuario'), $userLogin);
            $Log->addDetails('LDAP FILTER', $this->getUserDnFilter());
            $Log->writeLog();

            throw new \Exception(_('Error al buscar el DN del usuario'));
        }

        return $ldapSearchData;
    }

    /**
     * Obtener el servidor de LDAP a utilizar
     *
     * @return mixed
     */
    protected abstract function pickServer();

    /**
     * Realizar una búsqueda de objetos en la ruta indicada.
     *
     * @throws \Exception
     * @return int El número de resultados
     */
    protected function searchBase()
    {
        $Log = new Log(__FUNCTION__);

        $groupDN = (!empty($this->group)) ? $this->searchGroupDN() : '*';
        $filter = '(&(|(memberOf=' . $groupDN . ')(groupMembership=' . $groupDN . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject)))';
        $filterAttr = ["dn"];

        $searchRes = @ldap_search($this->ldapHandler, $this->searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar objetos en DN base'));
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error($this->ldapHandler), ldap_errno($this->ldapHandler)));
            $Log->addDetails('LDAP FILTER', $filter);
            $Log->writeLog();

            throw new \Exception(_('Error al buscar objetos en DN base'));
        }

        return @ldap_count_entries($this->ldapHandler, $searchRes);
    }
}