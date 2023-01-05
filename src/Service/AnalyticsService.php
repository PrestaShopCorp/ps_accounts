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

    public function page(string $path, ?string $referrer, ?string $search, string $title, string $url): void
    {
        Segment::page([
            'path' => $path,
            'referrer' => $referrer,
            'search' => $search,
            'title' => $title,
            'url' => $url,
        ]);
        Segment::flush();
    }
}
