# Silverstripe AI chatbot Matomo tracking

This module records public frontend page views made by known AI chatbot agents in Matomo.

## Configuration

Set these environment variables in the runtime `.env`:

```dotenv
MATOMO_TRACKING_URL="https://example.matomo.cloud/matomo.php"
MATOMO_SITE_ID="1"
MATOMO_BOT_TRACKING_ENABLED="true"
```

Tracking is disabled in the project DDEV template by default. Set
`MATOMO_BOT_TRACKING_ENABLED` to `true` only when local traffic should be sent to
Matomo.

The middleware tracks successful public `GET` requests whose `User-Agent` contains
one of the configured AI chatbot signatures. Admin, security, development, API,
and asset paths are ignored. Matomo errors and timeouts never interrupt the page
request.

The default signatures can be overridden in Silverstripe YAML using the
`bot_user_agent_substrings` configuration property.

## Development

From the project root, use the DDEV web container for dependency and application
commands:

```shell
ddev composer install
ddev exec vendor/bin/phpstan analyse modules/ai-chatbot-tracking/src
ddev exec vendor/bin/phpcs modules/ai-chatbot-tracking
ddev exec php modules/ai-chatbot-tracking/tests/run.php
```
