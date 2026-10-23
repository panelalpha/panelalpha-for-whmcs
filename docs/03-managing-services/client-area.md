# Client area

Your clients manage their WordPress sites in PanelAlpha. The WHMCS client area gives them a one-click way in, without a second password.

## What clients see

- **WordPress Management** on the service page opens their PanelAlpha account, already logged in.
- **WordPress Management** in the main menu does the same, when **Panel Alpha SSO in main menu** is on for the product. See [Products](../02-products/products.md).
- **Metrics** on the service page shows their current usage: active WordPress instances, remote backup size, disk usage, bandwidth and website visitors.
- **Additional Information** shows the name of the WordPress site created with the service.

The button labels do not mention PanelAlpha, so you can use your own brand. You can change the wording with WHMCS language overrides.

## Troubleshooting

**"Unable to log in to the control panel. Please try again later or contact support."**
The login link could not be created. Most often the service has no WordPress site yet, or WHMCS cannot reach PanelAlpha. Check the server with **Test Connection**, and check the service in the admin area.
