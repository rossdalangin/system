# Agency Nexus - User Manual & Instructions

Welcome to **Agency Nexus**, the premium, all-in-one WordPress management suite designed exclusively for freelancers and agency owners. Agency Nexus integrates project management, content operations, client collaboration, and financial tracking into a single, intuitive dashboard.

---

## 📑 Table of Contents
- [🛠️ Quick Start](#🛠️-quick-start-exploring-with-sample-data)
- [⚙️ Phase 1: Setup & Configuration](#1-phase-1-setup--configuration)
- [🚀 Phase 2: Sales & Onboarding](#2-phase-2-sales--onboarding)
- [📁 Phase 3: Project Operations](#3-phase-3-project-operations)
- [📅 Phase 4: Content & Approval](#4-phase-4-content--approval)
- [💬 Phase 5: Communication & Collaboration](#5-phase-5-communication--collaboration)
- [💰 Phase 6: Financials & Growth](#6-phase-6-financials--growth)
- [⏱️ Phase 7: Productivity & Health](#7-phase-7-productivity--health)
- [🤖 Phase 8: Automation & Resources](#8-phase-8-automation--resources)
- [👥 Roles & Permissions](#-roles--permissions)
- [💡 Best Practices](#-best-practices-for-agency-success)
- [❓ FAQ & Troubleshooting](#-faq--troubleshooting)

---

## 🛠️ Quick Start: Exploring with Sample Data
If you are new to the plugin, you can quickly explore its features by seeding it with sample data.
1. Navigate to **Agency Nexus > Dashboard**.
2. Click the **Seed Sample Data** button in the welcome panel.
3. This will instantly create:
    - A **Sample Client** account.
    - A **Sample Team Member** account.
    - A **Sample Project** with tasks, time logs, and invoices.
    - **Leads**, **Canned Responses**, and **Resources** to play with.

---

## 1. Phase 1: Setup & Configuration

### ⚙️ Global Settings
Navigate to **Agency Nexus > Settings** to configure your agency's identity. This is the "brain" of your plugin.

#### **How to Configure:**
1.  **Agency Logo**: Upload your high-resolution logo (transparent PNG recommended). This appears on every invoice generated.
2.  **Hourly Rate**: Enter your standard billable rate. **MoneyFlow** uses this to calculate labor costs based on team time logs.
3.  **API Keys**:
    -   *Stripe*: Required for payment processing.
    -   *Zapier*: The global webhook for AutoPilot triggers.

### 👥 Client Management
Clients are the top-level entity. Everything (Projects, Invoices, Messages) belongs to a Client.

#### **Registration Workflow:**
1.  Go to **Agency Nexus > Clients > Add New**.
2.  Fill in the company details and the **Primary Email**.
3.  **Portal Access**: If you want the client to see their data:
    -   Create a WordPress User (Subscriber role).
    -   Use the **EXACT SAME email** as the Client record.
    -   Agency Nexus will automatically link them.

---

## 2. Phase 2: Sales & Onboarding

### 🚀 SmartOnboard: The Scope Builder
Standardize your sales process to prevent "scope creep" and ensure profitability.

#### **How to Build a Scope:**
1.  Navigate to **Agency Nexus > Scope Builder**.
2.  **Define Services**: Select service types (e.g., SEO, Web Design).
3.  **Select Scale**: Choose the project size (e.g., Small, Medium, Large).
4.  **Review Deliverables**: The system automatically populates tasks and costs based on your templates.
5.  **One-Click Conversion**: Once the client agrees, click **Create Project**. This instantly creates the project, assigns the Lead, and moves the scope into the production pipeline.

### 📈 EngageTrack: Lead Intelligence & Conversion
Turn prospects into clients with data-driven lead management.

#### **Lead Capture Workflow:**
1.  **Generate Embed Code**: On the Leads page, click the button to generate a custom HTML form.
2.  **Redirect URL**: Set where the user goes after submitting (e.g., a "Booking" page or your "Thank You" page).
3.  **Thank You Page Builder**: Use the `[agency_nexus_thank_you]` shortcode on any WordPress page.
    -   *Customization*: Edit the default title, offer, and CTA button text in **Agency Nexus > Lead Settings**.
    -   *Shortcode Overrides*: Use attributes like `product_name="SEO Package"` to override defaults.

#### **Canned Responses:**
-   Save frequently used snippets in **Agency Nexus > Canned Responses**.
-   These are accessible inside the **ClientSync Messaging Hub** for rapid customer support.

---

## 3. Phase 3: Project Operations

### 📁 Project Management (The Core Engine)
Track every moving piece of your agency's fulfillment.

#### **Operational Workflow:**
1.  **Create Project**: Link it to a Client and assign a **Project Lead** (Staff).
2.  **Task Management**: Within the project view, add tasks. Assign them to specific team members to distribute workload.
3.  **Logging Time**: Team members log hours directly against tasks. This data flows into **BurnoutGuard** (for workload) and **MoneyFlow** (for profitability).
4.  **Timeline Visualizer (Gantt)**: View the automated chart at the bottom of the project page.
    -   *Dependencies*: Ensure `start_date` and `due_date` are set for every task to generate an accurate visual timeline.

---

## 4. Phase 4: Content & Approval

### 📅 ContentMatrix: The Master Calendar
Plan, schedule, and visualize your agency's content output.

#### **Calendar Management:**
1.  **Drafting**: Create content items (Blog, Social, Newsletter).
2.  **Scheduling**: Drag-and-drop items onto the calendar to set their publishing date.
3.  **Project Sync**: Each content item is linked to a project, making it easy to see what's due for which client.

### ✍️ ApprovalFlow: Streamlining Client Sign-offs
Eliminate the "email ping-pong" of approvals.

#### **The Approval Cycle:**
1.  **Set to Pending**: When a draft is ready, change its status to "Pending Approval".
2.  **Client Portal**: The client logs in and sees a "Needs Review" notification.
3.  **Sign-off**: The client clicks **Approve** or **Request Revisions**.
4.  **Audit Trail**: Agency Nexus records the exact timestamp and user for every approval, creating a legal paper trail.

---

## 5. Phase 5: Communication & Collaboration

### 💬 ClientSync: Unified Communication Hub
Centralize all client communication and file exchanges.

#### **How to Communicate:**
1.  **Real-Time Chat**: Select a client from the sidebar to open the message thread.
2.  **Canned Responses**: Click the dropdown (Staff only) to insert pre-written templates.
3.  **File Sharing**: Use the "Shared Files" tab to upload contracts, assets, or deliverables.
    -   *Security*: Files are linked to the client's internal ID, ensuring only the intended client can download them via their portal.

---

## 6. Phase 6: Financials & Growth

### 💰 MoneyFlow: Your Agency's Bottom Line
Monitor profitability and manage accounts receivable.

#### **Invoicing System:**
1.  **Generate Invoice**: Select a project and click "Create Invoice".
2.  **Print & Send**: Invoices use your **Agency Logo** from settings. Click "Print" to save as PDF or send to the client.
3.  **Status Tracking**: Mark invoices as Sent, Paid, or Overdue.

#### **Profitability Intelligence:**
-   **Dashboard Widget**: View your **True Profit** at a glance.
-   **The Formula**: `Project Budget - (Logged Hours * Hourly Rate) - Uploaded Expenses = Profit`.
-   **Expense Tracking**: Upload receipts for software, outsourcing, or travel under the project's expense tab.

---

## 7. Phase 7: Productivity & Health

### ⏱️ TimeBlock Pro: Intelligent Productivity
Design your day for high-performance output.

#### **Manage Your Day:**
1.  **Define Blocks**: Set your "Focus Mode" blocks (Deep Work) vs "Shallow Work" (Emails/Admin).
2.  **AI Suggestions**: Check the suggestions box for tips like: *"Your cognitive load is highest at 10 AM. Schedule your Deep Work then."*
3.  **Focus Mode**: Use the built-in timer to stay on track during focus blocks.

### 🛡️ BurnoutGuard: Workload Monitoring
Ensure your agency remains sustainable by protecting your team from exhaustion.

#### **Sustainability Tracking:**
1.  **Workload Capacity**: The dashboard widget shows your team's current load vs their max capacity (based on logged hours).
2.  **Health Check-in**: Navigate to **Agency Nexus > Health Check** daily.
    -   Log stress levels and mental well-being notes.
    -   Track trends over time to identify if a project or client is causing undue stress.

---

## 8. Phase 8: Automation & Resources

### 🤖 AutoPilot: The Automation Center
Scale your agency by automating repetitive workflows using "Trigger & Action" logic.

#### **How to Set Up an Automation:**
1.  Navigate to **Agency Nexus > Automations**.
2.  Click **Add New Rule**.
3.  **IF This Happens (Trigger)**: Select an event (e.g., "Project Completed").
4.  **THEN Do This (Action)**: Select the response (e.g., "Send Email to Client").
5.  **External Integration**: Connect Zapier or Make.com in the sidebar to trigger workflows in 5,000+ other apps.

#### **Connecting External Apps (Zapier/Make.com)**
1.  In **Zapier**, create a new Zap with the "Webhooks by Zapier" trigger.
2.  Copy the **Webhook URL** provided by Zapier.
3.  Back in **Agency Nexus > Automations**, paste this into the **External Integration** sidebar and click **Update Settings**.
4.  **Is it Automatic?** Yes. You do *not* need to set up any external cron jobs or technical triggers. Agency Nexus handles everything automatically:
    -   *Instant Triggers*: (e.g., Project Completion, New Leads, Content Approval) fire the moment the event happens.
    -   *Background Triggers*: (e.g., Overdue Invoices) are checked once per day by the plugin's built-in scheduler.
5.  Now, any automation set to "Trigger Zapier Webhook" will send a data payload to that URL whenever the trigger event occurs.

### 📚 FreebieFactory: Resource Library
Maintain your agency's "Intellectual Property" and reusable assets.

#### **Resource Management:**
1.  **Templates**: Store standard contracts, SOWs, and questionnaires.
2.  **Swipe Files**: Keep a library of winning ad copy or email subject lines.
3.  **Central Access**: Your team can quickly copy/paste these assets into projects, saving hours of "starting from scratch".

---

## 👥 Roles & Permissions
Agency Nexus uses a strict tiered system to keep your data safe:
- **Administrator**: Full agency-wide access. Can manage Team, Settings, and Finances.
- **Team Member (Staff)**: Can only see and manage **assigned** projects, tasks, and messages. Access to agency tools is restricted to their work only.
- **Client**: Access to the "Client Portal" only. Limited to their specific projects, approvals, and shared files.

---

## 💡 Best Practices for Agency Success
1. **Log Time Daily**: This is the single most important habit. It powers your profit reports and health metrics.
2. **Standardize Scopes**: Use the Scope Builder for every lead to ensure you never undercharge again.
3. **Batch Your Content**: Plan the entire month in **ContentMatrix** in one sitting to save hours of context-switching.
4. **Move Conversations out of Email**: Use **ClientSync** for all client chat. This ensures everything is searchable and linked to the project.
5. **Monitor BurnoutGuard**: If your workload hits "Red", it's time to hire more help or increase your rates to reduce the number of clients.

---

## ❓ FAQ & Troubleshooting
- **Why can't my client see their project?** Ensure the client record email matches the email of their WordPress user account.
- **How do I change the currency?** Custom currency settings can be found in **Agency Nexus > Settings**.
- **Is my data encrypted?** Yes, all client messaging and financial data are handled with standard industry encryption protocols within your database.
- **How do I reset everything?** Go to the Dashboard and click **Clear Data** (Admin only).

---
*Built for Agencies, by Agencies.*
