# Changelog

## 1.2.0
- Compatibility: Nextcloud 32 to 35 (tested on 32.0.15, 33.0.10 and 35.0.2)
- Share a note with other users from its menu (user picker using the server's sharing rules; owners add and remove people). Recipients can edit content and color
- Position, size, open, pinned and archived are now per user (new table postit_state; existing values migrated). Shares live in postit_shares
- Shared notes show a 👥 badge with the owner's name in the list, the panel and the note bar
- Only the owner can delete a note; for a recipient, delete removes it from their own notes
- Open shared notes refresh about every 15 s unless you typed in the last 10 s (last write wins)
- Images inside shared notes are visible to everyone the note is shared with
- Notes, shares and state are removed when a user account is deleted
- API: GET /notes/{id}, GET/POST /notes/{id}/shares, DELETE /notes/{id}/shares/{userId}, GET /users?search=; PUT /notes/{id} accepts open

## 1.1.1
- Notes list: two buttons on the "New note" row. ↗ opens every active note at its last saved size and position (kept on screen); ↙ closes all open notes and saves their positions. Both have tooltips

## 1.1.0
- Notes can be archived (new `archived` column, migration Version1100). Archived notes never open on screen and are unpinned
- Notes list (right-click the launcher): an edit toggle at the right of the "All notes" header shows a delete X (no confirmation) and a lock (archive) on every row. Archived rows stay greyed until the toggle is switched off, then disappear; clicking the lock again unarchives
- Clicking the "All notes" header opens a centered panel: active notes on the left, archived on the right, archive/unarchive per row
- Export from the panel: pick Active or Archive and download a CSV (id, title, plain-text content, color, created, updated in ISO 8601 UTC)
- API: PUT /api/v1/notes/{id} accepts `archived`; notes carry `archived` in responses

## 1.0.2
- Launcher default position moved 15 px to the left
- Hold the right mouse button (or long-press on touch) for about 600 ms to unlock the launcher (wiggle, dashed outline, unlock badge), drag it anywhere, release to lock
- A quick right-click still opens the notes list
- The launcher position is saved per user on the server (follows you across devices), kept on screen, with "Reset launcher position" in Personal settings

## 1.0.1
- The editor now fills the full note width and is transparent, so it takes the note color in every palette color and in dark mode
- Notes reopen with their last size and position (saved on drag end, resize end and close), clamped to the window: shifted first, shrunk only if larger than the window
- Note titles in the list keep spaces between paragraphs

## 1.0.0
- First release: launcher, rich-text notes with emoji and images, drag and resize, Expand/Shrink, PinIt, colors with "Set as default color", delete with confirmation, All notes list
- Personal defaults (size, color, font size, start pinned); admin group restriction
- Server-side HTML sanitizing; OCS API; Nextcloud 30-34
