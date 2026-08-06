# Support Agent Guide

For support agents (`support-agent` / `support-manager`).

## Daily workflow

1. Open **Support** → **Queue** or **Kanban**  
2. Claim or accept assignment  
3. Reply (public) or leave an **internal** note  
4. Update status: In Progress → Waiting for Customer → Resolved → Closed  
5. Watch SLA timers on the ticket detail page  

## Creating tickets

Use **Create** when logging phone/email issues for a company/customer. Choose category carefully — **Emergency Support** upgrades priority to Emergency.

## Assignment

| Type | When to use |
|------|-------------|
| Manual / Agent | You know who should own it |
| Auto | Round-robin to available agents |
| Team / Department | Route without picking a person yet |

## Conversation tips

- **Public** — customer-visible (also used by portal customers)  
- **Private** — restricted staff audience  
- **Internal** — agent notes; never shown in the customer portal  
- Attachments: images, docs, video (private storage; download via ticket UI)  
- Use **Canned** templates from the reply bar for greetings and standard asks  

## Knowledge Base

Before long replies, search `/support/knowledge`. Link customers to FAQs/guides. Mark articles Helpful/Not helpful after using them.

## Notifications

- Bell icon shows in-app alerts (assign, reply, SLA warning, escalation)  
- Adjust email vs in-app under `/notifications/preferences`  
- Clicking a notification opens the related ticket when linked  

## Status transitions

Invalid jumps (for example Closed → In Progress without reopen) are rejected by the API. Prefer **Reopen** for closed tickets.

## Portal awareness

Customers may submit and reply from `/portal`. You will see their messages as author type **customer**, visibility **public**. Do not put sensitive internal commentary in public replies.

## Permissions reminder

| Need | Permission |
|------|------------|
| View tickets / KB | `support.view` |
| Create tickets / reply | `support.create` |
| Update status / assign | `support.update` |
| Shared canned / SLA admin | `support.manage` |
