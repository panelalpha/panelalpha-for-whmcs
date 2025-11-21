# DNS Manager Integration Guide

## Table of Contents
1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [DNS Manager Configuration](#dns-manager-configuration)
4. [PanelAlpha Configuration](#panelalpha-configuration)

---

## Overview

This document provides a comprehensive guide for integrating DNS Manager with PanelAlpha. The integration enables automated DNS zone management through PanelAlpha's interface, utilizing WHMCS DNS Manager as the backend DNS server.

---

## Prerequisites

Before beginning the integration setup, ensure the following requirements are met:

- **WHMCS Module**: 
  - PanelAlpha for WHMCS: https://github.com/panelalpha/panelalpha-for-whmcs/tree/integration/dns-manager
  - DNS Manager Module: Installed and configured in WHMCS
- **PanelAlpha Installation**: Active PanelAlpha instance
  - Required version: `1.5.1.1-dns-manager`

---

## DNS Manager Configuration

### Step 1: Configure Global Settings

Navigate to the DNS Manager global settings panel to enable custom IP address configuration.

1. Log in to your **WHMCS Admin Panel**
2. Navigate to **Addons** → **DNS Manager** → **Settings**
3. Locate the **Global Settings** section
4. Enable the **Custom IP Address** toggle:
   - Set the switch to **Enabled**
   - This allows DNS Manager to use custom IP addresses for nameserver records

---

### Step 2: Verify DNS Server Configuration

Ensure that a DNS server is properly configured in DNS Manager.

1. In DNS Manager, navigate to the **Servers** section
2. Verify that your DNS server is listed and configured

**Note:** If you already have a DNS server configured in DNS Manager, no action is required for this step. The server should be added and configured within the DNS Manager itself.

---

### Step 3: Configure Package Settings

Ensure that a package is properly configured for DNS zone provisioning.

1. Navigate to **DNS Manager** → **Packages**
2. Select your existing package (or create a new one if needed)
3. Verify the package configuration:
   - Ensure **Items Type** is set to **Other**
   - Confirm other settings as needed

**Note:** If you already have a package configured in DNS Manager, no action is required for this step. Simply verify that the **Items** → **Other** option is selected in your existing package configuration.

---

## PanelAlpha Configuration

### Step 4: Generate API Token

Before configuring PanelAlpha, you need to generate an API token from the PanelAlpha WordPress Hosting module.

1. In WHMCS Admin Panel, navigate to **Setup** → **Addon Modules**
2. Locate **PanelAlpha WordPress Hosting For WHMCS**
3. Click **Configure** button
4. In the configuration page, locate the **API Token** field:
   - If a token already exists, copy it
   - If no token is set, click **Generate new API token** button
5. **Copy the token** and store it securely

---

### Step 5: Create DNS Server in PanelAlpha

Configure PanelAlpha to connect to your DNS Manager instance.

1. Log in to **PanelAlpha Admin Panel**
2. Navigate to **DNS** → **DNS Servers**
3. Click **Add New DNS Server**
4. Fill in the required credentials:

**Required Fields:**

| Field | Description | Example |
|-------|-------------|---------|
| **Server Name** | Descriptive name for the DNS server | `DNS Manager` |
| **WHMCS URL** | Full URL to your WHMCS installation | `https://whmcs.example.com/whmcs` |
| **API Token** | Token generated from PanelAlpha WordPress Hosting module | `eyJ0eXAiOiJKV1QiLCJhbGc...` |

---

### Step 6: Configure DNS Server in Plan

Assign the DNS Manager as the DNS server for your hosting plans.

1. Navigate to **Plans** in PanelAlpha Admin Panel
2. Select the plan you want to configure (or create a new one)
3. In the plan settings, locate the **DNS Server** section
4. Select your newly created DNS Manager server from the dropdown
5. Save the plan configuration

**Note:** Once a plan is configured with DNS Manager as the DNS server, all new WordPress instances created on that plan will automatically have their DNS zones provisioned and managed through DNS Manager.
