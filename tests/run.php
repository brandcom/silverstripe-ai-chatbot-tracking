<?php

require dirname(__DIR__, 3) . "/vendor/autoload.php";
require dirname(__DIR__) . "/src/AIChatbotTrackingMiddleware.php";

use Brandcom\AIChatbotTracking\AIChatbotTrackingMiddleware;

$middleware = new AIChatbotTrackingMiddleware();

$signatures = [
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

foreach ($signatures as $signature) {
    assert($middleware->isAIBot("Example " . $signature . "/1.0"));
}

assert($middleware->isAIBot("gPtBoT/1.0"));
assert(!$middleware->isAIBot("Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36"));
assert(!$middleware->isAIBot("Claude/1.0")); // Only the configured Claude-User signature is accepted.

echo "AI chatbot tracking tests passed\n";
