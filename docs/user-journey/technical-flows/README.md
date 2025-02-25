# Technical Implementation Flows

## Authentication Flow
```mermaid
graph TD
    A[Homepage] --> B{Has Account?}
    B -->|No| C[Register]
    B -->|Yes| D[Login]
    C --> E[Email Verification]
    D --> F[2FA Check]
    E --> G[Homepage]
    F --> G[logout]
    G 
```

## Car Listing Flow
```mermaid
graph TD
    A[Search] --> B[Filter Results]
    B --> C[View Details]
    C --> D{User Logged In?}
    D -->|No| E[Prompt Login]
    D -->|Yes| F[Save/Contact]
```

## Meeting Scheduling Flow
```mermaid
graph TD
    A[Select Car] --> B[Choose Date]
    B --> C[Select Time]
    C --> D[Confirm Details]
    D --> E[Notification]
```

## File Structure
- /public/
  - Frontend entry points
  - Static assets
- /src/
  - Business logic
  - Models
- /data/
  - JSON storage
- /logs/
  - System logs
