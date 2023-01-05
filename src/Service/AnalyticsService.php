<?php

namespace PrestaShop\Module\PsAccounts\Service;

use Segment;

class AnalyticsService
{
    public function __construct(string $segmentWriteKey)
    {
        Segment::init($segmentWriteKey);
    }

    public function track(array $properties)
    {
        Segment::track($properties);
        Segment::flush();
    }

    public function trackUserSignedIntoApp($userUid, $shopUid, $application)
    {
        $this->track([
            "event" => "User Signed Into App",
            "userId"=> $userUid,
            "context"=> [
                "groupId"=> $shopUid,
            ],
            "properties" => [
                "application"=> $application,
            ],
        ]);
    }

    public function trackUserSignedIntoBackOfficeLocally($userUid, $userEmail, $shopUid)
    {
        $this->track([
            "event" => "User Signed Into Back Office Locally",
            "email" => $userEmail,
            "userId" => $userUid,
            "context" => [
                "groupId" => $shopUid,
            ],
        ]);
    }

    public function trackBackOfficeSSOSignInFailed($userUid, $shopUid, $type, $description)
    {
        $this->track([
            "event" => "Back Office SSO Sign In Failed",
            "userId" => $userUid,
            "context" => [
                "groupId" => $shopUid,
            ],
            "properties" => [
                "type" => $type,
                "description" => $description,
            ],
        ]);
    }

    public function page($path, $referrer, $search, $title, $url)
    {
        Segment::page([
            "path" => $path,
            "referrer" => $referrer,
            "search" => $search,
            "title" => $title,
            "url" => $url,
        ]);
        Segment::flush();
    }
}
