<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Infrastructure\Account\Repositories;


/**
 * Class AccountRepositorySql
 */
final class AccountRepositorySql
{
    public const TOTAL_NUM_ACCOUNTS = 'SELECT SUM(n) AS num FROM 
            (SELECT COUNT(*) AS n FROM Account UNION SELECT COUNT(*) AS n FROM AccountHistory) a';

    public const INCREMENT_DECRYPT_COUNTER = 'UPDATE Account SET countDecrypt = (countDecrypt + 1) WHERE id = ? LIMIT 1';

    public const CREATE = 'INSERT INTO Account SET 
            clientId = ?,
            categoryId = ?,
            `name` = ?,
            login = ?,
            url = ?,
            pass = ?,
            `key` = ?,
            notes = ?,
            dateAdd = NOW(),
            userId = ?,
            userGroupId = ?,
            userEditId = ?,
            isPrivate = ?,
            isPrivateGroup = ?,
            passDate = UNIX_TIMESTAMP(),
            passDateChange = ?,
            parentId = ?';

    public const EDIT_PASSWORD = 'UPDATE Account SET 
            pass = ?,
            `key` = ?,
            userEditId = ?,
            dateEdit = NOW(),
            passDate = UNIX_TIMESTAMP(),
            passDateChange = ?
            WHERE id = ?';

    public const UPDATE_PASSWORD = 'UPDATE Account SET 
            pass = ?,
            `key` = ?
            WHERE id = ?';

    public const EDIT_RESTORE = 'UPDATE Account dst, 
            (SELECT * FROM AccountHistory AH WHERE AH.id = ?) src SET 
            dst.clientId = src.clientId,
            dst.categoryId = src.categoryId,
            dst.name = src.name,
            dst.login = src.login,
            dst.url = src.url,
            dst.notes = src.notes,
            dst.userGroupId = src.userGroupId,
            dst.userEditId = ?,
            dst.dateEdit = NOW(),
            dst.pass = src.pass,
            dst.key = src.key,
            dst.passDate = src.passDate,
            dst.passDateChange = src.passDateChange, 
            dst.parentId = src.parentId, 
            dst.isPrivate = src.isPrivate,
            dst.isPrivateGroup = src.isPrivateGroup
            WHERE dst.id = src.accountId';

    public const DELETE = 'DELETE FROM Account WHERE id = ? LIMIT 1';

    public const EDIT_BY_ID = 'SELECT * FROM account_data_v WHERE id = ? LIMIT 1';

    public const GET_ALL = 'SELECT * FROM Account ORDER BY id';

    public const INCREMENT_VIEW_COUNTER = 'UPDATE Account SET countView = (countView + 1) WHERE id = ? LIMIT 1';

    public const GET_DATA_FOR_LINK = 'SELECT Account.id, 
            Account.name,
            Account.login,
            Account.pass,
            Account.key,
            Account.url,
            Account.notes,
            Client.name AS clientName,
            Category.name AS categoryName
            FROM Account
            INNER JOIN Client ON Account.clientId = Client.id
            INNER JOIN Category ON Account.categoryId = Category.id 
            WHERE Account.id = ? LIMIT 1';

    public const GET_ACCOUNT_PASS_DATA = 'SELECT id, `name`, pass, `key` FROM Account WHERE BIT_LENGTH(pass) > 0';
}