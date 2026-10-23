# Connect WHMCS to PanelAlpha

WHMCS talks to your panel through a server entry. You need the panel address and an admin API token from PanelAlpha.

## Before you start

Create the API token in the PanelAlpha admin area: **Configuration → Admins**, then the **Enable API Token** action on the admin account WHMCS should use. Copy the token. It acts with that admin's rights, so keep it private.

## Steps

1. In WHMCS go to **System Settings → Servers** and select **Add New Server**.
2. In **Module**, choose **PanelAlpha**. The form changes to the PanelAlpha fields.
3. Fill in:
   - **Name**: any name you will recognise.
   - **Hostname**: the address of your panel, for example `panel.example.com`, without `https://`.
   - **Protocol**: `https` for a normal panel.
   - **Port**: the port of the panel admin API. The default is `8443`.
   - **API Token**: the token you copied from PanelAlpha.
   - **Verify SSL Certificate**: keep it on. Turn it off only when the panel uses a self-signed certificate.
4. Select **Test Connection**. WHMCS should report that the connection works.
5. Save the server.
6. Select **Create Server Group**, give it a name, add the server you just created and save. Products are linked to a server group, not to a single server.

## Several panels in one WHMCS

You can add more than one PanelAlpha server. Each service stays linked to the panel it was created on. A client who has services on two panels has a separate PanelAlpha account on each of them.

## Troubleshooting

**Test Connection fails with a certificate error.**
The panel uses a self-signed certificate. Turn off **Verify SSL Certificate**, or install a trusted certificate on the panel.

**Test Connection fails with a timeout.**
WHMCS cannot reach the panel on that address and port. Check the hostname, the port and any firewall between the two servers. The module gives up after 10 seconds when it cannot connect, and after 60 seconds when the panel does not answer.

**Test Connection fails with an authorisation error.**
The API token is wrong or was disabled in PanelAlpha. Create a new one and paste it again.
