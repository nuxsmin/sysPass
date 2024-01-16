<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Account\Adapters;

use SP\Core\Bootstrap\BootstrapBase;
use SP\DataModel\ItemData;
use SP\Domain\Account\Models\AccountSearchView;
use SP\Domain\Account\Services\PublicLink;
use SP\Domain\Common\Dtos\ItemDataTrait;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Html\Html;

/**
 * Class AccountSearchItem para contener los datos de cada cuenta en la búsqueda
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
        protected AccountSearchView          $accountSearchView,
        private readonly AccountPermission   $accountAcl,
        private readonly ConfigDataInterface $configData,
        private array                        $tags,
        private int                          $textMaxLength = 0,
        private bool                         $favorite = false,
        private ?array                       $users = null,
        private ?array                       $userGroups = null,
        private ?string                      $color = null,
        private ?string                      $link = null,
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
        return Html::truncate($this->accountSearchView->getUrl(), $this->textMaxLength);
    }

    public function isUrlIslink(): bool
    {
        return preg_match('#^\w+://#', $this->accountSearchView->getUrl()) === 1;
    }

    public function getShortLogin(): string
    {
        return Html::truncate($this->accountSearchView->getLogin(), $this->textMaxLength);
    }

    public function getShortClientName(): string
    {
        return Html::truncate($this->accountSearchView->getClientName(), $this->textMaxLength / 3);
    }

    public function getClientLink(): ?string
    {
        return self::$wikiEnabled
            ? $this->configData->getWikiSearchurl() . $this->accountSearchView->getClientName()
            : null;
    }

    public function getPublicLink(): ?string
    {
        if (self::$publicLinkEnabled
            && $this->accountSearchView->getPublicLinkHash() !== null
        ) {
            $baseUrl = ($this->configData->getApplicationUrl() ?: BootstrapBase::$WEBURI) . BootstrapBase::$SUBURI;

            return PublicLink::getLinkForHash($baseUrl, $this->accountSearchView->getPublicLinkHash());
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
            '(G*) <em>' . $this->accountSearchView->getUserGroupName() . '</em>',
            '(U*) <em>' . $this->accountSearchView->getUserLogin() . '</em>',
        ];

        $userLabel = $this->accountSearchView->getOtherUserEdit() === 1 ? 'U+' : 'U';
        $userGroupLabel = $this->accountSearchView->getOtherUserGroupEdit() === 1 ? 'G+' : 'G';

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
            ? $this->accountSearchView->getNumFiles()
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

    public function getAccountSearchView(): AccountSearchView
    {
        return $this->accountSearchView;
    }

    public function getShortNotes(): string
    {
        if ($this->accountSearchView->getNotes()) {
            return nl2br(htmlspecialchars(Html::truncate($this->accountSearchView->getNotes(), 300), ENT_QUOTES));
        }

        return '';
    }

    /**
     * Develve si la clave ha caducado
     */
    public function isPasswordExpired(): bool
    {
        return $this->configData->isAccountExpireEnabled()
               && $this->accountSearchView->getPassDateChange() > 0
               && time() > $this->accountSearchView->getPassDateChange();
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
        return preg_match('/^' . $wikiFilter . '/i', $this->accountSearchView->getName()) === 1;
    }
}
