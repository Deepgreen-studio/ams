# EasyCarbs Support ↔ Compliance Workflow

Matches the EasyCarbs triage flowchart used for demo requests in AMS.

```mermaid
flowchart TD
  report[User reports issue]
  triage[Triage tagged by type and severity]
  personal{Involves personal data?}
  bugLife[Standard Support lifecycle]
  closed[Ticket closed reporter notified]
  escalate[Escalate to Compliance linked to original ticket]
  breach{Breach or near miss?}
  dpia[Privacy request or DPIA register for review]
  breachLog[Breach log opened 72h ICO clock]
  contained[Contained ICO notified if required]
  audit[All steps logged to shared audit log]

  report --> triage --> personal
  personal -->|No| bugLife --> closed --> audit
  personal -->|Yes| escalate --> breach
  breach -->|No| dpia --> audit
  breach -->|Yes| breachLog --> contained --> audit
```

## Demo request routing

| Demo request | Involves personal data? | Destination | AMS record |
|--------------|-------------------------|-------------|------------|
| Remove My Health Information | **Yes** | Support intake → **auto-escalate to Compliance** privacy request (`data_correction`). Not a breach/near miss. | Ticket + linked `privacy_requests` row |
| Temporarily Disable My Account | **No** (operational) | **Support only** standard lifecycle | Support ticket; customer may be suspended |

## Implementation

- Flag: `support_tickets.involves_personal_data`
- Link: `support_tickets.privacy_request_id` ↔ `privacy_requests.support_ticket_id`
- Service: `SupportComplianceRoutingService`
- Listener: `RoutePersonalDataTicketToCompliance` on `SupportTicketCreated`
- Keyword fallback detects health/privacy language; operational “disable my account” is excluded from Compliance escalation
