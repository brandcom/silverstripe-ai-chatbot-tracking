<?php

namespace Brandcom\AIChatbotTracking;

use MatomoTracker;

class MatomoBotTracker
{
    public function __construct(
        private readonly string $matomoUrl,
        private readonly int $siteId,
        private readonly int $timeoutMilliseconds = 500,
    ) {}

    /**
     * @throws \Exception
     */
    public function track(string $pageUrl, string $userAgent): void
    {
        $tracker = new MatomoTracker($this->siteId, $this->matomoUrl);
        $tracker->disableCookieSupport();
        $tracker->sendImageResponse = false;
        $timeout = max(1, (int) ceil($this->timeoutMilliseconds / 1000));
        $tracker->setRequestTimeout($timeout);
        $tracker->setRequestConnectTimeout($timeout);
        $tracker->setUrl($pageUrl);
        $tracker->setUserAgent($userAgent);
        $tracker->setCustomTrackingParameter("recMode", 2);
        $tracker->doTrackPageView($pageUrl);
    }
}
