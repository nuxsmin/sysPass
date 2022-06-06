<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Core;

use SP\Core\Context\ContextInterface;
use SP\Domain\Config\ConfigInterface;
use SP\Domain\Config\In\ConfigDataInterface;
use SP\Http\Request;
use SP\Http\RequestInterface;

defined('APP_ROOT') || die();

/**
 * Class Language para el manejo del lenguaje utilizado por la aplicación
 *
 * @package SP
 */
final class Language implements LanguageInterface
{
    /**
     * Lenguaje del usuario
     */
    public static string $userLang = '';
    /**
     * Lenguaje global de la Aplicación
     */
    public static string $globalLang = '';
    /**
     * Estado de la localización. false si no existe
     *
     * @var string|false
     */
    public static $localeStatus;
    /**
     * Si se ha establecido a las de la App
     */
    protected static bool $appSet = false;
    /**
     *  Available languages
     */
    private static array          $langs = [
        'es_ES' => 'Español',
        'ca_ES' => 'Catalá',
        'en_US' => 'English',
        'de_DE' => 'Deutsch',
        'hu_HU' => 'Magyar',
        'fr_FR' => 'Français',
        'pl_PL' => 'Polski',
        'ru_RU' => 'русский',
        'nl_NL' => 'Nederlands',
        'pt_BR' => 'Português',
        'it_IT' => 'Italiano',
        'da'    => 'Dansk',
        'fo'    => 'Føroyskt mál',
        'ja_JP' => '日本語',
    ];
    protected ConfigDataInterface $configData;
    protected ContextInterface    $context;
    private Request               $request;

    /**
     * Language constructor.
     */
    public function __construct(ContextInterface $session, ConfigInterface $config, RequestInterface $request)
    {
        $this->context = $session;
        $this->configData = $config->getConfigData();
        $this->request = $request;

        ksort(self::$langs);
    }

    /**
     * Devolver los lenguajes disponibles
     */
    public static function getAvailableLanguages(): array
    {
        return self::$langs;
    }

    /**
     * Establecer el lenguaje a utilizar
     *
     * @param  bool  $force  Forzar la detección del lenguaje para los inicios de sesión
     */
    public function setLanguage(bool $force = false): void
    {
        $lang = $this->context->getLocale();

        if (empty($lang) || $force === true) {
            self::$userLang = $this->getUserLang();
            self::$globalLang = $this->getGlobalLang();

            $lang = self::$userLang ?: self::$globalLang;

            $this->context->setLocale($lang);
        }

        self::setLocales($lang);
    }

    /**
     * Devuelve el lenguaje del usuario
     */
    private function getUserLang(): string
    {
        $userData = $this->context->getUserData();

        return ($userData->getId() > 0)
            ? $userData->getPreferences()->getLang()
            : '';
    }

    /**
     * Establece el lenguaje de la aplicación.
     * Esta función establece el lenguaje según esté definido en la configuración o en el navegador.
     */
    private function getGlobalLang(): string
    {
        return $this->configData->getSiteLang() ?: $this->getBrowserLang();
    }

    /**
     * Devolver el lenguaje que acepta el navegador
     */
    private function getBrowserLang(): string
    {
        $lang = $this->request->getHeader('Accept-Language');

        return $lang !== ''
            ? str_replace('-', '_', substr($lang, 0, 5))
            : 'en_US';
    }

    /**
     * Establecer las locales de gettext
     */
    public static function setLocales(string $lang): void
    {
        $lang .= '.utf8';

        self::$localeStatus = setlocale(LC_MESSAGES, $lang);

        putenv('LANG='.$lang);
        putenv('LANGUAGE='.$lang);

        $locale = setlocale(LC_ALL, $lang);

        if ($locale === false) {
            logger('Could not set locale', 'ERROR');
            logger('Domain path: '.LOCALES_PATH);
        } else {
            logger('Locale set to: '.$locale);
        }

        bindtextdomain('messages', LOCALES_PATH);
        textdomain('messages');
        bind_textdomain_codeset('messages', 'UTF-8');
    }

    /**
     * Establecer el lenguaje global para las traducciones
     */
    public function setAppLocales(): void
    {
        if (!$this->context->isInitialized()) {
            return;
        }

        if ($this->configData->getSiteLang() !== $this->context->getLocale()) {
            self::setLocales($this->configData->getSiteLang());

            self::$appSet = true;
        }
    }

    /**
     * Restablecer el lenguaje global para las traducciones
     */
    public function unsetAppLocales(): void
    {
        if (self::$appSet === true) {
            self::setLocales($this->context->getLocale());

            self::$appSet = false;
        }
    }
}
