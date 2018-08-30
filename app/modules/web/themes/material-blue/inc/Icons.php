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

defined('APP_ROOT') || die();

use SP\Core\UI\ThemeIcons;
use SP\Html\Assets\FontIcon;

$themeIcons = new ThemeIcons();
$themeIcons->addIcon('add', new FontIcon('add', 'mdl-color-text--indigo-A200', __u('Añadir')));
$themeIcons->addIcon('view', new FontIcon('visibility', 'mdl-color-text--indigo-A200', __u('Ver Detalles')));
$themeIcons->addIcon('viewPass', new FontIcon('lock_open', 'mdl-color-text--indigo-A200', __u('Ver Clave')));
$themeIcons->addIcon('edit', new FontIcon('mode_edit', 'mdl-color-text--amber-A200', __u('Editar')));
$themeIcons->addIcon('delete', new FontIcon('remove_circle', 'mdl-color-text--red-A200', __u('Eliminar')));
$themeIcons->addIcon('editPass', new FontIcon('lock_outline', 'mdl-color-text--amber-A200', __u('Cambiar Clave')));
$themeIcons->addIcon('appAdmin', new FontIcon('star', 'mdl-color-text--amber-A100', __u('Admin Aplicación')));
$themeIcons->addIcon('accAdmin', new FontIcon('star_half', 'mdl-color-text--amber-A100', __u('Admin Cuentas')));
$themeIcons->addIcon('ldapUser', new FontIcon('business', 'mdl-color-text--deep-purple-A100', __u('Usuario de LDAP')));
$themeIcons->addIcon('disabled', new FontIcon('error', 'mdl-color-text--red-A100', __u('Deshabilitado')));
$themeIcons->addIcon('enabled', new FontIcon('check_circle', 'mdl-color-text--teal-500', __u('Habilitado')));
$themeIcons->addIcon('refresh', new FontIcon('refresh', 'mdl-color-text--teal-500', __u('Actualizar')));
$themeIcons->addIcon('copy', new FontIcon('content_copy', 'mdl-color-text--indigo-A200', __u('Copiar')));
$themeIcons->addIcon('clipboard', new FontIcon('content_paste', 'mdl-color-text--indigo-A200'));
$themeIcons->addIcon('email', new FontIcon('email', 'mdl-color-text--indigo-A200', __u('Email')));
$themeIcons->addIcon('optional', new FontIcon('settings'));
$themeIcons->addIcon('publicLink', new FontIcon('link', 'mdl-color-text--teal-500'));
$themeIcons->addIcon('back', new FontIcon('arrow_back', 'mdl-color-text--indigo-A200', __u('Volver')));
$themeIcons->addIcon('restore', new FontIcon('restore', 'mdl-color-text--teal-500', __u('Restaurar')));
$themeIcons->addIcon('save', new FontIcon('save', 'mdl-color-text--teal-500', __u('Guardar')));
$themeIcons->addIcon('help', new FontIcon('help_outline', 'mdl-color-text--indigo-A100', __u('Ayuda')));
$themeIcons->addIcon('clear', new FontIcon('clear_all', 'mdl-color--indigo-A200', __u('Limpiar')));
$themeIcons->addIcon('play', new FontIcon('play_circle_filled', 'mdl-color-text--teal-500', __u('Realizar')));
$themeIcons->addIcon('download', new FontIcon('file_download', 'mdl-color-text--indigo-A200', __u('Descargar')));
$themeIcons->addIcon('warning', new FontIcon('warning', 'mdl-color-text--amber-A100', __u('Aviso')));
$themeIcons->addIcon('check', new FontIcon('cached', 'mdl-color-text--indigo-A200', __u('Comprobar')));
$themeIcons->addIcon('search', new FontIcon('search', 'mdl-color-text--indigo-A200', __u('Buscar')));
$themeIcons->addIcon('account', new FontIcon('account_box', 'mdl-color-text--indigo-A200'));
$themeIcons->addIcon('group', new FontIcon('group_work', 'mdl-color-text--indigo-A200'));
$themeIcons->addIcon('settings', new FontIcon('settings', 'mdl-color-text--indigo-A200', __u('Configuración')));
$themeIcons->addIcon('headline', new FontIcon('view_headline', 'mdl-color-text--indigo-A200'));
$themeIcons->addIcon('info', new FontIcon('info_outline', 'mdl-color-text--indigo-A200', __u('Información')));
$themeIcons->addIcon('critical', new FontIcon('error_outline', 'mdl-color-text--red-A200', __u('Crítico')));
$themeIcons->addIcon('notices', new FontIcon('notifications', 'mdl-color-text--indigo-A200', __u('Notificaciones')));
$themeIcons->addIcon('remove', new FontIcon('remove', 'mdl-color-text--indigo-A200', __u('Eliminar')));
$themeIcons->addIcon('previous', new FontIcon('chevron_left', null, __u('Página anterior')));
$themeIcons->addIcon('next', new FontIcon('chevron_right', null, __u('Página siguiente')));
$themeIcons->addIcon('first', new FontIcon('arrow_back', null, __u('Primera página')));
$themeIcons->addIcon('last', new FontIcon('arrow_forward', null, __u('Última página')));
$themeIcons->addIcon('up', new FontIcon('arrow_drop_up'));
$themeIcons->addIcon('down', new FontIcon('arrow_drop_down'));

return $themeIcons;