<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@$syspass.org
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

namespace Theme;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\Init;
use SP\Core\ThemeIconsBase;
use SP\Html\Assets\FontIcon;
use SP\Html\Assets\ImageIcon;

/**
 * Class Icons con los iconos del tema visual
 *
 * @package Theme
 */
class Icons extends ThemeIconsBase
{
    /**
     * Establecer los iconos utilizados en el DataGrid
     */
    public function setIcons()
    {
        $this->_iconAdd = new ImageIcon(Init::$WEBURI . '/imgs/add.png', null, _('Añadir'));
        $this->_iconView = new ImageIcon(Init::$WEBURI . '/imgs/view.png', null, _('Ver Detalles'));
        $this->_iconViewPass = new ImageIcon(Init::$WEBURI . '/imgs/user-pass.png', null, _('Ver Clave'));
        $this->_iconEdit = new ImageIcon(Init::$WEBURI . '/imgs/edit.png', null, _('Editar'));
        $this->_iconDelete = new ImageIcon(Init::$WEBURI . '/imgs/delete.png', null, _('Eliminar'));
        $this->_iconEditPass = new ImageIcon(Init::$WEBURI . '/imgs/key.png', null, _('Cambiar Clave'));
        $this->_iconAppAdmin = new ImageIcon(Init::$WEBURI . '/imgs/check_blue.png', null, _('Admin Aplicación'));
        $this->_iconAccAdmin = new ImageIcon(Init::$WEBURI . '/imgs/check_orange.png', null, _('Admin Cuentas'));
        $this->_iconLdapUser = new ImageIcon(Init::$WEBURI . '/imgs/ldap.png', null, _('Usuario de LDAP'));
        $this->_iconDisabled = new ImageIcon(Init::$WEBURI . '/imgs/disabled.png', null, _('Deshabilitado'));
        $this->_iconRefresh = new ImageIcon(Init::$WEBURI . '/imgs/refresh.png', null, _('Actualizar'));
        $this->_iconCopy = new ImageIcon(Init::$WEBURI . '/imgs/btn_copy.png', null, _('Copiar'));
        $this->_iconClipboard = new ImageIcon(Init::$WEBURI . '/imgs/clipboard.png');
        $this->_iconEmail = new ImageIcon(Init::$WEBURI . '/imgs/request.png', null, _('Email'));
        $this->_iconOptional = new ImageIcon(Init::$WEBURI . '/imgs/action.png');
        $this->_iconPublicLink = new ImageIcon(Init::$WEBURI . '/imgs/action.png');
        $this->_iconBack = new ImageIcon(Init::$WEBURI . '/imgs/back.png', null, _('Volver'));
        $this->_iconRestore = new ImageIcon(Init::$WEBURI . '/imgs/restore.png', null, ('Restaurar'));
        $this->_iconSave = new ImageIcon(Init::$WEBURI . '/imgs/check.png', null, _('Guardar'));
        $this->_iconHelp = new ImageIcon(Init::$WEBURI . '/imgs/help.png', null, _('Ayuda'));
        $this->_iconClear = new ImageIcon(Init::$WEBURI . '/imgs/clear.png', null, _('Limpiar'));
        $this->_iconPlay = new ImageIcon(Init::$WEBURI . '/imgs/start.png', null, _('Realizar'));
        $this->_iconDownload = new ImageIcon(Init::$WEBURI . '/imgs/download.png', null, _('Descargar'));
        $this->_iconWarning = new ImageIcon(Init::$WEBURI . '/imgs/warning.png', null, _('Aviso'));

        // Iconos de Navegación
        $this->_iconNavPrev = new FontIcon('chevron_left', null, _('Página anterior'));
        $this->_iconNavNext = new FontIcon('chevron_right', null, _('Página siguiente'));
        $this->_iconNavFirst = new FontIcon('arrow_back', null, _('Primera página'));
        $this->_iconNavLast = new FontIcon('arrow_forward', null, _('Última página'));

        // Iconos de Ordenación
        $this->_iconUp = new FontIcon('arrow_drop_up');
        $this->_iconDown = new FontIcon('arrow_drop_down');
    }
}