<?php

namespace PrestaShop\Module\PsAccounts\Service;

use Segment;

class AnalyticsService
{
    public function __construct(string $segmentWriteKey)
    {
        Segment::init($segmentWriteKey);
    }

    public function track(array $properties): void
    {
        // TODO: lazy identify
        // TODO: remove old segment key and vue_component validation
        // $this->identify();
        Segment::track($properties);
        Segment::flush();
    }

    public function trackUserSignedIntoApp(string $userUid, ?string $shopUid, string $application): void
    {
        $this->track([
            'event' => 'User Signed Into App',
            'userId' => $userUid,
            'context' => [
                'groupId' => $shopUid,
            ],
            'properties' => [
                'application' => $application,
            ],
        ]);
    }

    public function trackUserSignedIntoBackOfficeLocally(string $userEmail, ?string $userUid, string $shopUid): void
    {
        $this->track([
            'event' => 'User Signed Into Back Office Locally',
            'email' => $userEmail,
            'userId' => $userUid,
            'context' => [
                'groupId' => $shopUid,
            ],
        ]);
    }

    public function trackBackOfficeSSOSignInFailed(string $userUid, ?string $shopUid, ?string $type, ?string $description): void
    {
        $this->track([
            'event' => 'Back Office SSO Sign In Failed',
            'userId' => $userUid,
            'context' => [
                'groupId' => $shopUid,
            ],
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
        $data = [];
        if ($userId) {
            $data['userId'] = $userId;
        } else {
            $data['anonymousId'] = session_id();
        }

        Segment::page($data + [
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

    public function pageAccountsBoLogin(): void
    {
        $this->page('Accounts Backoffice Login Page');
    }

    public function pageLocalBoLogin(): void
    {
        $this->page('Local Backoffice Login Page');
    }

    public function identify(string $userUid, string $name, string $email): void
    {
        Segment::identify([
            'userId' => $userUid,
            'traits' => [
                'name' => $name,
                'email' => $email,
            ],
        ]);
    }
}
