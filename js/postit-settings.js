/* SPDX-License-Identifier: AGPL-3.0-or-later */
(function () {
	'use strict'
	var t = function (s) { return window.t ? window.t('postit', s) : s }
	function msg(id, ok) { var m = document.getElementById(id); if (m) { m.textContent = ok ? t('Saved') : t('Could not save'); m.className = 'msg ' + (ok ? 'success' : 'error') } }
	function req(method, url, body, ocs) {
		var h = { 'Content-Type': 'application/json', 'requesttoken': OC.requestToken }
		if (ocs) { h['OCS-APIRequest'] = 'true'; h.Accept = 'application/json' }
		return fetch(url, { method: method, credentials: 'same-origin', headers: h, body: JSON.stringify(body) }).then(function (r) { if (!r.ok) throw new Error(r.status); return r.json() })
	}
	function prefs(p) {
		var u = (OC.linkToOCS ? OC.linkToOCS('apps/postit/api/v1', 2) : OC.webroot + '/ocs/v2.php/apps/postit/api/v1/') + 'prefs?format=json'
		return req('PUT', u, { prefs: p }, true).then(function () { msg('postit-personal-msg', true) }, function () { msg('postit-personal-msg', false) })
	}
	function init() {
		var g = document.getElementById('postit-save-groups')
		if (g) g.addEventListener('click', function () {
			var v = Array.prototype.map.call(document.querySelectorAll('.postit-group:checked'), function (c) { return c.value })
			req('POST', OC.generateUrl('/apps/postit/admin/groups'), { groups: v }).then(function () { msg('postit-admin-msg', true) }, function () { msg('postit-admin-msg', false) })
		})
		var w = document.getElementById('postit-w'); if (!w) return
		var h = document.getElementById('postit-h'), f = document.getElementById('postit-font'), p = document.getElementById('postit-pinned')
		w.addEventListener('change', function () { prefs({ w: +w.value, h: +h.value }) })
		h.addEventListener('change', function () { prefs({ w: +w.value, h: +h.value }) })
		f.addEventListener('change', function () { prefs({ fontSize: +f.value }) })
		p.addEventListener('change', function () { prefs({ pinned: p.checked }) })
		var rl = document.getElementById('postit-reset-launcher')
		if (rl) rl.addEventListener('click', function () { prefs({ launcherX: -1, launcherY: -1 }) })
		Array.prototype.forEach.call(document.querySelectorAll('.postit-swatch'), function (b) {
			b.addEventListener('click', function () {
				Array.prototype.forEach.call(document.querySelectorAll('.postit-swatch'), function (x) { x.classList.toggle('postit-on', x === b) })
				prefs({ color: b.getAttribute('data-color') })
			})
		})
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init()
})()
