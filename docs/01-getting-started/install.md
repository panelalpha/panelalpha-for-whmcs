# Install the module

Installing means copying the module files into your WHMCS. Nothing changes for your clients until you add a server and a product.

## Requirements

| | Supported |
|---|---|
| PHP | 8.1 or newer |
| WHMCS | 8.10 to 9.1 |
| WHMCS client area theme | Six or Twenty-One |
| PanelAlpha | A panel your WHMCS server can reach over the network, and an admin API token from it |

## Steps

1. Download the module: [latest release](https://github.com/panelalpha/panelalpha-for-whmcs/releases/latest), or the [main branch archive](https://github.com/panelalpha/panelalpha-for-whmcs/archive/refs/heads/main.zip).
2. Extract it and upload the `modules` folder into your WHMCS root folder, next to the `modules` folder that is already there. Let it merge with the existing folder.
3. Check that these two folders now exist on your WHMCS server:
   - `modules/servers/panelalpha/`
   - `modules/addons/panelalpha/`

The module is installed. Continue with [Connect WHMCS to PanelAlpha](connect-to-panelalpha.md).

## Troubleshooting

**PanelAlpha is not in the Module list when I add a server.**
The files are in the wrong place. The path must be `modules/servers/panelalpha/` inside your WHMCS root, not `modules/modules/...` or a folder named after the download.

**A blank page or a PHP error after upload.**
Check the PHP version WHMCS runs on. The module needs PHP 8.1 or newer.
