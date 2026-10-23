# Admin area

Open a client's PanelAlpha service in the WHMCS admin area to run module actions, log in as the client and see their usage.

## Module actions

- **Create**: creates the service in PanelAlpha, and the client's PanelAlpha account if they do not have one yet.
- **Suspend** and **Unsuspend**: suspends or restores the service in PanelAlpha.
- **Terminate**: removes the service from PanelAlpha. If the product uses **Manual termination**, WHMCS does not remove it and you remove it in PanelAlpha yourself.
- **Change Package**: moves the service to the plan of the new product.

## Log in as the client

- **Log in to PanelAlpha as User** opens the client's PanelAlpha account in a new tab.
- **Log in to Hosting Control Panel as User** appears when the plan uses cPanel, DirectAdmin or Plesk. It opens the client's hosting account on that server.

## How a service is linked to PanelAlpha

Each PanelAlpha service in WHMCS has two fields filled in by the module:

- **Service ID**: the number of the service in PanelAlpha.
- **User ID**: the number of the client's account in PanelAlpha.

When a client orders another service on the same PanelAlpha server, the module uses the **User ID** from their existing services, so all their services are in one PanelAlpha account. If the client has no linked account yet, a new one is created with the client's email, and WHMCS sends the client the **PanelAlpha Welcome New User Email** with their login details.

The module never links a client to an account only because the email matches. If a PanelAlpha account with the client's email already exists but is not linked, **Create** stops and tells you so. Check that the account belongs to this client, enter its number in the **User ID** field of the service and run **Create** again. See [Troubleshooting](../04-troubleshooting/common-problems.md).

Change these fields only when you are told to, or when you know which PanelAlpha account and service the WHMCS service belongs to. A wrong number gives the client access to another account.

## Usage

The service page shows the usage the module reads from PanelAlpha: active WordPress instances, remote backup size, disk usage, bandwidth and website visitors. With **Metric Billing** on the product, these values are billed.

A value of zero is a real reading and is billed as zero. If PanelAlpha cannot be reached, the usage update fails with an error, so old values are not kept without anyone noticing.

## Addon services

PanelAlpha product addons appear under the main service. You can **Create** and **Terminate** them. See [Product addons](../02-products/product-addons.md).

## PanelAlpha addon page

**Addons → PanelAlpha WordPress Hosting For WHMCS** lists all PanelAlpha services in WHMCS with their client and domain.
