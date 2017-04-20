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

namespace SP\Core;

use SP\Config\Config;
use SP\Http\Request;
use SP\Mgmt\Users\UserPreferences;

defined('APP_ROOT') || die();

/**
 * Class Language para el manejo del lenguaje utilizado por la aplicación
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
     * Estado de la localización. false si no existe
     *
     * @var string|false
     */
    public static $localeStatus;
    /**
     * Si se ha establecido a las de la App
     *
     * @var bool
     */
    protected static $appSet = false;

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

            $lang = self::$userLang ?: self::$globalLang;

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
        return (Session::getUserData()->getUserId() > 0) ? Session::getUserPreferences()->getLang() : '';
    }

    /**
     * Establece el lenguaje de la aplicación.
     * Esta función establece el lenguaje según esté definido en la configuración o en el navegador.
     */
    private function getGlobalLang()
    {
        $browserLang = $this->getBrowserLang();
        $configLang = Config::getConfig()->getSiteLang();

        // Establecer a en_US si no existe la traducción o no es español
        if (!$configLang
            && !$this->checkLangFile($browserLang)
            && strpos($browserLang, 'es_') === false
        ) {
            $lang = 'en_US';
        } else {
            $lang = $configLang ?: $browserLang;
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
        $lang = Request::getRequestHeaders('HTTP_ACCEPT_LANGUAGE');

        if ($lang) {
            return str_replace('-', '_', substr($lang, 0, 5));
        } else {
            return '';
        }
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
    public static function setLocales($lang)
    {
        $lang .= '.utf8';
        $fallback = 'en_US.utf8';

        putenv('LANG=' . $lang);
        self::$localeStatus = setlocale(LC_MESSAGES, [$lang, $fallback]);
        setlocale(LC_ALL, [$lang, $fallback]);
        bindtextdomain('messages', LOCALES_PATH);
        textdomain('messages');
        bind_textdomain_codeset('messages', 'UTF-8');
    }

    /**
     * Devolver los lenguajes disponibles
     *
     * @return array
     */
    public static function getAvailableLanguages()
    {
        return [
            'Español' => 'es_ES',
            'Catalá' => 'ca_ES',
            'English' => 'en_US',
            'Deutsch' => 'de_DE',
            'Magyar' => 'hu_HU',
            'Français' => 'fr_FR',
            'Polski' => 'po_PO',
            'русский' => 'ru_RU',
            'Nederlands' => 'nl_NL'
        ];
    }

    /**
     * Establecer el lenguaje global para las traducciones
     */
    public static function setAppLocales()
    {
        if (Config::getConfig()->getSiteLang() !== Session::getLocale()) {
            self::setLocales(Config::getConfig()->getSiteLang());
            self::$appSet = true;
        }
    }

    /**
     * Restablecer el lenguaje global para las traducciones
     */
    public static function unsetAppLocales()
    {
        if (self::$appSet === true) {
            self::setLocales(Session::getLocale());
            self::$appSet = false;
        }
    }
}