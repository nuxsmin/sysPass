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
 * Class Language para el manejo del languaje utilizado por la aplicación
 *
 * @package SP
 */
class Language
{
    /**
     * Lenguaje del usuario
     *
     * @var string
     */
    public static $userLang = '';
    /**
     * Lenguaje global de la Aplicación
     *
     * @var string
     */
    public static $globalLang = '';

    /**
     * Establecer el lenguaje a utilizar
     *
     * @param bool $force Forzar la detección del lenguaje para los inicios de sesión
     */
    public static function setLanguage($force = false)
    {
        $lang = Session::getLocale();

        if (empty($lang) || $force === true) {
            $Language = new Language();

            self::$userLang = $Language->getUserLang();
            self::$globalLang = $Language->getGlobalLang();

            $lang = (self::$userLang) ? self::$userLang : self::$globalLang;

            Session::setLocale($lang);
        }

        self::setLocales($lang);
    }

    /**
     * Devuelve el lenguaje del usuario
     *
     * @return bool
     */
    private function getUserLang()
    {
        return (Session::getUserId() > 0) ? UserPreferences::getPreferences(Session::getUserId())->getLang() : '';
    }

    /**
     * Establece el lenguaje de la aplicación.
     * Esta función establece el lenguaje según esté definido en la configuración o en el navegador.
     */
    private function getGlobalLang()
    {
        $browserLang = $this->getBrowserLang();
        $configLang = Config::getValue('sitelang');

        // Establecer a en_US si no existe la traducción o no es español
        if (!$configLang
            && !$this->checkLangFile($browserLang)
            && !preg_match('/^es_.*/i', $browserLang)
        ) {
            $lang = 'en_US';
        } else {
            $lang = ($configLang) ? $configLang : $browserLang;
        }

        return $lang;
    }

    /**
     * Devolver el lenguaje que acepta el navegador
     *
     * @return mixed
     */
    private function getBrowserLang()
    {
        return str_replace("-", "_", substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5));
    }

    /**
     * Comprobar si el archivo de lenguaje existe
     *
     * @param string $lang El lenguaje a comprobar
     * @return bool
     */
    private function checkLangFile($lang)
    {
        return file_exists(LOCALES_PATH . DIRECTORY_SEPARATOR . $lang);
    }

    /**
     * Establecer las locales de gettext
     *
     * @param string $lang El lenguaje a utilizar
     */
    private static function setLocales($lang)
    {
        $lang .= '.utf8';

        putenv("LANG=" . $lang);
        setlocale(LC_MESSAGES, $lang);
        setlocale(LC_ALL, $lang);
        bindtextdomain("messages", LOCALES_PATH);
        textdomain("messages");
        bind_textdomain_codeset("messages", 'UTF-8');
    }

    /**
     * Devolver los lenguajes disponibles
     *
     * @return array
     */
    public static function getAvailableLanguages()
    {
        return array(
            'Español' => 'es_ES',
            'English' => 'en_US',
            'Deutsch' => 'de_DE',
            'Magyar' => 'hu_HU',
            'Français' => 'fr_FR'
        );
    }
}