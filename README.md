<div align="center">

<p align="center">
  <img src="https://www.inbs.software/assets/img/logo-pa.svg" alt="PanelAlpha" width="200">
</p>

<h1>Your WHMCS store.<br>Your PanelAlpha panel. In sync.</h1>

<h3>
Connect billing and provisioning so you run WordPress hosting from WHMCS without juggling two systems.
</h3>

<h3>
<a href="#get-started"><b>Get started</b></a> ·
<a href="docs/README.md"><b>Documentation</b></a> ·
<a href="#support"><b>Support</b></a>
</h3>

<p>
<a href="#what-you-can-automate">Provisioning</a> ·
<a href="#what-you-can-automate">Client area</a> ·
<a href="#requirements">Requirements</a> ·
<a href="docs/02-products/trial-upgrades.md">Trials</a>
</p>

<p>
<a href="#requirements"><img src="https://img.shields.io/badge/PHP-8.1%2B-777bb4?logo=php&logoColor=white" alt="PHP 8.1+"></a>
<a href="#requirements"><img src="https://img.shields.io/badge/WHMCS-8.10–9.1-006687" alt="WHMCS 8.10–9.1"></a>
<a href="#requirements"><img src="https://img.shields.io/badge/themes-Six_%26_Twenty--One-333" alt="Six and Twenty-One"></a>
<a href="#license"><img src="https://img.shields.io/badge/license-Apache_2.0-0b7285" alt="Apache 2.0"></a>
</p>

</div>

---

## What is PanelAlpha for WHMCS?

**PanelAlpha for WHMCS** is the official integration between [WHMCS](https://www.whmcs.com/) and your [PanelAlpha](https://www.panelalpha.com/) panel. It is built for hosting providers and resellers who sell WordPress hosting through WHMCS. PanelAlpha handles the sites, plans and the customer control panel. WHMCS stays the place for clients, orders and invoices.

The download contains two parts that you set up once:

- the **server module**, which WHMCS uses to create and manage services in PanelAlpha,
- the **addon**, which PanelAlpha uses to show WHMCS invoices and usage to your clients (Direct Billing).

## What you can automate

### Admin area

- **Service lifecycle**: create, suspend, unsuspend, terminate and change package in step with WHMCS orders, with optional manual termination
- **Plans**: see what the PanelAlpha plan includes and override hosting settings per product
- **Configurable options**: generate WHMCS configurable options from the plan, such as number of sites or location
- **WordPress sites**: optionally create a site with a default theme and name when the service is created
- **Login as client**: open the client's PanelAlpha account, or their cPanel, DirectAdmin or Plesk account
- **Metric billing**: bill active WordPress instances, remote backup size, disk usage, bandwidth and visitors
- **Product addons**: sell PanelAlpha plugin and theme packages
- **Account synchronisation**: import services that already exist in PanelAlpha

### Client area

- One-click login to the PanelAlpha account, from the service page or the main menu
- Current usage on the service page
- Upgrade from a PanelAlpha trial to a paid service, keeping the sites

### General

- English and German, with WHMCS language overrides
- WHMCS themes **Six** and **Twenty-One**

## Get started

1. [Install the module](docs/01-getting-started/install.md) into your WHMCS.
2. [Connect WHMCS to PanelAlpha](docs/01-getting-started/connect-to-panelalpha.md) by adding your panel as a server.
3. [Set up Direct Billing](docs/01-getting-started/direct-billing.md) in the addon and in PanelAlpha.
4. [Create a product](docs/02-products/products.md) for each plan you sell.

The full guide is in [docs](docs/README.md).

## Requirements

| Requirement | Supported versions |
| --- | --- |
| PHP | **8.1** and newer |
| WHMCS | **8.10** through **9.1** |
| WHMCS themes | **Six**, **Twenty-One** |
| PanelAlpha | A panel your WHMCS server can reach, and an admin API token from it |

## Support

Need help? Use the [PanelAlpha contact page](https://manage.panelalpha.com/contact) or open an issue in this repository.

## License

PanelAlpha for WHMCS is open source under the **Apache License 2.0**. See [LICENSE](LICENSE) for the full text.
