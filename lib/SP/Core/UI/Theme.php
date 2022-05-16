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

namespace SP\Core\UI;

use SP\Config\Config;
use SP\Config\ConfigDataInterface;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Core\Context\ContextBase;
use SP\Core\Context\ContextInterface;
use SP\Core\Exceptions\InvalidClassException;
use SP\Storage\File\FileCacheInterface;
use SP\Storage\File\FileException;

defined('APP_ROOT') || die();

/**
 * Class Theme
 *
 * @package SP
 */
final class Theme implements ThemeInterface
{
    public const ICONS_CACHE_FILE = CACHE_PATH.DIRECTORY_SEPARATOR.'icons.cache';
    /**
     * Cache expire time
     */
    public const CACHE_EXPIRE = 86400;
    private string              $themeUri      = '';
    private string              $themePath     = '';
    private string              $themePathFull = '';
    private string              $themeName     = '';
    private string              $viewsPath     = '';
    private ?ThemeIcons         $icons         = null;
    private ConfigDataInterface $configData;
    private ContextInterface    $context;
    private string              $module;
    private FileCacheInterface  $fileCache;

    /**
     * Theme constructor.
     *
     * @param  string  $module
     * @param  Config  $config
     * @param  ContextInterface  $context
     * @param  FileCacheInterface  $fileCache
     */
    public function __construct(
        string $module,
        Config $config,
        ContextInterface $context,
        FileCacheInterface $fileCache
    ) {
        $this->configData = $config->getConfigData();
        $this->context = $context;
        $this->fileCache = $fileCache;
        $this->module = $module;
    }

    /**
     * Inicializar el tema visual a utilizar
     *
     * @param  bool  $force  Forzar la detección del tema para los inicios de sesión
     *
     * @throws InvalidClassException
     */
    public function initTheme(bool $force): void
    {
        if (is_dir(VIEW_PATH)) {
            if (empty($this->themeName) || $force === true) {
                $this->themeName = $this->getUserTheme() ?: $this->getGlobalTheme();
            }

            $this->themeUri = BootstrapBase::$WEBURI.'/app/modules/'.$this->module.'themes'.$this->themeName;
            $this->themePath = str_replace(APP_ROOT, '', VIEW_PATH).DIRECTORY_SEPARATOR.$this->themeName;
            $this->themePathFull = VIEW_PATH.DIRECTORY_SEPARATOR.$this->themeName;
            $this->viewsPath = $this->themePathFull.DIRECTORY_SEPARATOR.'views';

            $this->initIcons();
        }
    }

    /**
     * Obtener el tema visual del usuario
     */
    protected function getUserTheme(): ?string
    {
        return $this->context->isLoggedIn() ? $this->context->getUserData()->getPreferences()->getTheme() : null;
    }

    /**
     * Devolver el tema visual de sysPass desde la configuración
     */
    protected function getGlobalTheme(): string
    {
        return $this->configData->getSiteTheme();
    }

    /**
     * Inicializar los iconos del tema actual
     *
     * @throws InvalidClassException
     */
    private function initIcons(): void
    {
        try {
            if ($this->context->getAppStatus() !== ContextBase::APP_STATUS_RELOADED
                && !$this->fileCache->isExpired(self::CACHE_EXPIRE)
            ) {
                $this->icons = $this->fileCache->load();

                logger('Loaded icons cache', 'INFO');

                return;
            }
        } catch (FileException $e) {
            processException($e);
        }

        $this->saveIcons();

    }

    /**
     * @throws InvalidClassException
     */
    private function saveIcons(): void
    {
        $iconsClass = $this->themePathFull.DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'Icons.php';

        if (file_exists($iconsClass)) {
            if (!($this->icons = require $iconsClass) instanceof ThemeIcons) {
                throw new InvalidClassException(__u('Invalid icons class'));
            }

            try {
                $this->fileCache->save($this->icons);

                logger('Saved icons cache', 'INFO');
            } catch (FileException $e) {
                processException($e);
            }
        }
    }

    /**
     * Obtener los temas disponibles desde el directorio de temas
     */
    public function getThemesAvailable(): array
    {
        $themesAvailable = [];

        $themesDirs = dir(VIEW_PATH);

        while (false !== ($themeDir = $themesDirs->read())) {
            if ($themeDir !== '.' && $themeDir !== '..') {
                $themeFile = VIEW_PATH.DIRECTORY_SEPARATOR.$themeDir.DIRECTORY_SEPARATOR.'index.php';

                if (file_exists($themeFile)) {
                    $themeInfo = require $themeFile;

                    $themesAvailable[$themeDir] = $themeInfo['name'];
                }
            }
        }

        $themesDirs->close();

        return $themesAvailable;
    }

    /**
     * Obtener la información del tema desde el archivo de información
     *
     * @return array (
     *          'name' => string
     *          'creator' => string
     *          'version' => string
     *          'js' => array
     *          'css' => array
     *  )
     */
    public function getThemeInfo(): array
    {
        $themeFile = $this->themePathFull.DIRECTORY_SEPARATOR.'index.php';

        if (file_exists($themeFile)) {
            $themeInfo = include $themeFile;

            if (is_array($themeInfo)) {
                return $themeInfo;
            }
        }

        return [];
    }

    public function getThemeUri(): string
    {
        return $this->themeUri;
    }

    public function getThemePath(): string
    {
        return $this->themePath;
    }

    public function getThemeName(): string
    {
        return $this->themeName;
    }

    public function getIcons(): ThemeIcons
    {
        return clone $this->icons;
    }

    public function getViewsPath(): string
    {
        return $this->viewsPath;
    }
}