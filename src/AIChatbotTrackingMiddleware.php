<?php

namespace Brandcom\AIChatbotTracking;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use BadMethodCallException;
use Throwable;

class AIChatbotTrackingMiddleware implements HTTPMiddleware
{
    use Configurable;

    private static array $bot_user_agent_substrings = [
        "GPTBot",
        "ChatGPT-User",
        "MistralAI-User",
        "Gemini-Deep-Research",
        "Claude-User",
        "Perplexity-User",
        "GoogleAgent",
        "Devin",
        "NovaAct",
        "Google-Extended",
        "Google-CloudVertexBot",
    ];

    private static string $matomo_url = "";

    private static int $site_id = 0;

    private static bool $enabled = true;

    private static int $timeout_milliseconds = 500;

    public function process(HTTPRequest $request, callable $delegate): HTTPResponse
    {
        $response = $delegate($request);

        if ($this->shouldTrack($request, $response)) {
            $this->track($request);
        }

        return $response;
    }

    public function shouldTrack(HTTPRequest $request, ?HTTPResponse $response = null): bool
    {
        if (!$this->getEnabled() || $request->httpMethod() !== "GET") {
            return false;
        }

        if ($response !== null && $response->getStatusCode() >= 400) {
            return false;
        }

        $url = trim($request->getURL(), "/");
        $excludedPrefixes = ["admin/", "Security/", "dev/", "api/", "assets/"];

        foreach ($excludedPrefixes as $prefix) {
            if (stripos($url, $prefix) === 0) {
                return false;
            }
        }

        return $this->isAIBot((string) $request->getHeader("User-Agent"));
    }

    public function isAIBot(string $userAgent): bool
    {
        foreach ($this->getBotUserAgentSubstrings() as $substring) {
            if ($substring !== "" && stripos($userAgent, $substring) !== false) {
                return true;
            }
        }

        return false;
    }

    private function track(HTTPRequest $request): void
    {
        $matomoUrl = $this->getMatomoUrl();
        $siteId = $this->getSiteId();

        if ($matomoUrl === "" || $siteId < 1) {
            return;
        }

        try {
            $tracker = new MatomoBotTracker($matomoUrl, $siteId, $this->getTimeoutMilliseconds());
            $tracker->track($this->getAbsoluteUrl($request), (string) $request->getHeader("User-Agent"));
        } catch (Throwable $exception) {
            try {
                Injector::inst()
                    ->get(LoggerInterface::class)
                    ->warning("AI chatbot Matomo tracking failed: " . $exception->getMessage());
            } catch (Throwable) {
                // Analytics must never prevent the requested page from being served.
            }
        }
    }

    private function getAbsoluteUrl(HTTPRequest $request): string
    {
        $scheme = $request->getScheme() ?: "https";
        $host = (string) $request->getHeader("Host");

        return $scheme . "://" . $host . "/" . ltrim($request->getURL(), "/");
    }

    private function getMatomoUrl(): string
    {
        return (string) (Environment::getEnv("MATOMO_TRACKING_URL") ?: static::config()->get("matomo_url"));
    }

    private function getSiteId(): int
    {
        return (int) (Environment::getEnv("MATOMO_SITE_ID") ?: static::config()->get("site_id"));
    }

    private function getEnabled(): bool
    {
        $value = Environment::getEnv("MATOMO_BOT_TRACKING_ENABLED");

        return $value === null ? (bool) static::config()->get("enabled") : filter_var($value, FILTER_VALIDATE_BOOL);
    }

    private function getTimeoutMilliseconds(): int
    {
        return max(1, (int) static::config()->get("timeout_milliseconds"));
    }

    private function getBotUserAgentSubstrings(): array
    {
        try {
            return (array) static::config()->get("bot_user_agent_substrings");
        } catch (BadMethodCallException) {
            // Keep pure user-agent checks usable before Silverstripe config bootstrapping.
            return self::$bot_user_agent_substrings;
        }
    }
}
