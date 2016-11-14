<?php

namespace Auth\Ldap;

interface LdapInterface
{
    /**
     * Realizar la autentificación con el servidor de LDAP.
     *
     * @return void
     */
    public function bind();

    /**
     * Comprobar la conexión al servidor de LDAP.
     *
     * @return bool
     */
    public function checkConnection();

    /**
     * Realizar la conexión al servidor de LDAP.
     *
     * @return void
     */
    public function connect();

    /**
     * Comprobar si los parámetros necesarios de LDAP están establecidos.
     *
     * @return bool
     */
    public function checkParams();

    /**
     * Obtener el RDN del usuario que realiza el login.
     *
     * @param string $userLogin Login del usuario
     * @return string
     */
    public function getUserDN($userLogin);

    public function unbind();

    /**
     * Obtener los atributos del usuario.
     *
     * @return array
     */
    public function getAttributes();

    /**
     * Buscar al usuario en un grupo.
     *
     * @return bool
     */
    public function searchUsrInGroup();
}