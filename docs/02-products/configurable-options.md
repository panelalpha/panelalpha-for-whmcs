# Configurable options

Configurable options let a client choose part of their hosting on the order form, for example the number of sites or the server location. The module can create them for you from the PanelAlpha plan of the product.

## Generate the options

1. Open the product, then the **Module Settings** tab.
2. In **Configurable Options**, select **Generate Configurable Options**.

The module creates a configurable option group named `Configurable Options for <product name> product`, links it to the product, and adds one option for each value the PanelAlpha plan allows clients to change. Only those values appear. Selecting the button again updates the existing options instead of creating copies.

The button only creates the options. It does not set prices.

## Set the prices

1. Go to **System Settings → Configurable Options**.
2. Open the group named `Configurable Options for <product name> product`.
3. Set the price of each option for each billing cycle, as for any WHMCS configurable option.

## Options the module can create

Which of these you get depends on the plan.

| Area | Options |
|---|---|
| Location | Location (server or region) |
| Resources | Number of Sites, Space Quota, Disk Space Limit, Memory Limit, CPU Limit, MySQL Databases Limit, FTP Accounts Limit, SFTP Accounts Limit, Addon Domains Limit, Subdomains Limit, Inodes Limit, Bandwidth Limit |
| Performance | PHP Workers, PHP Memory Limit, Burst Up PHP Workers, PHP-FPM Pool Settings, LiteSpeed Settings, Redis Cache |
| Access and network | SSH Access, Dedicated IPv4, Dedicated IPv6, Read Rate Limit, Write Rate Limit |
| Plan and package | WHM Package, Package, Hosting Plan |

When the plan places new accounts on a random server, or on the server with the fewest accounts, the **Location** option lists the servers of the plan's server group. In other cases it is created with sample values that you replace with your own.

A number option (such as Number of Sites) is a field where the client types a whole number. A yes/no option (such as SSH Access) is a checkbox. The others are drop-down lists.

## Number of sites and trials

If the product has a **Number of Sites** option, also look at **Automatically set number of sites** in the product settings. See [Trial upgrades](trial-upgrades.md).
