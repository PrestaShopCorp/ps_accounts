<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PsAccounts\Service;

use Monolog\Logger;
use Ramsey\Uuid\Uuid;
use Segment;

class AnalyticsService
{
    const COOKIE_ANONYMOUS_ID = 'ajs_anonymous_id';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private static $anonymousId;

    /**
     * @param string $segmentWriteKey
     * @param Logger $logger
     *
     * @throws \Exception
     */
    public function __construct($segmentWriteKey, Logger $logger)
    {
        Segment::init($segmentWriteKey);
        $this->logger = $logger;
        $this->initAnonymousId();
    }

    /**
     * @param array $message
     *
     * @return void
     */
    public function track($message)
    {
        try {
            Segment::track($message);
            Segment::flush();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $message);
        }
    }

    /**
     * @param string|null $userUid
     * @param string $application
     *
     * @return void
     *
     * @throws \Exception
     */
    public function trackUserSignedIntoApp($userUid, $application)
    {
        $this->track([
            'event' => 'User Signed Into App',
            'userId' => $userUid,
            'anonymousId' => $this->getAnonymousId(),
            'properties' => [
                'application' => $application,
            ],
        ]);
    }

    /**
     * @param string|null $userUid
     * @param string $userEmail
     *
     * @return void
     *
     * @throws \Exception
     */
    public function trackUserSignedIntoBackOfficeLocally($userUid, $userEmail)
    {
        $this->track([
            'event' => 'User Signed Into Back Office Locally',
            'userId' => $userUid,
            'anonymousId' => $this->getAnonymousId(),
            'properties' => [
                'email' => $userEmail,
            ],
        ]);
    }

    /**
     * @param string|null $userUid
     * @param string|null $type
     * @param string|null $description
     *
     * @return void
     *
     * @throws \Exception
     */
    public function trackBackOfficeSSOSignInFailed($userUid, $type, $description)
    {
        $this->track([
            'event' => 'Back Office SSO Sign In Failed',
            'userId' => $userUid,
            'anonymousId' => $this->getAnonymousId(),
            'properties' => [
                'type' => $type,
                'description' => $description,
            ],
        ]);
    }

    /**
     * @param string|null $userUid
     * @param string $userEmail
     * @param string $shopUid
     * @param string $shopUrl
     * @param string $shopBoUrl
     * @param string|null $triggeredBy
     * @param string|null $errorCode
     *
     * @return void
     *
     * @throws \Exception
     */
    public function trackMaxRefreshTokenAttempts(
        $userUid,
        $userEmail,
        $shopUid,
        $shopUrl,
        $shopBoUrl,
        $triggeredBy = null,
        $errorCode = null
    ) {
        $this->track([
            'event' => 'Unintentionally Dissociated',
            'userId' => $userUid,
            'anonymousId' => $this->getAnonymousId(),
            'properties' => [
                'shopUid' => $shopUid,
                'shopUrl' => $shopUrl,
                'shopBoUrl' => $shopBoUrl,
                'ownerEmail' => $userEmail,
                'dissociatedAt' => (new \DateTime())->format('Uv'),
                'psStoreVersion' => _PS_VERSION_,
                'psAccountVersion' => \Ps_accounts::VERSION,
                'triggeredBy' => $triggeredBy,
                'errorCode' => $errorCode,
            ],
        ]);
    }

    /**
     * @param string $name
     * @param string|null $userId
     * @param string|null $path
     * @param string|null $referrer
     * @param string|null $search
     * @param string|null $title
     * @param string|null $url
     *
     * @return void
     *
     * @throws \Exception
     */
    public function page(
        $name,
        $userId = null,
        $path = null,
        $referrer = null,
        $search = null,
        $title = null,
        $url = null
    ) {
        $message = [
            'userId' => $userId,
            'anonymousId' => $this->getAnonymousId(),
            'name' => $name,
            'properties' => [
                'path' => $path !== null ? $path : $_SERVER['PATH_INFO'],
                'referrer' => $referrer !== null ? $referrer : $_SERVER['HTTP_REFERER'],
                'search' => $search,
                'title' => $title,
                'url' => $url !== null ? $url : $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            ],
        ];
        try {
            Segment::page($message);
            Segment::flush();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $message);
        }
    }

    /**
     * @param string|null $userUid
     *
     * @return void
     *
     * @throws \Exception
     */
    public function pageAccountsBoLogin($userUid = null)
    {
        $this->page('Accounts Backoffice Login Page', $userUid);
    }

    /**
     * @param string|null $userUid
     *
     * @return void
     *
     * @throws \Exception
     */
    public function pageLocalBoLogin($userUid = null)
    {
        $this->page('Local Backoffice Login Page', $userUid);
    }

    /**
     * @param string|null $userUid
     * @param string|null $name
     * @param string|null $email
     *
     * @return void
     *
     * @throws \Exception
     */
    public function identify($userUid, $name, $email)
    {
        $message = [
            'userId' => $userUid,
            'anonymousId' => $this->getAnonymousId(),
            'traits' => [$name ? ['name' => $name] : []] +
                [$email ? ['email' => $email] : []],
        ];
        try {
            Segment::identify($message);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $message);
        }
    }

    /**
     * @param string|null $userUid
     * @param string $shopUid
     *
     * @return void
     *
     * @throws \Exception
     */
    public function group($userUid, $shopUid)
    {
        $message = [
            'userId' => $userUid,
            'groupId' => $shopUid,
            'anonymousId' => $this->getAnonymousId(),
//            "traits" => [
//                "name" => $shopName,
//            ]
        ];
        try {
            Segment::group($message);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $message);
        }
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function getAnonymousId()
    {
        $this->initAnonymousId();

        return self::$anonymousId;
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    private function initAnonymousId()
    {
        if (!isset(self::$anonymousId)) {
            if (!isset($_COOKIE[self::COOKIE_ANONYMOUS_ID])) {
                self::$anonymousId = Uuid::uuid4()->toString();
                try {
                    setcookie(self::COOKIE_ANONYMOUS_ID, self::$anonymousId, time() + 3600);
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            } else {
                self::$anonymousId = $_COOKIE[self::COOKIE_ANONYMOUS_ID];
            }
        }
    }
}
