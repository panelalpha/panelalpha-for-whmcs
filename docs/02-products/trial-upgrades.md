# Trial upgrades

A client can start with a free trial in PanelAlpha and later pay for it in WHMCS. When the paid order is created, the module turns the trial into the paid service, so the client keeps their sites.

This needs [Direct Billing](../01-getting-started/direct-billing.md) with WHMCS, and a trial plan in PanelAlpha whose upgrade link points to your WHMCS order form.

## How it works

1. The client opens the upgrade link from PanelAlpha. It brings them to your WHMCS order form and remembers which trial they came from.
2. While they order, WHMCS checks the number of sites. The client cannot choose fewer sites than the trial already has. The number is read from PanelAlpha, not from the link.
3. When the order is paid and the service is created, the module moves the trial to the plan of the product and removes the trial status. The client's account and sites stay the same.

## The trial must belong to the client

The trial is upgraded only when the email of the trial owner in PanelAlpha is the same as the email of the client in WHMCS. Upper and lower case do not matter.

If the emails differ, the service is not created and you see a message that names the trial. The service's **Service ID** field is cleared, so the WHMCS service is not linked to someone else's trial. You then decide:

- **It is the same person.** Make the two emails the same, enter the trial number from the message in the **Service ID** field of the service, and run **Create** again. The trial is upgraded.
- **It is not the same person**, or you want a new service. Leave **Service ID** empty and run **Create** again. A new, empty service is created.

## Automatically set number of sites

When **Automatically set number of sites** is on for the product, the number of sites is set from what the client uses on the trial. If the product has one "sites" option, the client goes straight to checkout. Otherwise the field is filled in and locked.

## Troubleshooting

**"We couldn't verify the number of sites on your trial. Please try again."**
WHMCS could not read the trial from PanelAlpha while the client was ordering, so the order is held. Check that the PanelAlpha server in WHMCS still connects, then ask the client to try again.

**"Your trial service has more sites than the Number of Sites you selected."**
The client chose fewer sites than they already have. They need to choose at least that number, or remove sites in PanelAlpha first.
