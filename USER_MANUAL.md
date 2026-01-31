# Agency Nexus - User Manual & Instructions

Welcome to **Agency Nexus**, the premium, all-in-one WordPress management suite designed exclusively for freelancers and agency owners. Agency Nexus integrates project management, content operations, client collaboration, and financial tracking into a single, intuitive dashboard.

---

## 🛠️ Quick Start: Exploring with Sample Data
If you are new to the plugin, you can quickly explore its features by seeding it with sample data.
1. Navigate to **Agency Nexus > Dashboard**.
2. Click the **Seed Sample Data** button in the welcome panel.
3. This will create:
    - A **Sample Client** account (WP User + Client Record).
    - A **Sample Team Member** account (WP User).
    - A **Sample Project** with pre-populated tasks and history.

---

## 1. Getting Started: Core Data Management

Before diving into the advanced features, you need to establish your agency's core data structure.

### 👥 Client Management (The Foundation)
Clients are the heart of your agency. Everything from projects to invoices is linked to a client record.
- **How to register**: Navigate to **Agency Nexus > Clients** and click **Add New**.
- **Best Practice**: Use the primary contact's email. If you want the client to have login access, create a standard WordPress user with the *same* email address. The system will automatically link them.

### 📁 Project Management (The Engine)
Projects house your tasks, time logs, and content plans.
- **Creating a Project**: Go to **Agency Nexus > Projects > Add New**.
- **Task Management**: Within any project's **View** page, you can create tasks and assign them to team members.
- **Dynamic Timeline**: Use the **Start Date** and **Due Date** fields for tasks to automatically populate the **Timeline Visualizer (Gantt Chart)** at the bottom of the page.
- **ROI Tracking**: Encourage your team to log their hours against specific tasks. This data flows directly into your **MoneyFlow** financial reports.

---

## 2. Sales & Onboarding

### 🚀 SmartOnboard: Standardizing Your Sales
Avoid "scope creep" by using the **Scope Builder**.
- **Interactive Scoping**: Define service types (SEO, Web Design) and scales (Small to Large) in the settings.
- **One-Click Projects**: Once you build a scope, click **Create Project** to instantly move the prospect into your production pipeline with pre-defined deliverables.

---

## 3. Content Operations & Approval

### 📅 ContentMatrix: The Master Calendar
Manage your agency's content output across all clients.
- **Visual Planning**: Use the **Content Calendar** to drag-and-drop content items into specific dates.
- **Platform Agnostic**: Track content for WordPress, Instagram, LinkedIn, or any custom platform.

### ✍️ ApprovalFlow: Getting Client Sign-off
No more chasing emails for approvals.
- **Client Portal**: Clients see a list of items marked as **Pending Approval**.
- **Audit Trail**: They can approve or request revisions with a single click, which updates the status in real-time on your calendar.

---

## 4. Communication & Collaboration

### 💬 ClientSync (Messaging Hub & File Sharing)
- Navigate to **Agency Nexus > Messages**.
- Select a client from the sidebar to open the chat window.
- **Messages**: Chat in real-time with your clients. You can delete individual messages.
- **Shared Files**: Use the "Shared Files" tab in the chat window to upload assets, deliverables, or contracts for your clients to download. Manage files by deleting them when no longer needed.

---

## 5. Time & Productivity

### ⏱️ TimeBlock Pro
- Navigate to **Agency Nexus > Time Blocking**.
- **Manage Blocks**: Add, edit, or delete your daily focus blocks.
- View your daily schedule divided into **Deep Work**, **Shallow Work**, **Meetings**, and **Breaks**.
- Check the **AI Suggestion** box for tips on optimizing your focus based on your cognitive load patterns.

### 🛡️ BurnoutGuard (Workload Monitoring & Health Check)
- View the widget on the main **Agency Nexus Dashboard** to monitor your **Workload Capacity**.
- **Health Check**: Navigate to **Agency Nexus > Health Check** to log your daily stress levels and mental well-being notes.
- Use the history table to track your burnout risk over time.

