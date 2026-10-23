# Updating

Updating replaces the module files with a newer version. Your servers, products and services in WHMCS stay as they are.

## Steps

1. Note your current version: in WHMCS go to **System Settings → Addon Modules** and read the version next to **PanelAlpha WordPress Hosting For WHMCS**.
2. Back up the `modules/servers/panelalpha/` and `modules/addons/panelalpha/` folders, so you can go back if needed.
3. Download the new version and upload its `modules` folder into your WHMCS root, overwriting the existing files.
4. Check the version again. It should show the new number.
5. Open one PanelAlpha product and one PanelAlpha service in the admin area to confirm they load.

Read the notes below for the version you install before you update.

## Version 1.7.0

- **PHP 8.1 is required.** Check the PHP version of your WHMCS before you update.
- **Works with the encrypted API token of WHMCS 9.1.** Older module versions fail with "Unauthenticated" on WHMCS 9.1 for every action except Test Connection. See [Troubleshooting](../04-troubleshooting/common-problems.md#connection).
- **Supported WHMCS versions are 8.10 to 9.1.**
- **Product module settings work again for plans with number limits.** The plan settings list, Advanced Mode and **Generate Configurable Options** could stop working when a plan had a number limit such as disk space.
- **More time to create a service.** Creating a service or a WordPress site waits up to 180 seconds for PanelAlpha. Other requests still stop after 60 seconds.
- **The "Manual Service Termination" email to the admin starts with "Hello,".** The module updates this line in the existing template the next time the WHMCS cron runs. Other changes you made to the template stay.
- **New messages when a service is created.** Creating a service now stops with a message in two situations where it used to continue silently:
  - a PanelAlpha account with the client's email already exists but is not linked to this client,
  - a trial being upgraded belongs to a different email than the client.

  The message tells you what to do. See [Troubleshooting](../04-troubleshooting/common-problems.md).
- **Ownership checks need a newer panel.** The module can check that an invoice or a DNS zone belongs to the client who asks for it, but only when PanelAlpha sends who that client is. Current PanelAlpha versions do not send it yet. Until they do, invoices and DNS work as before and the module writes a line about a "legacy panel" to the WHMCS activity log. You do not need to do anything. A later module version will require a PanelAlpha version that sends it, and the release notes will say which one.
