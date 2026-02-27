# Agency Nexus User Manual: Master Your Operations

Welcome to **Agency Nexus**, the ultimate Agency Operating System. This manual provides detailed instructions on how to set up and use every module in the system to maximize your efficiency and scale your agency.

---

## 🟢 1. Initial Setup & Global Configuration

Before diving into the modules, ensure your core settings are calibrated:

1.  **Installation:** Activate the plugin. Note that Agency Nexus creates several custom database tables to manage its advanced logic.
2.  **Licensing:** Go to **Nexus > Licensing**. Enter your key. This unlocks specific modules based on your tier (Starter, Pro, or VIP).
3.  **Branding:** In **Nexus > Settings**, upload your **Agency Logo**. This logo will automatically appear on all client invoices and proposals (VIP tier).
4.  **SMTP Configuration:** Under the "Email & SMTP" section in Settings, configure your mail server. This is critical for ensuring automated invoices and daily briefings reach their destination.
5.  **Demo Mode:** If you want to see how the system looks when fully operational, click **Add Best Sample Content** at the bottom of the Settings page.

---

## 📈 2. Sales & CRM (EngageTrack)

**EngageTrack** turns your agency into a lead-generation machine.

*   **Lead Capture:** Use the **Generate Embed Code** button on the Leads page. Copy the HTML to your marketing site. Leads will flow directly into your dashboard.
*   **Lead Scoring:** The system scores prospects (0-100). High values indicate a large budget or a warm source (like a referral). Focus your sales team here.
*   **Social Hub:** Link your Instagram, LinkedIn, X, and Facebook handles in **Social Settings**. The dashboard will aggregate engagement and flag "Priority Interactions" for immediate reply.
*   **Conversion:** When a prospect says "Yes," click **Convert to Client**. This creates a Client record and a WordPress 'Subscriber' user account simultaneously.

---

## 🤝 3. Onboarding & Deals (SmartOnboard)

Streamline the transition from "Lead" to "Active Project."

*   **Interactive Scope Builder:** Select a base service (SEO, Design, etc.) and toggle addons. The dynamic calculator updates the budget in real-time.
    *   *Sample Scope:* "High-Growth SEO" ($2,500 base) + "Backlink Outreach" ($500 addon) + "Technical Audit" ($750 addon) = **$3,750 Total**.
*   **Proposals:** After building a scope, generate a Proposal. Your client can view this on the frontend, review the terms, and provide a digital signature.
*   **Project Kickoff:** Once a proposal is signed, the system can automatically create an active Project in the database (via AutoPilot rules).

---

## 🚀 4. Delivery & Execution (Project Management)

The engine that drives your agency's fulfillment.

*   **Task Management:** Add tasks to any project. Use the **Briefs** field for detailed SOP instructions so your team knows exactly what to do.
*   **Dependencies:** Link tasks using the "Depends On" selector. This prevents "Task B" from being marked as completed before "Task A" is finalized.
*   **Buffer Management:** Set **Buffer Days** in Project settings. This adds a visual safety margin to your Gantt charts to protect against delays.
*   **Time Tracking:** Team members log hours against specific tasks. Admins can view and edit these entries to ensure labor costs are accurate.

---

## ✍️ 5. Content Operations (ContentMatrix)

Scale your content engine without the chaos.

*   **Pillar Architect:** Plan content in clusters. Mark high-level pieces as **Pillars** and link supporting cluster content to them.
    *   *Sample Pillar Map:* **Pillar:** "Agency Operations" -> **Clusters:** "Client Onboarding SOP", "Reducing Tool Fatigue", "Scaling Project Margins".
*   **Forecasting:** Each content item includes an engagement prediction based on historical performance and platform strength.
*   **Visual Calendar:** Drag and drop content items to reschedule. Syncs automatically with your team's workload.
*   **Batch Mode:** Use the Batch Automation tool to generate 10+ draft titles and skeletons for a project in a single click.

---

## ✅ 6. Client Sign-off (ApprovalFlow)

Eliminate "Email Ping-Pong" during the review process.

