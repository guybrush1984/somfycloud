# Somfy Cloud (Overkiz) — Jeedom Plugin

A Jeedom plugin to control Somfy devices through the **Overkiz cloud API**.

## Why this plugin?

Some Somfy gateways — notably the **Connexoon** and the **Connectivity Kit** — do not support Somfy's local "Developer Mode" API. The only way to control devices connected to these boxes programmatically is through the **Overkiz cloud API**, the same backend used by the TaHoma by Somfy mobile app.

This plugin gives you full control of everything you see in the TaHoma app, directly from Jeedom: roller shutters, blinds, awnings, lights, garage doors, and more.

### Trade-offs of the cloud approach

Because this plugin relies on Somfy's cloud servers, it comes with inherent limitations:

- **Internet dependency** — commands and state updates go through Somfy's servers. If your internet is down or Somfy's servers are unavailable, control is lost.
- **Latency** — commands take a bit longer to execute compared to local API control (typically 1-3 seconds).
- **Rate limits** — Somfy limits the command queue to 10 simultaneous executions. The plugin polls for state updates every 5 minutes to avoid overloading their servers.
- **Server outages** — during peak hours or maintenance, Somfy's servers may respond slowly or return errors. The plugin handles retries and re-authentication automatically.

If your gateway supports the local API (TaHoma Switch, TaHoma v2 with Developer Mode enabled), consider using a local API plugin instead for better reliability and speed. This plugin is for gateways where that option simply isn't available.

## Features

- Automatic device discovery — syncs all devices visible in the TaHoma app
- Control roller shutters, blinds, screens, awnings, lights, garage doors, gates, and more
- Position control with slider (0 = open, 100 = closed)
- Favourite position ("my") support
- Automatic state polling every 5 minutes
- Supports Somfy Europe, Australia, and North America servers
- Session caching with automatic re-authentication

## Requirements

- Jeedom 4.0 or higher
- A Somfy Connect account (the one used with the TaHoma by Somfy mobile app)
- A Somfy gateway: Connexoon, Connectivity Kit, TaHoma, or any Overkiz-compatible box

## Installation

### From GitHub

1. Copy the `somfycloud/` folder to your Jeedom plugins directory:
   ```bash
   cp -r somfycloud/ /var/www/html/plugins/
   ```

2. In Jeedom, go to **Plugins > Plugin Management** and activate **Somfy Cloud (Overkiz)**.

### Configuration

1. Go to the plugin configuration page.
2. Select your **server** (region): Europe, Australia, or North America.
3. Enter your **Somfy Connect email** and **password** (same credentials as the TaHoma app).
4. Click **Test connection** to verify your credentials.
5. Click **Sync devices** to import all your equipment.

Your devices will appear in **Plugins > Home automation protocol > Somfy Cloud (Overkiz)**, each with the appropriate commands for its type.

## Supported Device Types

| Device Type | Actions | State Feedback |
|---|---|---|
| Roller Shutter / Shutter / Screen / Awning | Open, Close, Stop, My, Position (slider) | Position (%) |
| Venetian Blind | Open, Close, Stop, My, Position, Orientation | Position (%), Orientation (%) |
| Light | On, Off | On/Off state |
| Garage Door / Gate | Open, Close, Stop, My, Position (slider) | Position (%) |
| Other / Unknown | Open, Close, Stop | -- |

**Note on RTS devices:** RTS protocol is one-way only. The plugin can send commands (open, close, etc.) but cannot read the current state. Only IO Homecontrol and similar bidirectional protocols provide state feedback.

## Position Convention

Following Somfy's standard:
- **0** = fully open
- **100** = fully closed

## License

AGPL-3.0 — Free and open source.
