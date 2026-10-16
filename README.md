# Post It for Nextcloud

Floating sticky notes on every Nextcloud page.

- A tilted yellow note under the user menu opens a **new note** in the middle of the screen
- **Right-click** the launcher (or use **All notes** in a note's menu) to reopen any note
- Switch on the toggle in the list header for quick **delete** (no confirmation) and **archive** per note; click the **All notes** header for a panel with active and archived notes side by side and **CSV export**
- **Share** from a note's ⋮ menu: search for users, add or remove them. Everyone edits the same content; position, size, pinned and archive stay personal
- **Hold the right mouse button** (or long-press on touch) on the launcher to unlock it and drag it anywhere; the position is saved per user
- Rich text: bold, italic, underline, strike-through, heading, lists, links, emoji, images (upload or paste)
- Drag by the top bar, resize each note; position, size, color and pin state are saved per note
- Menu: **Expand / Shrink**, **PinIt** (stays open over every app while you navigate; unpinned notes close on navigation), **Color** (palette and *Set as default color*), **Delete** (with confirmation)
- **✕** closes a note without deleting it

## Requirements
Nextcloud 32 to 35.

## Settings
- **Personal → Post It:** default size, color and font size, and whether new notes start pinned
- **Administration → Post It:** enable for all users (default) or only for selected groups

## Storage and security
- Notes live in the `oc_postit_notes` table (per user: content, color, x, y, w, h, pinned, timestamps)
- The OCS API (`/ocs/v2.php/apps/postit/api/v1/...`) requires a logged-in session and the `OCS-APIRequest` header (CSRF protection)
- Note HTML is sanitized on the server against an allow-list of tags, attributes and CSS properties; scripts, event handlers, external images and `javascript:` links are removed
- Images are stored in the app's data storage (appdata), max 5 MB, PNG/JPEG/GIF/WebP, and are served only to their owner

## Editor
A built-in light editor (contentEditable with a small toolbar). No build step or external libraries.

## Install (manual)
```
cp -r postit /var/www/html/custom_apps/
occ app:enable postit
```
To turn it off: `occ app:disable postit`

## Build
`make dist` creates `build/postit-<version>.tar.gz`. `make sign` adds the app-store signature.

## License
AGPL-3.0-or-later
