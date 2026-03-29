# Calendar Widget

The Calendar Widget displays upcoming (or past) events from shared Nextcloud calendars on your intranet pages. Events are shown with colored date badges, support recurring events, and the layout adapts automatically to the available space.

![Calendar Widget](../screenshots/calendarwidget-intro.gif)

*Calendar widget with colored date badges, responsive multi-column layout, and background themes*

## Features

- **Multi-calendar support** — Select multiple calendars for a merged view
- **Colored date badges** — Each event shows a date badge in the calendar's color
- **Recurring events** — RRULE patterns are automatically expanded into individual occurrences
- **Responsive grid** — 1, 2, or 3 columns depending on available space
- **Clickable events** — Click an event to open it in Nextcloud Calendar
- **Past events** — Show events from the past (past week, month, or 3 months)
- **Background themes** — None, Light, or Primary background with automatic contrast

## Layout

The widget uses a responsive grid that adapts to the container width using CSS container queries:

| Container width | Columns | Typical placement |
|----------------|---------|-------------------|
| < 300px | 1 column | Side column |
| 300-499px | 1 column | Narrow main content |
| 500-799px | 2 columns | Main content area |
| 800px+ | 3 columns | Wide content / full width |

No manual column configuration is needed — the layout adapts automatically.

## Configuration

### Adding the Widget

1. Click **+ Add Widget** in edit mode
2. Select **Calendar** from the widget picker
3. The editor opens automatically — select calendars and configure options
4. Click **Save** to apply

### Settings

| Setting | Description | Default |
|---------|-------------|---------|
| **Widget title** | Optional title above the widget | *(empty)* |
| **Background color** | None, Light, or Primary | None |
| **Calendars** | Checkbox list of available calendars | *(none selected)* |
| **Date range** | Time period to show events from | Upcoming (30 days) |
| **Number of events** | Maximum events to display (1-20) | 5 |
| **Show time** | Display event start/end time | Enabled |
| **Show location** | Display event location | Disabled |

### Date Range Options

**Future:**

| Option | Period |
|--------|--------|
| Upcoming (30 days) | Now → 30 days ahead |
| This week | Monday → Sunday of current week |
| Next 2 weeks | Now → 14 days ahead |
| This month | 1st → last day of current month |
| Next 3 months | Now → 3 months ahead |
| Next 6 months | Now → 6 months ahead |
| Next year | Now → 1 year ahead |

**Past:**

| Option | Period |
|--------|--------|
| Past week | 7 days ago → now |
| Past month | 30 days ago → now |
| Past 3 months | 3 months ago → now |

## Calendar Selection

The editor shows all calendars available to the current user, including:

- Personal calendars
- Calendars shared with the user directly
- Calendars shared with groups the user belongs to

Each calendar appears with its color dot and display name. Select multiple calendars for a merged view — events from all selected calendars are combined and sorted chronologically.

### Shared Calendar Setup

To make a calendar available in the widget:

1. Open **Nextcloud Calendar** app
2. Click the three-dot menu on a calendar → **Edit calendar**
3. Under **Share calendar**, add users or groups (e.g., "IntraVox Users")
4. The calendar will now appear in the widget editor for those users

## Event Display

Each event shows:

- **Date badge** — Day number and month abbreviation in a colored square (calendar color)
- **Proximity label** — "Today" or "Tomorrow" shown above the title when applicable
- **Event title** — Up to 2 lines, wraps instead of truncating
- **Time** — Start and end time, or "All day" for all-day events
- **Location** — Shown below time when enabled

### Clicking Events

Events are clickable and open the Nextcloud Calendar app on the day view for that date. This allows users to see full event details, edit the event, or view other events on that day.

> **Note:** On public shared pages (not logged in), events are not clickable since the Calendar app requires authentication.

### Recurring Events

Events with recurrence rules (RRULE) — such as weekly meetings or monthly reviews — are automatically expanded into individual occurrences within the selected date range. Each occurrence appears as a separate event with its correct date.

## Background Colors

The widget supports three background styles:

| Style | Appearance |
|-------|------------|
| **None** | Transparent, no padding |
| **Light** | Light gray background with padding |
| **Primary** | Primary theme color (e.g., dark blue) with white text |

Text colors automatically adjust for proper contrast on each background.

## Tips

- **Calendar colors**: Use distinct colors in Nextcloud Calendar for easy visual distinction in merged views
- **Date range**: Shorter ranges (this week, 2 weeks) work well for busy calendars; longer ranges (3-6 months) for planning/overview pages
- **Event limit**: Show 5-10 events for sidebar widgets, up to 20 for main content areas
- **Recurring events**: Weekly meetings will show multiple occurrences — adjust the event limit accordingly
- **Shared calendars**: Create a dedicated "Organization Events" calendar shared with all users for company-wide events

## Screenshots

![Calendar Widget Layout](../screenshots/Calendarwidget-layout.png)

*2-column layout in main content with timed and all-day events*

![Calendar Widget Sidebar](../screenshots/Calendarwidget-sidebar.png)

*Compact 1-column layout in side column*

![Calendar Widget Editor](../screenshots/Calendarwidget-editor.png)

*Widget editor with calendar selection and date range options*

![Calendar Widget 3-Column](../screenshots/Calendarwidget-3col.png)

*Responsive 3-column grid in full-width row*

## Requirements

- IntraVox 1.1.0 or higher
- Nextcloud Calendar app installed
- At least one calendar with events visible to the current user
