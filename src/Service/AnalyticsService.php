<?php

namespace PrestaShop\Module\PsAccounts\Service;

use Segment;

class AnalyticsService
{
    public function __construct(string $segmentWriteKey)
    {
        Segment::init($segmentWriteKey);
    }

    public function track(array $message): void
    {
        Segment::track($message);
        Segment::flush();
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
        Segment::page([
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
        ]);
        Segment::flush();
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
        Segment::identify([
            'userId' => $userUid,
            // aggregate any previous anonymous call
            'anonymous_id' => $this->getAnonymousId(),
            'traits' => [$name ? ['name' => $name] : []] +
                [$email ? ['email' => $email] : []],
        ]);
    }

    public function group(?string $userUid, string $shopUid): void
    {
        Segment::group([
            'userId' => $userUid,
            'groupId' => $shopUid,
            'anonymous_id' => $this->getAnonymousId(),
//            "traits" => [
//                "name" => $shopName,
//            ]
        ]);
    }

    protected function getAnonymousId(): ?string
    {
        return $_COOKIE['ajs_anonymous_id'] ?? session_id();
    }
}
