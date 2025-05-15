# OTP Verification for Gravity Forms

![WordPress Plugin](https://img.shields.io/wordpress/plugin/v/gravity-otp-verification)
![Gravity Forms](https://img.shields.io/badge/Compatible%20with-Gravity%20Forms-blue)
![License](https://img.shields.io/badge/license-GPL--2.0-blue.svg)

**OTP Verification for Gravity Forms** ensures secure form submissions by verifying users’ mobile numbers via OTP before saving.

## 🚀 Features

- 🔒 **Secure Mobile Verification** – Ensures users verify their phone numbers before submitting.
- ✅ **Seamless Gravity Forms Integration** – Works with all versions of Gravity Forms without conflicts.
- 🌎 **Supports Persian, Arabic & English Numbers** – Converts and validates all number formats.
- 📡 **Flexible SMS Gateway Support** – Connects to multiple SMS providers via built-in integrations or custom hooks.
- ⚙️ **Easy Setup** – Configure in just a few clicks with user-friendly settings.

## 📌 Supported SMS Gateways

The plugin supports direct integration with the following SMS providers:

- **SMS.ir**
- **FarazSMS**
- **IPPanel**
- **Persian WooCommerce SMS**

Additionally, you can add **any other SMS gateway** via **WordPress hooks and filters**.

## 📖 How to Setup the Plugin

1. **Install & Activate** the plugin.
2. **Go to Gravity Forms** and create a form.
3. **Add the OTP Field** from the field settings.
4. **Configure your SMS Gateway** in plugin settings.
5. **Save your form**, and OTP verification will be active.

## 📷 Screenshots

1. **Gravity Form > OTP Field Setting**
2. **Settings > General**
3. **Settings > SMS Configuration**

## 🔧 Installation

1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the **WordPress plugins** screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Gravity Forms > Your Form > Add OTP Field**.
4. Configure your **SMS Gateway** in the plugin settings.
5. Save your form, and you're good to go!

## ❓ FAQ

### 🔹 How does OTP Verification work?
Once a user enters their mobile number, they receive an **OTP via SMS**. They must **enter the correct OTP** before submitting the form.

### 🔹 Can I use my own SMS provider?
Yes! The plugin supports **multiple SMS gateways**, and you can **add your own** via hooks.

### 🔹 Does this plugin support Persian & Arabic numbers?
Yes! The plugin **automatically converts** Persian and Arabic numerals to English before validation.

### 🔹 Does it work with all Gravity Forms versions?
Yes! It is tested and compatible with **all recent Gravity Forms versions**.

### 🔹 How can I contribute to this plugin?
You can contribute by submitting your changes to our **GitHub repository**:
➡️ [GitHub Repository](https://github.com/pigment-dev/gravity-otp-verification)

## 🎉 Credits


**OTP Verification for Gravity Forms** is developed and maintained by [BlackSwanDev](https://blackswandev.com/), in collaboration with [Pigment.Dev](https://pigment.dev/). Together, we focus on **creating powerful WordPress tools** for security and automation.

Developed at: **[BlackSwanDev](https://blackswandev.com/)** & **[Pigment.Dev](https://pigment.dev/)**

Lead Developer: **[AmirhpCom](https://amirhp.com/)**

## 🛡️ Disclaimer and Warranty

This plugin is provided **"as is"** without any warranties, express or implied. While every effort has been made to ensure reliability and security, the developers are not responsible for any issues arising from its use. Always test in a **staging environment** before deploying to production.

## 📜 License

This plugin is licensed under **GPLv2 or later**. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) for details.

## 🔥 Changelog

#### v2.7.0 | 2025-05-15 | 1404-02-25
- Added Persian WooCommerce SMS as Gateway
- Fix Log panel not loaded

#### v2.6.0 | 2025-04-30 | 1404-02-10
- Update WordPress version
- Fix GF-Panel not Loaded

#### v2.5.0 | 2025-04-02 | 1404-01-13
- General fixes and Enhancement

#### v2.4.0 | 2025-03-31 | 1404-01-11
- General fixes and Enhancement

#### v2.3.0 | 2025-03-20 | 1403-12-30
- Initial release of the plugin for w.org