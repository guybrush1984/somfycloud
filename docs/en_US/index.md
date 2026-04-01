# Somfy Cloud (Overkiz) Plugin

## Overview

This plugin lets you control your Somfy devices directly from Jeedom, using the **Overkiz cloud API** (the same servers used by the **TaHoma by Somfy** app on your phone).

### Who is this plugin for?

This plugin is designed for users with a Somfy gateway that **does not support the local API**:

- **Somfy Connexoon**
- **Somfy Connectivity Kit**
- Any Overkiz-compatible box where "Developer Mode" is not available

If your gateway supports the local API (TaHoma Switch, TaHoma v2 with Developer Mode enabled), consider using a local API plugin instead for better performance. This plugin is for cases where the local API simply isn't an option.

### What you can do

Everything you see in the TaHoma app on your phone can be controlled from Jeedom via this plugin: roller shutters, blinds, awnings, lights, garage doors, gates, and more.

### Good to know

This plugin communicates through Somfy's cloud servers. This means:

- **Internet required** — if your internet connection is down or Somfy's servers are unavailable, control is not possible
- **Slight delay** — commands take 1-3 seconds longer than local API control
- **State updates** — the plugin polls the servers every 5 minutes to fetch your devices' current state

---

## Installation

### Step 1: Install the plugin

1. In Jeedom, go to **Plugins > Plugin Management**
2. Click **Market** and search for **Somfy Cloud**
3. Install the plugin
4. Activate it

> **Manual install:** you can also copy the `somfycloud/` folder to `/var/www/html/plugins/` on your Jeedom server.

### Step 2: Configure your credentials

1. Go to the plugin configuration page (click **Somfy Cloud (Overkiz)** in plugin management)
2. Fill in the fields:
   - **Overkiz Server**: choose your region (Europe for France/EU)
   - **Email**: your Somfy Connect account email (same as in the TaHoma app)
   - **Password**: your Somfy Connect account password
3. Click **Test connection**

You should see a green message confirming the connection and the number of devices found.

> **Tip:** your Somfy Connect credentials are the ones you use to log in to the TaHoma by Somfy app on your phone. If you've forgotten them, use the "forgot password" feature at [www.somfy-connect.com](https://www.somfy-connect.com).

### Step 3: Import your devices

1. Still on the plugin configuration page, click **Sync devices**
2. The plugin will fetch all your devices from the Somfy cloud
3. A green message confirms how many devices were synced

### Step 4: View and use your devices

1. Go to **Plugins > Home automation protocol > Somfy Cloud (Overkiz)**
2. You'll see all your imported devices
3. Click on a device to view its commands and settings

To make a device appear on your Jeedom dashboard:

1. Click on the device
2. Choose a **Parent object** (the room where you want it to appear)
3. Check **Visible**
4. Click **Save**

---

## Daily Use

### Available commands

Depending on the device type, the plugin automatically creates the appropriate commands:

#### Roller shutters, blinds, screens, awnings

| Command | Description |
|---|---|
| **open** | Fully opens |
| **close** | Fully closes |
| **stop** | Stops current movement |
| **my** | Goes to the favourite position (the one programmed on your Somfy remote) |
| **Positionner** | Slider to choose a precise position (0% = open, 100% = closed) |
| **Etat position** | Shows the current position as a percentage |

#### Lights

| Command | Description |
|---|---|
| **on** | Turns on |
| **off** | Turns off |
| **Etat** | Shows current state (on/off) |

### Position: how does it work?

Position follows the Somfy standard:

- **0%** = fully **open** (shutter raised)
- **100%** = fully **closed** (shutter lowered)

For example, to close a shutter halfway, set the slider to **50**.

### Using in scenarios

You can use all commands from this plugin in your Jeedom scenarios. For example:

- Close all shutters at sunset
- Open the living room shutters at 7am
- Set an awning to 50% when the temperature exceeds 25C

---

## State Updates

The plugin automatically polls Somfy's servers every **5 minutes** to update your devices' state (shutter positions, light states, etc.).

> **Note about RTS devices:** devices using the RTS protocol (classic white Somfy remotes) are one-way only. The plugin can send commands to them but cannot know their current state. Only **IO Homecontrol** devices (with two-way communication) report their position back.

---

## Troubleshooting

### "Login failed" error

- Check that your email and password are correct (test them in the TaHoma app)
- Check that you selected the correct server (Europe for France/EU)
- Check that your Jeedom server has internet access

### No devices found

- Check that your devices appear in the TaHoma app on your phone
- Click **Sync devices** again

### Commands don't work

- Check that the device is enabled (**Activate** checkbox is checked)
- Check the logs: **Analysis > Logs > somfycloud**
- There may be a few seconds delay between sending a command and its execution

### States don't update

- States are refreshed automatically every 5 minutes
- RTS devices never report state (this is a protocol limitation, not a plugin issue)
- Check the logs to see if polling is working correctly

---

## FAQ

**Does the plugin work with the Connexoon?**
Yes, this is its primary use case. Since the Connexoon doesn't support the local API, this plugin uses the cloud API.

**Can I use this plugin with a TaHoma?**
Yes, the Overkiz cloud API works with TaHoma and TaHoma Switch too. But if your TaHoma supports the local API, a local plugin would be a better choice.

**My shutters don't report their state?**
If your shutters use the RTS protocol, they don't support state feedback. This is a hardware limitation, not a plugin issue.

**How many devices can I control?**
As many as you have in the TaHoma app. The plugin imports all visible devices.

**Is my password sent in plain text?**
No, the password is stored encrypted in Jeedom and transmitted to Somfy's servers over HTTPS (encrypted connection).
