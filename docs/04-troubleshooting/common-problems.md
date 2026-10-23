# Troubleshooting

Problems you may meet, with the message you see and what to do. Problems with setup are also listed at the end of each setup page.

## Connection

**"Unauthenticated" after updating WHMCS, while Test Connection still works.**
WHMCS 9.1 and later stores the server API token encrypted. Module versions before 1.7.0 send it to PanelAlpha in that form, so every action except Test Connection fails: creating services, Login as user, suspending, the plan list on the product page. Update the module to 1.7.0 or later. You do not need a new token.

## Creating a service

**"A PanelAlpha user with this email already exists (user #ID) but is not linked to this WHMCS client."**
PanelAlpha already has an account with this client's email, for example from a trial or from before you used WHMCS, but no WHMCS service links to it. The module does not take over an account only because the email matches.
Check in PanelAlpha that the account belongs to this client. If it does, enter the number from the message in the **User ID** field of the service and run **Create** again. If it does not, change the email of one of the two accounts so they differ, then run **Create** again.

**"The "User ID" field of this service must be a positive number."**
Something other than a number was entered in **User ID**. Enter the account number from PanelAlpha, or leave the field empty.

**"PanelAlpha user #ID from the "User ID" field of this service was not found."**
The number in **User ID** does not exist on this PanelAlpha server. The module does not create a new account in this case. Correct the number, or clear the field to let the module create a new account.

**"Trial upgrade refused: PanelAlpha trial service #ID belongs to a different email than this WHMCS client."**
The client tried to upgrade a trial that is registered to another email. See [Trial upgrades](../02-products/trial-upgrades.md#the-trial-must-belong-to-the-client).

**"Service from PanelAlpha not found." when creating a product addon.**
The main service does not exist in PanelAlpha yet. Create the main service first.

## Billing and invoices

**WHMCS activity log entries about a "legacy panel".**
Your PanelAlpha version does not yet send who owns an invoice or a DNS zone. Everything works as before. See [Updating](../01-getting-started/updating.md#version-170).

**Usage is not updated.**
WHMCS could not read usage from PanelAlpha. Check the server with **Test Connection** and look at the WHMCS module log for the error.

## Cancelling from PanelAlpha

**A cancellation from PanelAlpha says "Service not found".**
WHMCS has no PanelAlpha product service with that PanelAlpha service number, or it is linked to another PanelAlpha server. Check the **Service ID** field of the service in WHMCS.

## Slow pages

**The order form, the admin area or the cron waits a long time.**
WHMCS is waiting for PanelAlpha. The module stops waiting after 10 seconds when it cannot connect and after 60 seconds when the panel does not answer, then shows an error. Check that the panel is up and reachable from the WHMCS server.
