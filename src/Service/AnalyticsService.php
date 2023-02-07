<?php

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

    public function __construct(string $segmentWriteKey, Logger $logger)
    {
        Segment::init($segmentWriteKey);
        $this->initAnonymousId();
        $this->logger = $logger;
    }

    public function track(array $message): void
    {
        try {
            Segment::track($message);
            Segment::flush();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $message);
        }
    }

    public function trackUserSignedIntoApp(?string $userUid, string $application): void
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

    public function trackUserSignedIntoBackOfficeLocally(?string $userUid, string $userEmail): void
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

    public function trackBackOfficeSSOSignInFailed(?string $userUid, ?string $type, ?string $description): void
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

    public function page(
        string $name,
        ?string $userId = null,
        ?string $path = null,
        ?string $referrer = null,
        ?string $search = null,
        ?string $title = null,
        ?string $url = null
    ): void {
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

    public function pageAccountsBoLogin(?string $userUid = null): void
    {
        $this->page('Accounts Backoffice Login Page', $userUid);
    }

    public function pageLocalBoLogin(?string $userUid = null): void
    {
        $this->page('Local Backoffice Login Page', $userUid);
    }

    public function identify(?string $userUid, ?string $name, ?string $email): void
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

    public function group(?string $userUid, string $shopUid): void
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

    public function getAnonymousId(): string
    {
        $this->initAnonymousId();

        return self::$anonymousId;
    }

    private function initAnonymousId(): void
    {
        if (!isset(self::$anonymousId)) {
            if (!isset($_COOKIE[self::COOKIE_ANONYMOUS_ID])) {
                self::$anonymousId = Uuid::uuid4();
                setcookie(self::COOKIE_ANONYMOUS_ID, self::$anonymousId, time() + 3600);
            } else {
                self::$anonymousId = $_COOKIE[self::COOKIE_ANONYMOUS_ID];
            }
        }
    }
}
