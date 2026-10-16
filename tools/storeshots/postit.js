/* SPDX-License-Identifier: AGPL-3.0-or-later
 * Post It: floating sticky notes for Nextcloud. No build step, no external libraries. */
(function () {
	'use strict'
	if (window.__postit || window.top !== window.self) return
	window.__postit = true
	var OCg = window.OC || {}
	var uid = (document.head && document.head.getAttribute('data-user')) || (OCg.getCurrentUser && OCg.getCurrentUser().uid)
	if (!uid) return
	var t = function (s, v) { return window.t ? window.t('postit', s, v) : s.replace(/\{(\w+)\}/g, function (a, k) { return v && v[k] != null ? v[k] : a }) }
	function initial(key, d) {
		try {
			if (window.OCP && OCP.InitialState && OCP.InitialState.loadState) return OCP.InitialState.loadState('postit', key, d)
			var e = document.getElementById('initial-state-postit-' + key); return e ? JSON.parse(atob(e.value)) : d
		} catch (e) { return d }
	}
	var prefs = initial('prefs', { w: 280, h: 260, color: '#fff59d', fontSize: 15, pinned: false, palette: ['#fff59d', '#ffcc80', '#f8bbd0', '#b3e5fc', '#c8e6c9', '#e1bee7', '#ffffff', '#cfd8dc'] })
	function base() { return OCg.linkToOCS ? OCg.linkToOCS('apps/postit/api/v1', 2) : (OCg.webroot || '') + '/ocs/v2.php/apps/postit/api/v1/' }
	function rtoken() { return OCg.requestToken || (document.head && document.head.getAttribute('data-requesttoken')) || '' }
	function api(method, path, body, isForm) {
		var o = { method: method, credentials: 'same-origin', headers: { 'OCS-APIRequest': 'true', 'Accept': 'application/json', 'requesttoken': rtoken() } }
		if (body && !isForm) { o.headers['Content-Type'] = 'application/json'; o.body = JSON.stringify(body) } else if (body) o.body = body
		return fetch(base() + path + (path.indexOf('?') < 0 ? '?' : '&') + 'format=json', o).then(function (r) {
			if (!r.ok) throw new Error('HTTP ' + r.status)
			return r.json().then(function (j) { return j.ocs ? j.ocs.data : j })
		})
	}
	function el(tag, c, txt) { var e = document.createElement(tag); if (c) e.className = c; if (txt != null) e.textContent = txt; return e }
	function btn(c, txt, title, fn) { var b = el('button', c, txt); b.type = 'button'; if (title) { b.title = title; b.setAttribute('aria-label', title) } if (fn) b.addEventListener('click', fn); return b }
	function debounce(fn, ms) { var h; return function () { var a = arguments, s = this; clearTimeout(h); h = setTimeout(function () { fn.apply(s, a) }, ms) } }

	var notes = {}, open = {}, z = 10045, pop = null
	function closePop() { if (pop) { pop.remove(); pop = null; document.removeEventListener('pointerdown', outside, true) } }
	function outside(e) { if (pop && !pop.contains(e.target)) closePop() }
	function showPop(content, anchor, at) {
		closePop(); pop = el('div', 'postit-pop'); pop.setAttribute('role', 'menu'); pop.append(content); document.body.append(pop)
		var r = anchor ? anchor.getBoundingClientRect() : { left: at.x, right: at.x, bottom: at.y, top: at.y }
		var x = Math.min(r.right - pop.offsetWidth, window.innerWidth - pop.offsetWidth - 8), y = r.bottom + 4
		if (y + pop.offsetHeight > window.innerHeight - 8) y = Math.max(8, r.top - pop.offsetHeight - 4)
		pop.style.left = Math.max(8, x) + 'px'; pop.style.top = y + 'px'
		setTimeout(function () { document.addEventListener('pointerdown', outside, true) })
		var f = pop.querySelector('button'); if (f) f.focus()
	}
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePop() })

	function excerpt(n) {
		var d = document.createElement('div'); d.innerHTML = (n.content || '').replace(/(<\/(p|div|li|h[1-6]|blockquote)>|<br\s*\/?>)/gi, ' $1')
		var s = (d.textContent || '').replace(/\s+/g, ' ').trim()
		return s ? s.slice(0, 60) : (d.querySelector('img') ? t('Image') : t('Empty note'))
	}
	function save(n, fields) {
		return api('PUT', 'notes/' + n.id, fields).then(function (r) { notes[n.id] = Object.assign(n, r); return r }).catch(function () {})
	}
	function front(w) { w.style.zIndex = ++z }

	function upload(file) {
		var fd = new FormData(); fd.append('image', file)
		return api('POST', 'images', fd, true).then(function (r) { return r.url })
	}
	var EMOJI = ['😀', '😂', '😍', '🙂', '😉', '😎', '🤔', '😅', '👍', '👎', '👏', '🙏', '💪', '🎉', '🔥', '⭐', '✅', '❌', '⚠️', '❗', '❓', '💡', '📌', '📎', '📅', '⏰', '📞', '✉️', '❤️', '💰', '🚀', '☕']

	function setOpen(n, on) { if (!!n.open !== on) { n.open = on; api('PUT', 'notes/' + n.id, { open: on }).catch(function () {}) } }
	function openNote(n, opts) {
		opts = opts || {}
		if (open[n.id]) { front(open[n.id].w); return open[n.id] }
		if (!opts.restore) setOpen(n, true)
		var w = el('div', 'postit-note'); w.setAttribute('role', 'dialog'); w.setAttribute('aria-label', t('Note'))
		w.style.background = n.color || prefs.color
		// Restore the saved size and position, clamped so the note is fully on screen (shift first, shrink only if needed).
		var M = 8, vw = window.innerWidth, vh = window.innerHeight
		var W = Math.max(160, Math.min(n.w || prefs.w, vw - 2 * M)), H = Math.max(120, Math.min(n.h || prefs.h, vh - 2 * M))
		var x = n.x, y = n.y
		if (opts.center || (!x && !y)) { x = Math.round((vw - W) / 2); y = Math.round((vh - H) / 2) }
		x = Math.min(Math.max(M, x), Math.max(M, vw - W - M)); y = Math.min(Math.max(M, y), Math.max(M, vh - H - M))
		w.style.width = W + 'px'; w.style.height = H + 'px'; w.style.left = x + 'px'; w.style.top = y + 'px'
		var bar = el('div', 'postit-bar')
		var title = el('span', 'postit-title', '')
		var pin = el('span', 'postit-pin', n.pinned ? '📌' : ''); pin.title = t('Pinned')
		var shr = el('span', 'postit-pin postit-shr', ''); function setShr() { shr.textContent = n.shared ? '👥' : ''; shr.title = n.isOwner === false ? t('Shared by {name}', { name: n.ownerName }) : t('Shared') } setShr()
		var menuB = btn('postit-ib', '⋮', t('Note menu'))
		var closeB = btn('postit-ib', '✕', t('Close note'))
		bar.append(title, shr, pin, menuB, closeB)
		var body = el('div', 'postit-body'); body.contentEditable = 'true'; body.setAttribute('role', 'textbox'); body.setAttribute('aria-multiline', 'true')
		body.setAttribute('data-placeholder', t('Write something …')); body.innerHTML = n.content || ''
		body.style.fontSize = (prefs.fontSize || 15) + 'px'
		function cmd(c, v) { body.focus(); try { document.execCommand(c, false, v) } catch (e) {} saveContent() }
		var file = el('input'); file.type = 'file'; file.accept = 'image/png,image/jpeg,image/gif,image/webp'; file.hidden = true
		file.onchange = function () { if (file.files[0]) upload(file.files[0]).then(function (u) { cmd('insertImage', u) }, function () { alert(t('Could not upload the image')) }); file.value = '' }
		var tools = el('div', 'postit-tools')
		tools.append(
			btn('postit-ib', 'B', t('Bold'), function () { cmd('bold') }),
			btn('postit-ib', 'I', t('Italic'), function () { cmd('italic') }),
			btn('postit-ib', 'U', t('Underline'), function () { cmd('underline') }),
			btn('postit-ib', 'S', t('Strikethrough'), function () { cmd('strikeThrough') }),
			btn('postit-ib', 'H', t('Heading'), function () { cmd('formatBlock', 'h2') }),
			btn('postit-ib', '•', t('Bulleted list'), function () { cmd('insertUnorderedList') }),
			btn('postit-ib', '1.', t('Numbered list'), function () { cmd('insertOrderedList') }),
			btn('postit-ib', '🔗', t('Link'), function () { var u = prompt(t('Link address (https://…)')); if (u && /^(https?:\/\/|mailto:)/i.test(u)) cmd('createLink', u) }),
			btn('postit-ib', '😊', t('Emoji'), function (e) {
				var g = el('div', 'postit-emoji'); EMOJI.forEach(function (em) { g.append(btn('', em, em, function () { closePop(); cmd('insertText', em) })) })
				showPop(g, e.currentTarget)
			}),
			btn('postit-ib', '🖼', t('Insert image'), function () { file.click() }),
			btn('postit-ib', 'Tx', t('Clear formatting'), function () { cmd('removeFormat') }),
			file)
		w.append(bar, tools, body)
		document.body.append(w); front(w)
		var rec = { w: w, n: n, body: body, pin: pin, lastInput: 0 }
		open[n.id] = rec
		function setTitle() { title.textContent = excerpt({ content: body.innerHTML }) }
		setTitle()
		var saveContent = debounce(function () { setTitle(); save(n, { content: body.innerHTML }) }, 700)
		body.addEventListener('input', function () { rec.lastInput = Date.now(); saveContent() })
		// Remote changes from a shared note: never while the user is typing or a save is pending.
		rec.busy = function () { return Date.now() - rec.lastInput < 10000 } // typed in the last 10 s (the save is debounced 0.7 s)
		rec.apply = function (r) {
			if (r.content !== body.innerHTML) { body.innerHTML = r.content || ''; setTitle() }
			if (r.color && r.color !== w.style.background) w.style.background = r.color
			n.content = r.content; n.color = r.color; n.updatedAt = r.updatedAt; n.shared = r.shared; n.shareCount = r.shareCount; setShr()
		}
		rec.setShr = setShr
		body.addEventListener('paste', function (e) {
			var items = (e.clipboardData && e.clipboardData.items) || []
			for (var i = 0; i < items.length; i++) if (items[i].kind === 'file' && /^image\//.test(items[i].type)) {
				e.preventDefault(); upload(items[i].getAsFile()).then(function (u) { cmd('insertImage', u) }); return
			}
		})
		w.addEventListener('pointerdown', function () { front(w) })
		bar.addEventListener('pointerdown', function (e) {
			if (e.button !== 0 || e.target.closest('button') || w.classList.contains('postit-expanded')) return
			var r = w.getBoundingClientRect(), dx = e.clientX - r.left, dy = e.clientY - r.top
			bar.setPointerCapture(e.pointerId)
			function mv(ev) {
				w.style.left = Math.min(Math.max(0, ev.clientX - dx), Math.max(0, window.innerWidth - r.width)) + 'px'
				w.style.top = Math.min(Math.max(0, ev.clientY - dy), Math.max(0, window.innerHeight - r.height)) + 'px'
			}
			function up() { bar.removeEventListener('pointermove', mv); bar.removeEventListener('pointerup', up); saveGeom() }
			bar.addEventListener('pointermove', mv); bar.addEventListener('pointerup', up)
		})
		function geom() { var b = w.getBoundingClientRect(); return { x: Math.round(b.left), y: Math.round(b.top), w: Math.round(w.offsetWidth), h: Math.round(w.offsetHeight) } }
		function saveGeom() {
			if (w.classList.contains('postit-expanded') || !w.isConnected) return
			var g = geom()
			if (g.x === n.x && g.y === n.y && g.w === n.w && g.h === n.h) return
			Object.assign(n, g) // keep the local copy current so a reopen restores it even before the server answers
			return save(n, g)
		}
		var saveSize = debounce(saveGeom, 400) // resize end
		var firstObs = true
		if (window.ResizeObserver) new ResizeObserver(function () { if (firstObs) { firstObs = false; return } saveSize() }).observe(w)
		w.addEventListener('pointerup', function () { setTimeout(saveGeom, 50) }) // end of a native resize-handle drag
		rec.saveGeom = saveGeom
		closeB.onclick = function () { closePop(); saveGeom(); w.remove(); delete open[n.id]; setOpen(n, false) }
		menuB.onclick = function () { noteMenu(rec, menuB) }
		if (opts.focus) setTimeout(function () { body.focus() })
		return rec
	}
	function noteMenu(rec, anchor) {
		var n = rec.n, w = rec.w, m = el('div')
		var exp = w.classList.contains('postit-expanded')
		m.append(
			btn('postit-mi', (exp ? '🗗  ' : '⛶  ') + (exp ? t('Shrink') : t('Expand')), null, function () {
				closePop(); if (!exp && rec.saveGeom) rec.saveGeom(); w.classList.toggle('postit-expanded', !exp); front(w)
			}),
			btn('postit-mi', '📌  ' + (n.pinned ? t('Unpin') : t('PinIt')), null, function () {
				closePop(); n.pinned = !n.pinned; rec.pin.textContent = n.pinned ? '📌' : ''; save(n, { pinned: n.pinned })
			}))
		var h = el('div', 'postit-h', t('Color')); var sw = el('div', 'postit-sw')
		;(prefs.palette || []).forEach(function (c) {
			var b = btn(c === n.color ? 'postit-on' : '', '', c, function () { n.color = c; w.style.background = c; save(n, { color: c }); closePop() })
			b.style.background = c; sw.append(b)
		})
		m.append(el('hr'), h, sw,
			btn('postit-mi', '★  ' + t('Set as default color'), null, function () {
				closePop(); prefs.color = n.color; api('PUT', 'prefs', { prefs: { color: n.color } }).catch(function () {})
			}),
			el('hr'),
			btn('postit-mi', '🗒  ' + t('All notes'), null, function () { listMenu(anchor) }),
			btn('postit-mi', '👥  ' + (n.isOwner === false ? t('Shared by {name}', { name: n.ownerName }) : t('Share')), null, function () { closePop(); shareDialog(n) }),
			btn('postit-mi postit-danger', '🗑  ' + (n.isOwner === false ? t('Remove from my notes') : t('Delete')), null, function () {
				closePop()
				if (!confirm(n.isOwner === false ? t('Remove this shared note from your notes? The owner keeps it.') : t('Delete this note? This cannot be undone.'))) return
				api('DELETE', 'notes/' + n.id).then(function () { w.remove(); delete open[n.id]; delete notes[n.id] }, function () { alert(t('Could not delete the note')) })
			}))
		showPop(m, anchor)
	}
	function activeList() { return Object.keys(notes).map(function (k) { return notes[k] }).sort(function (a, b) { return b.updatedAt - a.updatedAt }) }
	function closeOpen(id) { if (open[id]) { open[id].w.remove(); delete open[id] } }
	function sharedTag(n) {
		if (!n.shared) return null
		var s = el('span', 'postit-shared', n.isOwner === false ? '👥 ' + n.ownerName : '👥')
		s.title = n.isOwner === false ? t('Shared by {name}', { name: n.ownerName }) : t('Shared with {n} people', { n: n.shareCount })
		return s
	}
	function refresh() {
		return api('GET', 'notes').then(function (d) {
			var seen = {}
			;(d && d.notes || []).forEach(function (r) { seen[r.id] = 1; if (notes[r.id]) { if (!open[r.id] || !open[r.id].busy()) Object.assign(notes[r.id], r) } else notes[r.id] = r })
			Object.keys(notes).forEach(function (k) { if (!seen[k]) { closeOpen(k); delete notes[k] } })
		})
	}
	function setArchived(n, on) {
		return api('PUT', 'notes/' + n.id, { archived: on }).then(function (r) { Object.assign(n, r); n.archived = !!on; if (on) closeOpen(n.id); return n })
	}
	function removeNote(n) {
		return api('DELETE', 'notes/' + n.id).then(function () { closeOpen(n.id); delete notes[n.id] })
	}
	// Right-click list. The edit toggle shows delete (no confirmation) and archive per row; archived rows stay greyed until the toggle goes off.
	function listMenu(anchor, at) {
		var m = el('div', 'postit-list'), edit = false, greyed = {}
		function render() {
			m.textContent = ''
			var head = el('div', 'postit-h postit-head')
			var ht = btn('postit-htitle', t('All notes'), t('Open the All notes panel'), function () { closePop(); notesPanel() })
			var sw = el('label', 'postit-toggle'); sw.title = t('Edit: delete and archive')
			var cb = el('input'); cb.type = 'checkbox'; cb.checked = edit; cb.setAttribute('aria-label', t('Edit: delete and archive'))
			cb.onchange = function () { edit = cb.checked; if (!edit) greyed = {}; render() }
			sw.append(cb, el('span', 'postit-slider'))
			head.append(ht, sw); m.append(head)
			var nr = el('div', 'postit-row postit-newrow')
			nr.append(btn('postit-mi', '＋  ' + t('New note'), null, function () { closePop(); newNote() }),
				btn('postit-rb', '↗', t('Open all notes'), function () { closePop(); openAll() }),
				btn('postit-rb', '↙', t('Close all open notes'), function () { closePop(); closeAll() }))
			m.append(nr, el('hr'))
			var list = activeList().filter(function (n) { return !n.archived || greyed[n.id] })
			if (!list.length) m.append(el('div', 'postit-empty', t('No notes yet')))
			list.slice(0, 50).forEach(function (n) {
				var row = el('div', 'postit-row' + (n.archived ? ' postit-grey' : ''))
				var b = btn('postit-mi', null, null, function () { if (n.archived) return; closePop(); openNote(n) })
				var dot = el('span', 'postit-dot'); dot.style.background = n.color
				b.append(dot, el('span', 'postit-li', (n.pinned ? '📌 ' : '') + excerpt(n)))
				var tg = sharedTag(n); if (tg) b.append(tg)
				row.append(b)
				if (edit) {
					row.append(
						btn('postit-rb', n.archived ? '🔒' : '🔓', n.archived ? t('Unarchive') : t('Archive'), function () {
							var on = !n.archived
							setArchived(n, on).then(function () { if (on) greyed[n.id] = true; render() }, function () { alert(t('Could not archive the note')) })
						}),
						btn('postit-rb postit-danger', '✕', n.isOwner === false ? t('Remove from my notes') : t('Delete'), function () {
							removeNote(n).then(render, function () { alert(t('Could not delete the note')) })
						}))
				}
				m.append(row)
			})
			if (pop && pop.contains(m)) { // content width changed: keep the menu fully on screen
				var r = pop.getBoundingClientRect()
				if (r.right > window.innerWidth - 8) pop.style.left = Math.max(8, window.innerWidth - 8 - r.width) + 'px'
			}
		}
		render()
		showPop(m, anchor, at)
		refresh().then(function () { if (pop && pop.contains(m)) render() }, function () {}) // pick up notes shared since page load
	}
	// Open every active note at its saved size and position (openNote clamps each one on screen).
	function openAll() { activeList().filter(function (n) { return !n.archived }).reverse().forEach(function (n) { openNote(n) }) }
	// Close every open note, saving its position and size first.
	function closeAll() {
		Object.keys(open).forEach(function (id) { var r = open[id]; if (r.saveGeom) r.saveGeom(); r.w.remove(); delete open[id]; setOpen(r.n, false) })
	}
	function plain(html) {
		var d = document.createElement('div'); d.innerHTML = (html || '').replace(/(<\/(p|div|li|h[1-6]|blockquote)>|<br\s*\/?>)/gi, '$1\n')
		return (d.textContent || '').replace(/[ \t]+\n/g, '\n').replace(/\n{3,}/g, '\n\n').trim()
	}
	function iso(s) { return s ? new Date(s * 1000).toISOString() : '' }
	function exportCsv(which) {
		var rows = [['id', 'title', 'content', 'color', 'created', 'updated', 'owner']]
		activeList().filter(function (n) { return which === 'archived' ? n.archived : !n.archived }).forEach(function (n) {
			var txt = plain(n.content); rows.push([n.id, txt.split('\n')[0].slice(0, 80), txt, n.color, iso(n.createdAt), iso(n.updatedAt), n.ownerName || ''])
		})
		var csv = rows.map(function (r) { return r.map(function (v) { v = String(v == null ? '' : v); if (/^[=+\-@\t\r]/.test(v)) v = "'" + v; return '"' + v.replace(/"/g, '""') + '"' }).join(',') }).join('\r\n')
		var a = el('a'); a.href = URL.createObjectURL(new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' }))
		a.download = 'postit-' + which + '-' + (function (d) { return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2) })(new Date()) + '.csv'
		document.body.append(a); a.click(); setTimeout(function () { URL.revokeObjectURL(a.href); a.remove() }, 1000)
	}
	var panel = null
	function closePanel() { if (panel) { panel.remove(); panel = null } }
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && panel && !pop) closePanel() })
	// Centered panel: active notes left, archived right, CSV export top right.
	function notesPanel() {
		closePanel()
		panel = el('div', 'postit-overlay')
		var box = el('div', 'postit-panel'); box.setAttribute('role', 'dialog'); box.setAttribute('aria-label', t('All notes'))
		panel.append(box); document.body.append(panel)
		panel.addEventListener('pointerdown', function (e) { if (e.target === panel) closePanel() })
		function col(title, list, archived) {
			var c = el('section', 'postit-col'); c.append(el('h3', null, title + ' (' + list.length + ')'))
			var ul = el('div', 'postit-cl')
			if (!list.length) ul.append(el('div', 'postit-empty', archived ? t('No archived notes') : t('No notes yet')))
			list.forEach(function (n) {
				var row = el('div', 'postit-prow' + (archived ? ' postit-grey' : ''))
				var dot = el('span', 'postit-dot'); dot.style.background = n.color
				var main = btn('postit-pmain', null, archived ? null : t('Open'), archived ? null : function () { closePanel(); openNote(n) })
				main.append(dot, el('span', 'postit-li', excerpt(n)))
				var tg = sharedTag(n); if (tg) main.append(tg)
				main.append(el('span', 'postit-date', new Date(n.updatedAt * 1000).toLocaleDateString()))
				row.append(main)
				row.append(archived
					? btn('postit-rb', '↩', t('Unarchive'), function () { setArchived(n, false).then(render, function () { alert(t('Could not archive the note')) }) })
					: btn('postit-rb', '🔓', t('Archive'), function () { setArchived(n, true).then(render, function () { alert(t('Could not archive the note')) }) }))
				ul.append(row)
			})
			c.append(ul); return c
		}
		function render() {
			box.textContent = ''
			var top = el('div', 'postit-ptop'); top.append(el('h2', null, t('All notes')))
			var sel = el('select'); sel.setAttribute('aria-label', t('Export which notes'))
			;[['active', t('Active')], ['archived', t('Archive')]].forEach(function (o) { var op = el('option', null, o[1]); op.value = o[0]; sel.append(op) })
			var ex = el('div', 'postit-export'); ex.append(el('span', null, t('Export')), sel, btn('postit-pbtn', t('CSV'), t('Download CSV'), function () { exportCsv(sel.value) }))
			top.append(ex, btn('postit-rb', '✕', t('Close'), closePanel))
			var all = activeList()
			var cols = el('div', 'postit-cols')
			cols.append(col(t('Active'), all.filter(function (n) { return !n.archived }), false), col(t('Archived'), all.filter(function (n) { return n.archived }), true))
			box.append(top, cols)
		}
		render()
	}
	function newNote() {
		var W = prefs.w, H = prefs.h
		api('POST', 'notes', { content: '', color: prefs.color, w: W, h: H, x: Math.round((window.innerWidth - W) / 2), y: Math.round((window.innerHeight - H) / 2), pinned: !!prefs.pinned })
			.then(function (n) { notes[n.id] = n; openNote(n, { center: true, focus: true }) }, function () { alert(t('Could not create a note')) })
	}

	var launcher = btn('', '', t('Post It: click for a new note, right-click or long-press for all notes'))
	launcher.id = 'postit-launcher'
	launcher.append(Object.assign(el('span', 'postit-lock', '🔓'), { title: t('Unlocked: drag to move') }))
	// Quick click: new note. Quick right-click: notes list. Hold right button (or long-press) ~600 ms: unlock and drag.
	var hold = null, unlocked = false, suppressClick = false, grab = null
	function placeLauncher(x, y) {
		var s = launcher.offsetWidth || 34, M = 6
		x = Math.min(Math.max(M, x), window.innerWidth - s - M); y = Math.min(Math.max(M, y), window.innerHeight - s - M)
		launcher.style.left = Math.round(x) + 'px'; launcher.style.top = Math.round(y) + 'px'; launcher.style.right = 'auto'
		return { x: Math.round(x), y: Math.round(y) }
	}
	function applyLauncherPref() {
		if (prefs.launcherX >= 0 && prefs.launcherY >= 0) placeLauncher(prefs.launcherX, prefs.launcherY)
		else { launcher.style.left = ''; launcher.style.top = ''; launcher.style.right = '' }
	}
	window.addEventListener('resize', function () { if (launcher.style.left) { var r = launcher.getBoundingClientRect(); placeLauncher(r.left, r.top) } })
	launcher.addEventListener('click', function (e) { if (suppressClick) { suppressClick = false; e.preventDefault(); return } newNote() })
	launcher.addEventListener('contextmenu', function (e) { e.preventDefault() })
	launcher.addEventListener('pointerdown', function (e) {
		var isRight = e.pointerType === 'mouse' && e.button === 2, isTouch = e.pointerType !== 'mouse' && e.button === 0
		if (!isRight && !isTouch) return
		if (isRight) e.preventDefault()
		var r = launcher.getBoundingClientRect(); grab = { dx: e.clientX - r.left, dy: e.clientY - r.top, right: isRight }
		try { launcher.setPointerCapture(e.pointerId) } catch (x) {}
		clearTimeout(hold)
		hold = setTimeout(function () { hold = null; unlocked = true; closePop(); launcher.classList.add('postit-unlocked') }, 600)
	})
	launcher.addEventListener('pointermove', function (e) { if (unlocked && grab) placeLauncher(e.clientX - grab.dx, e.clientY - grab.dy) })
	function release(e, cancelled) {
		if (!grab) return
		var wasRight = grab.right; grab = null
		if (hold) { clearTimeout(hold); hold = null; if (wasRight && !cancelled) listMenu(launcher); return } // quick right-click
		if (unlocked) {
			unlocked = false; launcher.classList.remove('postit-unlocked'); suppressClick = true; setTimeout(function () { suppressClick = false }, 400)
			var r = launcher.getBoundingClientRect(), p = placeLauncher(r.left, r.top)
			prefs.launcherX = p.x; prefs.launcherY = p.y
			api('PUT', 'prefs', { prefs: { launcherX: p.x, launcherY: p.y } }).catch(function () {})
		}
	}
	launcher.addEventListener('pointerup', function (e) { release(e, false) })
	launcher.addEventListener('pointercancel', function (e) { release(e, true) })

	// Poll open shared notes about every 15 s; last write wins, active typing is never overwritten.
	setInterval(function () {
		if (document.hidden) return
		Object.keys(open).forEach(function (id) {
			var r = open[id]
			if (!r.n.shared || r.busy()) return
			api('GET', 'notes/' + id).then(function (d) { if (open[id] === r && !r.busy() && d.updatedAt >= r.n.updatedAt) r.apply(d) },
				function (e) { if (/404/.test(e && e.message)) { closeOpen(id); delete notes[id] } })
		})
	}, 15000)

	function shareDialog(n) {
		closePanel()
		panel = el('div', 'postit-overlay')
		var box = el('div', 'postit-panel postit-sharebox'); box.setAttribute('role', 'dialog'); box.setAttribute('aria-label', t('Share note'))
		panel.append(box); document.body.append(panel)
		panel.addEventListener('pointerdown', function (e) { if (e.target === panel) closePanel() })
		var top = el('div', 'postit-ptop'); top.append(el('h2', null, t('Share note')), btn('postit-rb', '✕', t('Close'), closePanel))
		var info = el('div', 'postit-sinfo', excerpt(n))
		var owner = n.isOwner !== false
		var input = el('input'); input.type = 'search'; input.placeholder = t('Search users …'); input.className = 'postit-sinput'; input.setAttribute('aria-label', t('Search users'))
		var results = el('div', 'postit-cl postit-sres'), cur = el('div', 'postit-cl'), seq = 0, data = { shares: [] }
		box.append(top, info)
		if (owner) box.append(input, results)
		box.append(el('h3', null, t('Shared with')), cur)
		function apply(d) {
			data = d; cur.textContent = ''
			n.shareCount = d.shares.length; n.shared = !owner || d.shares.length > 0; if (open[n.id]) open[n.id].setShr()
			var o = el('div', 'postit-prow'); o.append(el('span', 'postit-li', '👤 ' + d.ownerName + ' (' + t('owner') + ')')); cur.append(o)
			if (!d.shares.length) cur.append(el('div', 'postit-empty', t('Not shared yet')))
			d.shares.forEach(function (u) {
				var row = el('div', 'postit-prow'); row.append(el('span', 'postit-li', '👥 ' + u.name))
				if (owner) row.append(btn('postit-rb postit-danger', '✕', t('Remove {name}', { name: u.name }), function () {
					api('DELETE', 'notes/' + n.id + '/shares/' + encodeURIComponent(u.id)).then(apply, function () { alert(t('Could not remove the share')) })
				}))
				cur.append(row)
			})
		}
		var search = debounce(function () {
			var q = input.value.trim(), my = ++seq; results.textContent = ''
			if (!q) return
			api('GET', 'users?search=' + encodeURIComponent(q)).then(function (list) {
				if (my !== seq) return
				results.textContent = ''
				var have = {}; data.shares.forEach(function (s) { have[s.id] = 1 })
				list = list.filter(function (u) { return !have[u.id] })
				if (!list.length) results.append(el('div', 'postit-empty', t('No users found')))
				list.forEach(function (u) {
					var b = btn('postit-pmain', null, t('Share with {name}', { name: u.name }), function () {
						api('POST', 'notes/' + n.id + '/shares', { userId: u.id }).then(function (d) { input.value = ''; results.textContent = ''; apply(d) }, function () { alert(t('Could not share with this user')) })
					})
					b.append(el('span', 'postit-li', '＋ ' + u.name), el('span', 'postit-date', u.id))
					results.append(b)
				})
			}).catch(function () {})
		}, 250)
		input.addEventListener('input', search)
		api('GET', 'notes/' + n.id + '/shares').then(apply, function () { cur.append(el('div', 'postit-empty', t('Could not load shares'))) })
		if (owner) setTimeout(function () { input.focus() })
	}

	function start() {
		document.body.append(launcher)
		applyLauncherPref()
		api('GET', 'notes').then(function (d) {
			if (d && d.prefs) { prefs = d.prefs; if (!grab) applyLauncherPref() }
			;(d && d.notes || []).forEach(function (n) { notes[n.id] = n; if (n.pinned && !n.archived) openNote(n, { restore: true }) })
		}).catch(function () {})
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start()
})()
