<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Providers\Mail;

use Exception;
use SP\Core\Application;
use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Providers\MailerInterface;
use SP\Domain\Providers\MailProviderInterface;
use SP\Providers\Provider;

use function SP\__u;
use function SP\logger;
use function SP\processException;

/**
 * Class MailProvider
 *
 * @package SP\Providers\Mail
 */
final class MailProvider extends Provider implements MailProviderInterface
{
    private bool $debug = false;

    public function __construct(
        Application                      $application,
        private readonly MailerInterface $mailer
    ) {
        parent::__construct($application);
    }

    /**
     * Inicializar la clase PHPMailer.
     *
     * @throws MailProviderException
     */
    public function getMailerFrom(MailParams $mailParams): MailerInterface
    {
        $appName = AppInfoInterface::APP_NAME;
        $mailer = $this->mailer->getMailer();

        try {
            $mailer->set('SMTPAutoTLS', false);
            $mailer->isSMTP();
            $mailer->set('CharSet', 'utf-8');
            $mailer->set('Host', $mailParams->server);
            $mailer->set('Port', $mailParams->port);
            $mailer->set('SMTPSecure', strtolower($mailParams->security));

            if ($mailParams->mailAuthenabled) {
                $mailer->set('SMTPAuth', true);
                $mailer->set('Username', $mailParams->user);
                $mailer->set('Password', $mailParams->pass);
            }

            if ($this->debug) {
                $mailer->set('SMTPDebug', 2);
                $mailer->set('Debugoutput', static fn($str, $level) => logger($str, strtoupper($level)));
            }

            $mailer->setFrom($mailParams->from, $appName);
            $mailer->addReplyTo($mailParams->from, $appName);
            $mailer->set('WordWrap', 100);

            return $this->mailer;
        } catch (Exception $e) {
            processException($e);

            throw new MailProviderException(
                __u('Unable to initialize'),
                SPException::ERROR,
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    public function initialize(): void
    {
        // TODO: Implement initialize() method.
    }
}
