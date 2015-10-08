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
 * Class PublicLinkUtil con utilidades para la gestión de enlaces
 *
 * @package SP
 */
class PublicLinkUtil
{
    /**
     * Obtener los enlaces creados
     *
     * @param int $id EL id del enlace a obtener
     * @return array|bool
     */
    public static function getLinks($id = null)
    {
        if (!is_null($id)){
            $query = 'SELECT publicLink_id, publicLink_hash, publicLink_linkData ' .
                'FROM publicLinks ' .
                'WHERE publicLink_id = :id LIMIT 1';
            $data['id'] = $id;
        } else {
            $query = 'SELECT publicLink_id, publicLink_hash, publicLink_linkData FROM publicLinks';
            $data = null;
        }

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return array();
        }

        foreach ($queryRes as $data) {
            /**
             * @var PublicLink $PublicLink
             */
            $PublicLink = unserialize($data->publicLink_linkData);

            $link = new \stdClass();
            $link->publicLink_id = $data->publicLink_id;
            $link->publicLink_hash = $data->publicLink_hash;
            $link->publicLink_account = AccountUtil::getAccountNameById($PublicLink->getItemId());
            $link->publicLink_user = UserUtil::getUserLoginById($PublicLink->getUserId());
            $link->publicLink_notify = ($PublicLink->isNotify()) ? _('ON') : _('OFF');
            $link->publicLink_dateAdd = date("Y-m-d H:i", $PublicLink->getDateAdd());
            $link->publicLink_dateExpire = date("Y-m-d H:i", $PublicLink->getDateExpire());
            $link->publicLink_views = $PublicLink->getCountViews() . '/' .  $PublicLink->getMaxCountViews();
            $link->publicLink_useInfo = $PublicLink->getUseInfo();

            $publicLinks[] = $link;
        }

        return $publicLinks;
    }
}