<?php
/**
 * Created by PhpStorm.
 * User: rdb
 * Date: 15/11/16
 * Time: 8:22
 */

namespace SP\Auth;

use SP\DataModel\UserData;

/**
 * Interface AuthInterface
 * @package Auth
 */
interface AuthInterface
{
    /**
     * Autentificar al usuario
     *
     * @param UserData $UserData Datos del usuario
     * @return bool
     */
    public function authenticate(UserData $UserData);
}