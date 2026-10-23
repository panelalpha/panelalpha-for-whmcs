# Account synchronisation

Synchronisation imports services that exist in PanelAlpha into WHMCS, for example after you start using WHMCS with a panel that already has clients. It also finds WHMCS services whose PanelAlpha service no longer exists.

## Warning before you start

Synchronisation compares both systems. A service that is active in WHMCS but no longer exists in PanelAlpha is marked in the list, and if you select it, it is **deleted from WHMCS** when you finish. Check every selected row before you continue.

## Steps

1. Go to **System Settings → Servers**, find your PanelAlpha server and select **Sync Accounts**.
2. **Analyse**: the list shows the services found in PanelAlpha and in WHMCS. Select **Import/Sync** for the services you want, then **Continue**.
3. **Review**: check the services that will be created in WHMCS and choose the import settings:
   - **Send Client Welcome Email**: sends a welcome email to clients whose account is new in WHMCS. Choose the template and set it to **Yes**.
   - **Reset Service Account Passwords**: creates new passwords for the imported services.
   - **Send Service Welcome Email**: sends the product welcome email, for existing products only.
   - **Set Recurring Billing Information**: sets the billing from the chosen cycle, for existing products only.

   Then select **Continue**.
4. **Summary**: wait until the success message appears.

All services in PanelAlpha are listed, also when there are more than 100.

## Domain synchronisation

The domain of a service in WHMCS is filled in from PanelAlpha. This runs after the WHMCS cron, for services that do not have a domain in WHMCS yet, up to 25 services each time the cron runs. On an install with many new services it can take a few cron runs until all domains appear.
