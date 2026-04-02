# Protection & Licensing: Securing Your Agency HQ

As the owner of **Agency Nexus**, your intellectual property (IP) is your most valuable asset. While WordPress is built on the GPL (General Public License), there are several strategies—both technical and business-oriented—to protect your revenue and ensure long-term sustainability.

---

## 1. The GPL Reality & Business Strategy
WordPress code must be GPL-compliant, meaning users have the right to modify it. However, the industry standard for protection is **"The Value of the Service."**

*   **Continuous Updates:** Users pay for the license to access your automatic update server. Without a valid key, they miss out on critical security patches and new feature modules.
*   **Expert Support:** Only licensed users get access to your "Agency Support Desk." For professional agencies, downtime is more expensive than a license fee.
*   **Cloud Dependency:** You can move certain complex logic (like AI-driven sentiment analysis or content forecasting) to a remote API that you control. This makes the plugin "functionally restricted" without an active connection to your server.

---

## 2. Technical Protection Architecture

### **A. Remote License Validation**
Agency Nexus is designed to communicate with your "Main Domain" using the provided **Agency Nexus Store** plugin.

*   **Handshake:** On activation, the plugin sends the Site URL and License Key to your store via a secure REST API endpoint.
*   **Validation:** Your store checks the `an_issued_licenses` table. If valid, it returns the `tier` (Starter, Pro, or VIP) and records the activation.
*   **Enforcement:** The plugin stores a secure transient locally. If the handshake fails or the key is suspended, Pro/VIP modules are instantly locked.

### **B. Tier-Based Feature Gating**
We use a granular capability system to gate features. Only the `Agency VIP` tier, for example, can remove the "Powered by Agency Nexus" branding (White-Labeling).

| Feature | Starter | Pro | VIP |
| :--- | :---: | :---: | :---: |
| CRM & Project Management | ✅ | ✅ | ✅ |
| ROI Tracker (MoneyFlow) | ❌ | ✅ | ✅ |
| AutoPilot Logic Rules | ❌ | ✅ | ✅ |
| White-Labeling & Branding | ❌ | ❌ | ✅ |
| Unlimited Multi-Site Support | ❌ | ❌ | ✅ |
| BurnoutGuard Team Health | ❌ | ❌ | ✅ |

---

## 3. Implementing Your Store (Main Domain Setup)

To start selling Agency Nexus, follow these steps on your primary marketing site:

1.  **Install the Store Plugin:** Upload and activate the `agency-nexus-store` folder as a plugin on your main domain.
2.  **Configure Gateway Credentials:** In **AN Store > Settings**, enter your live **Stripe** and **PayPal** credentials.
3.  **Set Tier Pricing:** Define your costs for the Starter ($0), Pro ($199/yr), and VIP ($999/lifetime) tiers.
4.  **Display the Pricing Table:** Create a "Pricing" page on your WordPress site and drop the `[an_pricing_table]` shortcode.
5.  **Fulfillment Automation:** The store plugin handles the entire checkout flow. Once a payment is confirmed:
    *   A unique license key is generated.
    *   The customer receives an automated email with their key and a download link.
    *   The transaction is logged in your **AN Store > Payments** dashboard.

---

## 4. Anti-Piracy Recommendations

1.  **Site Limits:** Use the store dashboard to monitor how many sites a single key has activated. If a "Pro" key (limited to 1 site) appears on dozens of domains, you can suspend it with one click.
2.  **Branded Footers:** In the free version, include a "Powered by Agency Nexus" link in the client portal. This acts as a viral marketing engine. Users must pay for the VIP tier to remove it.
3.  **The "Value-Add" Moat:** Don't waste energy fighting the GPL with complex obfuscation. Instead, focus on the **Marketplace**. If users get high-value templates, swipe files, and SOPs as part of their paid subscription, they will never want a nulled version.

---
**Technical Note:** Always ensure your main domain has a valid SSL certificate. The license validation requests use `wp_remote_post()` and require a secure connection to process correctly.
