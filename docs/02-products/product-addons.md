# Product addons

A product addon sells a PanelAlpha package, a set of plugins and themes, on top of a PanelAlpha service. When the client orders the addon, the package is added to their service. When the addon is terminated, the package is removed.

## Create an addon

1. Go to **System Settings → Product Addons** and select **Add New Addon**.
2. Choose **Independent Product** as the addon type, enter a name and choose **PanelAlpha** as the module.
3. Open the **Module Settings** tab and choose the **Package**. The page shows what the package does:
   - **Plugin automation on assign**: what happens to its plugins when the package is added (install and activate, only install, or nothing).
   - **Theme automation on assign**: what happens to its themes (only install, or nothing).
   - **Plugins included** and **Themes included**.
4. In **Applicable Products**, select the PanelAlpha products this addon can be ordered with.
5. Select **Save Changes**.

An addon can only be created on a service that already exists in PanelAlpha. Suspending and changing the package are not available for addons.