---

## 6. Financials & Growth

### 💰 MoneyFlow: Your Agency's Bottom Line
Track every dollar flowing in and out of your agency.
- **Invoicing**: Create professional, printable invoices directly from your projects.
- **Expense Tracking**: Upload receipts for software, outsourcing, or travel.
- **Profitability Intelligence**: The dashboard widget calculates your **True Profit** by subtracting labor costs (calculated from logged hours) and expenses from your project budgets.

### 📈 EngageTrack (Leads & Sentiment)
- View the widget on the main **Agency Nexus Dashboard**.
- **Lead Management**: Navigate to **Agency Nexus > Leads** to track names, emails, sources, and potential values.
- **Canned Responses**: Navigate to **Agency Nexus > Canned Responses** to save frequently used DM or email templates.
- **Sentiment Analysis**: Gauge brand health based on social interaction data.

---

## 7. Configuration & API

### ⚙️ Global Settings
- Navigate to **Agency Nexus > Settings**.
- **Hourly Rate**: Set your agency's default hourly rate for ROI calculations.
- **API Keys**: Configure your Stripe Secret Key and Zapier Webhook URL for external integrations.

### 📱 Mobile App API
- Agency Nexus provides a built-in REST API for custom mobile app development or integrations.
- **Endpoints**: `/wp-json/agency-nexus/v1/projects`, `/tasks`, and `/messages`.

## 8. Automation & Resources

### 🤖 AutoPilot (Automation Center)
- Navigate to **Agency Nexus > Automations**.
- **Automation Rules**: Create custom IFTTT rules (e.g., IF Project Completed THEN Send Email).
- Manage your rules by activating/deactivating, editing, or deleting them.
- Configure your **Zapier/Make.com Webhook URL** for external integrations.

### 📚 FreebieFactory (Resource Library)
- Navigate to **Agency Nexus > Resource Library**.
- **Manage Assets**: Add your own contract templates, SOWs, and marketing swipe files.
- **File Support**: Upload PDFs, Docs, or Images directly to the library for easy access.

---

## 💡 Best Practices for Effectiveness
1. **Log Time Daily**: Accurate time logging is the engine that drives MoneyFlow and BurnoutGuard.
2. **Use the Scope Builder**: Standardize your pricing tiers to avoid scope creep.
3. **Batch Content**: Create all your content for the week in ContentMatrix and drag them to their respective dates in one go.
4. **Monitor Capacity**: If BurnoutGuard hits red, use the Resource Library (FreebieFactory) to find referral or overflow contract templates.

---

## 9. Roles & Permissions

Agency Nexus utilizes a tiered permission system to ensure data security and a tailored user experience.

- **Administrator**: Full access to all systems, global settings, and data management. Can see and manage everything across the agency.
- **Team Member (Editor/Author)**: Access to internal agency tools (MoneyFlow, AutoPilot, Messaging) but strictly limited to **assigned projects and tasks**. They cannot see or manage data from projects they are not part of.
- **Client (Subscriber/Linked Email)**: Access to a focused "Client Portal" view containing only their specific projects, messages, content approvals, and a shared resource library.

## 💡 Best Practices for Effectiveness

1.  **Log Time Daily**: Accurate time logging is the engine that drives **MoneyFlow** profitability reports and **BurnoutGuard** capacity monitoring.
2.  **Use the Scope Builder**: Standardize your pricing tiers and deliverables to avoid "scope creep" and improve project predictability.
3.  **Batch Content**: Use the **ContentMatrix** calendar to plan and schedule an entire week's worth of content in one sitting, then drag items to their final dates.
4.  **Monitor Capacity**: If your **BurnoutGuard** widget enters the "Red" zone, use the **FreebieFactory** to find referral or outsourcing contract templates to handle overflow.
5.  **Centralize Communication**: Encourage clients to use the **Messaging Hub** instead of email to maintain a single, searchable source of truth for every project.

---
*Built for Agencies, by Agencies.*
