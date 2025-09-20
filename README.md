# SimplySmartOS - Plugin Example OAuth2

## Overview

**This system plugin is itended for testing and educational purposes only!**\
The plugin uses the Joomla! OAuth2 framework package and enables users to authenticate via an external OAuth2 provider with ease for testing purposes.

## Key features
- Redirects users to an OAuth2 authorization endpoint and handles authorization code exchange.
- Configurable endpoints and client credentials. [TODO]
- Debug/logging mode for troubleshooting.

## Installation

1. Use the latest release version and install it via the Joomla backend as usual.
2. In the Joomla administrator, go to Extensions → Plugins.
3. Search for the plugin name (example: "sismosexampleoauth2") and enable it.
4. Configure the plugin as described below.

## Configuration

Open the plugin configuration in the Joomla admin and set the following values:

- Client ID — OAuth2 client identifier issued by the provider.
- Client Secret — OAuth2 client secret (keep this safe).
- Base URL — The base URL of the OAuth2 provider (e.g., https://github.com).
- Authorization Endpoint — URL where users are redirected to authenticate. [TODO]
- Token Endpoint — URL used to exchange the authorization code for tokens. [TODO]
- Redirect URI — The redirect is processed by the plugin via Ajax. The Authorization callback URL that you most likely also have to set in the provider settings is `https://[your-joomla-site.com]/?index.php&option=com_ajax&plugin=sismosexampleoauth2&format=raw`.\
   You have to replace `[your-joomla-site.com]` with your actual Joomla site domain.
- Debug / Logging — Enable detailed logs for troubleshooting.

<img width="1428" height="981" alt="image" src="https://github.com/user-attachments/assets/1f2761b7-fec7-477d-9c23-e66d67c74dc6" />

## Usage

When enabled, the plugin will intercept authentication-related routes and initiate the OAuth2 flow as configured.\
Typical flow:
  1. User clicks "Generate Token"
  2. User authenticates at the provider and consents to requested scopes.
  3. Provider redirects back to the site with an authorization code.
  4. Plugin exchanges the code for tokens and writes to the log file if enabled.

## Security & privacy

- Store client secrets securely and never commit them to version control.
- Use HTTPS for your Joomla site and for all OAuth2 endpoint interactions.
- Limit scopes to only the permissions required.

## Troubleshooting

- Error: "Invalid redirect URI" — Verify the redirect URI registered with the provider exactly matches the plugin's callback URL (including scheme and trailing slash).
- Error: "Invalid client" or 401 from token endpoint — Check client ID/secret and that the provider supports the grant type used (authorization code).
- Enable plugin debug/logging and review Joomla logs for detailed request/response traces.


## Support & contribution

This plugin is provided as a sample. For production use, review the code thoroughly, validate security practices, and adapt as needed. For issues or enhancements, inspect the plugin files and logs, then open issues or contribute fixes in this repository.

## Changelog

- 1.0.0 — Initial sample plugin with basic OAuth2 authorization code flow.
