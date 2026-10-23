# Products

A WHMCS product sells one PanelAlpha plan. When a client orders it, the module creates a PanelAlpha account for the client (or uses the one they already have) and a service on that plan.

## Create a product

1. Go to **System Settings → Products/Services** and create a product group if you do not have one.
2. Select **Create a New Product**, choose the group, enter the product name and choose **PanelAlpha** as the module. Turn off **Create as Hidden** if clients should see the product straight away.
3. Open the **Module Settings** tab and choose your PanelAlpha server group. The PanelAlpha settings load below.
4. Choose the **Plan**. **Plan Settings** then shows what the plan includes in PanelAlpha: instances limit, onboarding type, server type and group, DNS server and email server.
5. Set the options below that you need, then select **Save Changes**.

## Product settings

**Custom Server Configuration**
Settings for the hosting account, such as disk space or PHP workers. Values set here replace the ones in the PanelAlpha plan for this product only. Leave them empty to use the plan.

**Configurable Options**
Lets clients choose some of these values on the order form. See [Configurable options](configurable-options.md).

**Automatic Instance Provisioning**
Creates a WordPress site as soon as the service is created. Choose the **Default Instance Theme** and the **Default Instance Name**. With **Show Instance Name Field on Order Form**, the client types the site name when ordering.

**Manual termination (recommended)**
When this is on, terminating the service in WHMCS does not remove it from PanelAlpha. You remove it in PanelAlpha yourself. This protects client sites from a termination made by mistake.

**Panel Alpha SSO in main menu**
Adds a link to the client's PanelAlpha account in the main client area menu, for clients with an active service on this product.

**Automatically set number of sites**
For trial upgrades to a product with a "sites" configurable option. The number of sites is set from what the client already uses on the trial. See [Trial upgrades](trial-upgrades.md).

**Metric Billing**
Bills usage on top of the product price. Available metrics: active WordPress instances, remote backup size, disk usage, bandwidth and website visitors. Select **Configure Pricing** to set the prices in WHMCS.

## Domain field

The WHMCS **Require Domain** option is hidden for PanelAlpha products. Whether a client gives a domain when ordering depends on the onboarding type of the PanelAlpha plan.

## Troubleshooting

**Module Settings asks me to set up a connection to PanelAlpha.**
No PanelAlpha server exists yet, or the product has no server group. See [Connect WHMCS to PanelAlpha](../01-getting-started/connect-to-panelalpha.md).

**The plan list is empty or incomplete.**
Check the connection with **Test Connection** on the server. The module loads every plan from the panel, including when there are more than 100.
