# Protecting and Licensing Your WordPress Plugin

Since WordPress is built on the GPL (General Public License), any code that interacts with WordPress core must also be GPL-compliant. This means users technically have the right to study, modify, and redistribute your code. However, you can still protect your business and revenue.

---

## 1. The Best Protection: "Support & Updates"
In the WordPress world, you aren't just selling code; you are selling **peace of mind**.
-   **License Keys**: Use a license key to gate access to your automatic update server. Users can use "nulled" versions, but they won't get security patches or new features without a key.
-   **Priority Support**: Agency owners cannot afford downtime. Make it clear that only licensed users get help from your expert team.

---

## 2. Technical Protection Methods

### **A. Licensing Servers (The Industry Standard)**
Connect your plugin to a remote server (like the provided **Agency Nexus Store** plugin).
-   *How it works*: On activation, the plugin sends the site URL and key to your server. Your server checks the database and records the activation.
-   *The Benefit*: You can enforce site limits. For example, our **Pro** tier is limited to 1 site, while the **Agency VIP** tier allows unlimited site activations.

### **B. Obfuscation (The "Hard" Method)**
If you have proprietary logic you don't want competitors to read:
-   **IonCube / SourceGuardian**: These tools "encrypt" your PHP files.
-   **Drawback**: This is controversial in the WP community and requires a special PHP extension to be installed on the user's server (which many shared hosts don't support).

### **C. The SaaS Hybrid Approach**
Move the most valuable logic to your own server (API-based).
-   *Example*: Instead of calculating ROI in the plugin, send the raw data to your server via API, calculate it there, and return the result.
-   *The Benefit*: They can't steal the logic because it's never on their server.

---

## 3. Tier-Based Feature Gating
Agency Nexus uses the `Agency_Nexus_License_Manager` class to dynamically enable or disable features based on the validated key.

### **Tier Comparison Matrix:**

| Module | Starter (Free) | Pro | Agency VIP |
| :--- | :---: | :---: | :---: |
| Project Management | **Enabled** | **Enabled** | **Enabled** |
| Client CRM | **Enabled** | **Enabled** | **Enabled** |
| Messaging Hub | **Enabled** | **Enabled** | **Enabled** |
| Content Calendar | **Enabled** | **Enabled** | **Enabled** |
| MoneyFlow (ROI) | Disabled | **Enabled** | **Enabled** |
| AutoPilot (Rules) | Disabled | **Enabled** | **Enabled** |
| Lead Intel (Embeds) | Disabled | **Enabled** | **Enabled** |
| White-Labeling | Disabled | Disabled | **Enabled** |
| Multi-Site Support | Disabled | Disabled | **Enabled** |

### **Technical Enforcement:**
-   **License Validation**: On activation, the plugin performs a `POST` request to your main domain store. The store returns the tier and site limit.
-   **Activation Tracking**: The `an_license_activations` table in your store database records every unique `site_url` to prevent Starter/Pro keys from being shared across multiple sites.

---

## 4. Setting Up Your Store (The Main Domain)
To sell Agency Nexus on your main domain, we have provided a companion plugin: **Agency Nexus Store & Licensing**.

### **Setup Instructions:**
1.  **Install**: Zip and install the `agency-nexus-store` directory on your main domain as a WordPress plugin.
2.  **Configure Pricing**: Go to **AN Store > Settings** and set your Pro and Agency tier prices.
3.  **Download Link**: Provide the URL where the Pro version of the plugin is hosted (e.g., your Amazon S3 link or local server path).
4.  **Display Pricing**: Use the `[an_pricing_table]` shortcode on your landing page to show the 3 pricing tiers.
5.  **Payment**: The checkout is pre-integrated with a simulated Stripe/PayPal flow. Users will enter their email and receive a generated license key and download link instantly after "paying."

### **Connecting the Plugins:**
-   On the client's site, go to **Agency Nexus > Settings**.
-   Enter your main domain URL in the **Main Domain Store URL** field.
-   Now, when the client enters their key in **Agency Nexus > Licensing**, it will validate instantly against your store's database.

---

## 5. Final Recommendation
Don't waste time fighting "piracy." Focus on making the Pro version so valuable and the updates so frequent that it's "cheaper" to pay for the license than to deal with a nulled version.
