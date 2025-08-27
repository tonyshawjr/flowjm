# FlowJM - Journey Management in Motion

A solo-first journey management system that transforms freelance project tracking into a narrative-driven experience. Built for creatives who want to manage work through moments, not tasks.

## Context
- [REQUIREMENTS](./context/REQUIREMENTS.md) - What we're building and why
- [DESIGN](./context/DESIGN.md) - How we're building it
- [TASKS](./context/TASKS.md) - What's being worked on now
- [PLAN](./context/PLAN.md) - High-level roadmap and phases

## Overview

FlowJM reimagines project management for solo freelancers by:
- Replacing task lists with **Moments** - meaningful updates that tell your work story
- Organizing client work as **Journeys** instead of projects
- Providing a mobile-first, swipe-friendly interface that feels like social media
- Integrating payment tracking directly into project flow
- Creating a visual timeline of your creative career

## Key Features

### The Lookout (Dashboard)
Your daily command center showing:
- **Circle** - Priority journeys that need attention
- **Stacks** - Scrollable feed of all recent moments
- **Pulse Alerts** - Health indicators for overdue or blocked work

### Journeys
Transform projects into visual journeys with:
- Integrated payment and deadline tracking
- Stream of moments showing progress
- Pulse health checks for project status
- Fieldnotes for private thoughts

### Moments
Single-tap progress updates that build your narrative:
- Quick logging without forms
- Visual cards in your Stack feed
- Timestamps and context preserved
- Swipeable actions on mobile

## Tech Stack

- **Backend**: PHP 7.4+ (vanilla, no framework)
- **Database**: MySQL 5.7+
- **Frontend**: Vanilla JavaScript ES6+
- **CSS**: TailwindCSS 3.x (CDN)
- **Hosting**: cPanel shared hosting compatible
- **No build process required** - Direct deployment via FTP

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/tonyshawjr/flowjm.git
   ```

2. Upload files to your web server via FTP or cPanel

3. Create MySQL database and import schema:
   ```sql
   mysql -u username -p database_name < schema.sql
   ```

4. Configure database connection:
   ```php
   // config/database.php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

5. Navigate to your domain to begin setup

## Development

This project follows the CLAUDE.md development process. Key files:
- `CLAUDE.md` - Development process and team protocols
- `/context/` - Living documentation for the project
- `/.agent-os/` - Agent OS configuration and product docs

### Project Structure
```
/api          - AJAX endpoints returning JSON
/components   - Reusable PHP UI components
/includes     - Shared utilities and config
/assets       - CSS, JS, images
/context      - Project documentation
/.agent-os    - Agent OS product configuration
```

## Contributing

This is currently a solo project. For questions or collaboration:
- GitHub: https://github.com/tonyshawjr/flowjm
- Follow development in `/context/TASKS.md`

## License

Proprietary - All rights reserved

---

*FlowJM - Because your freelance career is a story in motion, not a spreadsheet of deadlines.*