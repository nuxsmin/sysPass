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

namespace SP\Domain\Account\Services;

use SP\Core\Bootstrap\BootstrapBase;
use SP\DataModel\AccountSearchVData;
use SP\DataModel\ItemData;
use SP\Domain\Common\Dtos\ItemDataTrait;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Html\Html;

/**
 * Class AccountSearchItem para contener los datos de cada cuenta en la búsqueda
 *
 * @package SP\Controller
 */
final class AccountSearchItem
{
    use ItemDataTrait;

    public static bool $accountLink       = false;
    public static bool $topNavbar         = false;
    public static bool $optionalActions   = false;
    public static bool $showTags          = false;
    public static bool $requestEnabled    = true;
    public static bool $wikiEnabled       = false;
    public static bool $dokuWikiEnabled   = false;
    public static bool $publicLinkEnabled = false;
    public static bool $isDemoMode        = false;

    public function __construct(
        protected AccountSearchVData $accountSearchVData,
        private AccountAcl $accountAcl,
        private ConfigDataInterface $configData,
        private array $tags,
        private int $textMaxLength = 0,
        private bool $favorite = false,
        private ?array $users = null,
        private ?array $userGroups = null,
        private ?string $color = null,
        private ?string $link = null,
    ) {
        $this->tags = self::buildFromItemData($this->tags);
    }

    public function isFavorite(): bool
    {
        return $this->favorite;
    }

    public function setFavorite(bool $favorite): void
    {
        $this->favorite = $favorite;
    }

    public function isShowRequest(): bool
    {
        return !$this->accountAcl->isShow() && self::$requestEnabled;
    }

    public function isShow(): bool
    {
        return $this->accountAcl->isShow();
    }

    public function isShowCopyPass(): bool
    {
        return $this->accountAcl->isShowViewPass()
               && !$this->configData->isAccountPassToImage();
    }

    public function isShowViewPass(): bool
    {
        return $this->accountAcl->isShowViewPass();
    }

    public function isShowOptional(): bool
    {
        return ($this->accountAcl->isShow() && !self::$optionalActions);
    }

    public function setTextMaxLength(int $textMaxLength): void
    {
        $this->textMaxLength = $textMaxLength;
    }

    public function getShortUrl(): string
    {
        return Html::truncate($this->accountSearchVData->getUrl(), $this->textMaxLength);
    }

    public function isUrlIslink(): bool
    {
        return preg_match('#^\w+://#', $this->accountSearchVData->getUrl()) === 1;
    }

    public function getShortLogin(): string
    {
        return Html::truncate($this->accountSearchVData->getLogin(), $this->textMaxLength);
    }

    public function getShortClientName(): string
    {
        return Html::truncate($this->accountSearchVData->getClientName(), $this->textMaxLength / 3);
    }

    public function getClientLink(): ?string
    {
        return self::$wikiEnabled
            ? $this->configData->getWikiSearchurl().$this->accountSearchVData->getClientName()
            : null;
    }

    public function getPublicLink(): ?string
    {
        if (self::$publicLinkEnabled
            && $this->accountSearchVData->getPublicLinkHash() !== null
        ) {
            $baseUrl = ($this->configData->getApplicationUrl() ?: BootstrapBase::$WEBURI).BootstrapBase::$SUBURI;

            return PublicLinkService::getLinkForHash($baseUrl, $this->accountSearchVData->getPublicLinkHash());
        }

        return null;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): void
    {
        $this->color = $color;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getAccesses(): array
    {
        $accesses = [
            '(G*) <em>'.$this->accountSearchVData->getUserGroupName().'</em>',
            '(U*) <em>'.$this->accountSearchVData->getUserLogin().'</em>',
        ];

        $userLabel = $this->accountSearchVData->getOtherUserEdit() === 1 ? 'U+' : 'U';
        $userGroupLabel = $this->accountSearchVData->getOtherUserGroupEdit() === 1 ? 'G+' : 'G';

        foreach ($this->userGroups ?? [] as $group) {
            $accesses[] = sprintf(
                '(%s) <em>%s</em>',
                $userGroupLabel,
                $group->getName()
            );
        }

        foreach ($this->users ?? [] as $user) {
            $accesses[] = sprintf(
                '(%s) <em>%s</em>',
                $userLabel,
                $user->login
            );
        }

        return $accesses;
    }

    public function getNumFiles(): int
    {
        return $this->configData->isFilesEnabled()
            ? $this->accountSearchVData->getNumFiles()
            : 0;
    }

    public function isShowView(): bool
    {
        return $this->accountAcl->isShowView();
    }

    public function isShowEdit(): bool
    {
        return $this->accountAcl->isShowEdit();
    }

    public function isShowCopy(): bool
    {
        return $this->accountAcl->isShowCopy();
    }

    public function isShowDelete(): bool
    {
        return $this->accountAcl->isShowDelete();
    }

    public function getAccountSearchVData(): AccountSearchVData
    {
        return $this->accountSearchVData;
    }

    public function getShortNotes(): string
    {
        if ($this->accountSearchVData->getNotes()) {
            return nl2br(htmlspecialchars(Html::truncate($this->accountSearchVData->getNotes(), 300), ENT_QUOTES));
        }

        return '';
    }

    /**
     * Develve si la clave ha caducado
     */
    public function isPasswordExpired(): bool
    {
        return $this->configData->isAccountExpireEnabled()
               && $this->accountSearchVData->getPassDateChange() > 0
               && time() > $this->accountSearchVData->getPassDateChange();
    }

    /**
     * @return ItemData[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function isWikiMatch(string $wikiFilter): bool
    {
        return preg_match('/^'.$wikiFilter.'/i', $this->accountSearchVData->getName()) === 1;
    }
}
