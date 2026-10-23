# Set up Direct Billing

Direct Billing lets PanelAlpha use WHMCS as its billing system. Your clients see their WHMCS invoices in the PanelAlpha client area, can pay or download them from there, and usage is billed in WHMCS. It is also needed for trials that upgrade through WHMCS.

PanelAlpha talks to WHMCS through the addon part of the module. Only requests with the right API token, from addresses you allow, are accepted.

## Steps

1. In WHMCS go to **System Settings → Addon Modules** and activate **PanelAlpha WordPress Hosting For WHMCS**.
2. Select **Configure** on the addon and fill in:
   - **API Token**: a token is created for you on first save. **Generate new API token** replaces it. The old token stops working at once, so update PanelAlpha straight after.
   - **Allow From**: the IP address of your PanelAlpha panel. Separate several addresses with commas. `*` accepts requests from any address; use it only for a short test.
3. Save the addon settings and copy the API token.
4. In the PanelAlpha admin area go to **Configuration → Direct Billing**, choose **WHMCS**, enter the address of your WHMCS (for example `https://billing.example.com`) and paste the API token. Save.

Open an invoice from the PanelAlpha client area as a test client. The invoice should open or download.

## Troubleshooting

**PanelAlpha shows that WHMCS refused the request.**
Either the token in PanelAlpha is not the one saved in the addon, or the panel's IP address is missing from **Allow From**. If the panel sits behind NAT, allow the address WHMCS sees, which may be different from the panel's own address.

**Invoices and DNS work, but the WHMCS activity log has lines about a "legacy panel".**
Your panel does not yet send the owner of an invoice or DNS zone with its requests. The module works as before and writes these lines so you can see it. See [Updating](updating.md#version-170).
