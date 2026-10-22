# Version Changer

A simple Pelican Panel plugin, improved and maintained by **ItZRealMoritz**.

Switch your Minecraft server's software and version straight from the panel — no SFTP, no manual JAR uploads, no console commands.

> **Credits & takedown**
> This plugin is based on the original **Minecraft Version Changer** by
> [Y.Junks](https://hub.pelican.dev/author/1150215871642476667) on the Pelican plugin hub,
> licensed under the MIT License. It has only been improved here.
> The original author ([Y.Junks](https://hub.pelican.dev/author/1150215871642476667)) can,
> at any time, message me on Discord (**itzrealmoritz**) and ask me to take it down —
> and I will remove it.

---

## What it does

- Pick any software (Vanilla, Paper, Purpur, Fabric, Forge, NeoForge, Folia, Spigot, Quilt and more) and any version from a clean catalog.
- The plugin stops your server **gracefully** first (it never force-kills it), backs up your current JAR, downloads the new one and lines everything up for you.
- If the server was running, it starts it back up on the new version automatically.
- Works even if you don't run a queue worker — the install runs straight from the page.

## Good to know

- Your old JAR is kept as `server.jar.bak`, and your `eula.txt` is left alone.
- Want a fresh start? Tick **Clean install** in the dialog to wipe the server files before installing (off by default).
- Every change is listed under **Recent version changes** with a full log you can open.

## Requirements

- Pelican Panel **v1.0.0-rc.1** or newer.

## Install

1. Open your panel as an admin and go to **Admin → Plugins**.
2. Click **Import from file** and upload the `minecraft-version-changer.zip`.
3. Hit **Install** next to "Version Changer".
4. Run `php artisan optimize:clear` once, then open a Minecraft server and look for **Version Changer** in the sidebar.

## Settings (optional)

Add any of these to your panel `.env` if you want to tweak the defaults:

| Variable | Default | What it does |
| :--- | :--- | :--- |
| `VERSIONS_AUTO_STOP` | `true` | Stop the server gracefully before swapping files |
| `VERSIONS_STOP_TIMEOUT` | `180` | Seconds to wait for a clean stop before cancelling (never a kill) |
| `VERSIONS_AUTO_RESTART` | `true` | Start the server again after a successful change |
| `VERSIONS_KEEP_BACKUP` | `true` | Keep the old JAR as `server.jar.bak` |
| `VERSIONS_CLEAN_LIBRARIES` | `true` | Clear the old `/libraries/` folder to avoid version clashes |
| `VERSIONS_DOWNLOAD_TIMEOUT` | `600` | Max seconds to wait for the download |

---

Made with ❤️ by **ItZRealMoritz**.