*   **Draft System:** Move content through stages (Idea, Draft, Review, Approved).
*   **Comparison Engine:** View version history side-by-side to see exactly what changed between revisions.
*   **Client Portal:** Clients see a simplified view of items awaiting their signature. They can approve or request changes with a single click.

---

## 💰 7. Financials & ROI (MoneyFlow)

Know exactly how much profit you're making on every project.

*   **True Profit Formula:** The system calculates: `Project Budget - (Team Hours * Hourly Rate) - Hard Expenses`.
*   **Invoicing:** Generate PDF-style invoices. You can add one-click **Late Fees (5%)** to any overdue invoice.
*   **Online Payments:** Clients can pay their invoices directly via Stripe or PayPal from the printable invoice view.
*   **Financial Reports:** View 6-month P&L trends and automated tax estimations (25%) in the Reports dashboard.

---

## 💬 8. Client Collaboration (ClientSync)

Secure, branded communication that stays inside your ecosystem.

*   **Communication Hub:** A real-time chat interface for all client interactions. No more lost emails.
*   **Shared Files:** A dedicated repository where you can upload deliverables and clients can upload assets (brand guides, etc.).
*   **Meeting Scheduler:** Integrated tool to book strategy sessions without leaving WordPress.

---

## ⏰ 9. Productivity (TimeBlock Pro)

Maximized focus for you and your team.

*   **Focus Mode:** Launch the Pomodoro timer during deep work sessions. The system can log these as "Focus Sprints" in your productivity report.
*   **Boundaries:** Set your "Communication Hours" in Settings. The system will automatically notify clients that you are away if they message you after hours.
*   **Capacity Reports:** See which team members are over-leveraged and who has room for new tasks.

---

## 🏗 10. Assets & IP (FreebieFactory)

Turn your agency's knowledge into a scalable asset.

*   **Internal Library:** Store your best contract clauses, email swipes, and SOP templates.
*   **Questionnaire Builder:** Create discovery forms to send to clients during onboarding.
*   **Marketplace:** Sell your frameworks or buy premium templates from the Agency Nexus community.

---

## 🤖 11. Automation (AutoPilot)

Put your agency on cruise control.

*   **Trigger Rules:** Create "If-This-Then-That" logic using JSON conditions.
    *   *Sample Rule:* `{"trigger": "project_completed", "condition": {"budget_min": 5000}, "action": "trigger_zapier"}`.
*   **Webhooks:** Connect to Zapier or Slack to push updates to your favorite external tools.
*   **Daily Briefing:** Every morning, the system sends an email to the admin with:
    1.  All tasks overdue or due today.
    2.  Leads captured in the last 24h.
    3.  Urgent social interactions.

---

## 🔒 12. Security & Compliance

*   **Encryption at Rest:** Enable "Encrypt internal notes" in **Security & Privacy**. This protects sensitive client data using AES-256-CBC encryption.
*   **GDPR Compliance:** Use the **Data Portability** tool to export a client's entire history (projects, invoices, messages) into a single JSON file.
*   **Audit Logging:** Monitor administrative actions to ensure the integrity of your agency's data.

---

## ❓ Frequently Asked Questions (FAQ)

**Q: Can I use Agency Nexus on a multisite network?**
A: Yes. The Agency VIP tier supports unlimited site activations across your entire network.

**Q: Does the plugin slow down my site?**
A: No. Agency Nexus is built with performance in mind. It uses custom tables and lazy-loading for data, ensuring your frontend marketing pages remain lightning fast.

**Q: How secure is my client data?**
A: Extremely. We use AES-256-CBC encryption for sensitive notes and descriptions. Only authorized users with the correct roles can access client data.

---

## 🛠 Troubleshooting & Support

1.  **Invoices Not Sending:** Ensure your SMTP settings are correct in **Nexus > Settings**. Send a test email to verify connectivity.
2.  **Shortcode Not Rendering:** Check your license tier in **Nexus > Licensing**. Some shortcodes (like the Marketplace) require a Pro or VIP key.
3.  **Media Upload Errors:** If you cannot upload files to the Shared Repository, ensure the `upload_files` capability is granted to your client user role (automatically handled for 'Subscriber' by the plugin).

**Need Priority Support?** Contact your Agency VIP account manager directly through the support portal.
