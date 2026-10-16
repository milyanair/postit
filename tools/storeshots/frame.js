// Clean demo Nextcloud page + caption overlay. All data is fictional.
const LIGHT = '--color-main-background:#fff;--color-main-text:#222;--color-border:#dedede;--color-border-dark:#bbb;--color-text-maxcontrast:#6b6b6b;--color-background-hover:#f2f2f2;--color-primary-element:#0082c9;--color-primary-element-light:#e3f0fa;--color-primary-element-text:#fff;--color-error:#db0606;--font-face:system-ui,sans-serif;--header-height:50px'
const DARK = '--color-main-background:#1b1b1b;--color-main-text:#ebebeb;--color-border:#3b3b3b;--color-border-dark:#555;--color-text-maxcontrast:#9a9a9a;--color-background-hover:#2a2a2a;--color-primary-element:#0082c9;--color-primary-element-light:#16374d;--color-primary-element-text:#fff;--color-error:#ff5c5c;--font-face:system-ui,sans-serif;--header-height:50px'
function page({ dark = false, app = 'Files', head = '', body = '', caption = '', sub = '', nav = null }) {
  const files = [['📁', 'Projects', '—', '2 hours ago'], ['📁', 'Photos', '1.2 GB', 'yesterday'], ['📄', 'Q4 plan.md', '14 KB', '3 hours ago'], ['📊', 'Budget 2027.xlsx', '88 KB', 'last week'], ['🖼', 'Team offsite.jpg', '2.4 MB', 'last week'], ['📄', 'Release notes.odt', '31 KB', 'Oct 2'], ['📁', 'Shared with me', '—', 'Sep 28']]
  const rows = files.map(f => `<tr><td><span class=fi>${f[0]}</span>${f[1]}</td><td>${f[2]}</td><td>${f[3]}</td></tr>`).join('')
  const apps = ['Dashboard', 'Files', 'Photos', 'Talk', 'Calendar', 'Notes'].map(a => `<span class="ha${a === app ? ' on' : ''}">${a}</span>`).join('')
  return `<!doctype html><html><head data-user="demo" data-requesttoken="x"><meta charset="utf-8">${head}
<style>:root{${dark ? DARK : LIGHT}}body{margin:0;font-family:system-ui,sans-serif;background:${dark ? '#121212' : '#fff'};color:var(--color-main-text);overflow:hidden}
#hdr{position:fixed;top:0;left:0;right:0;height:50px;background:linear-gradient(90deg,#0069a6,#0082c9);display:flex;align-items:center;gap:22px;padding:0 18px;color:#fff;z-index:10}
#hdr b{font-size:20px;letter-spacing:.3px}.ha{opacity:.75;font-size:14px}.ha.on{opacity:1;font-weight:600;border-bottom:2px solid #fff;padding-bottom:3px}
#av{margin-left:auto;width:30px;height:30px;border-radius:50%;background:#ffd28a;color:#7a4b00;display:flex;align-items:center;justify-content:center;font-weight:700}
#nav{position:fixed;top:50px;left:0;bottom:0;width:250px;background:${dark ? '#181818' : '#f5f7f9'};padding:14px 10px;box-sizing:border-box}
#nav div{padding:9px 12px;border-radius:8px;font-size:14px;margin-bottom:2px}#nav .on{background:${dark ? '#16374d' : '#dcebf6'};font-weight:600}
#main{position:fixed;top:50px;left:250px;right:0;bottom:0;padding:22px 34px;box-sizing:border-box}
#main h1{font-size:22px;margin:0 0 16px}table{width:100%;border-collapse:collapse;font-size:14px}td{padding:12px 8px;border-bottom:1px solid var(--color-border)}
td:nth-child(n+2){color:var(--color-text-maxcontrast);width:140px}.fi{margin-right:12px}
#cap{position:fixed;left:50%;bottom:28px;transform:translateX(-50%);z-index:20000;background:rgba(20,28,38,.88);color:#fff;padding:14px 26px;border-radius:14px;
 font-size:24px;font-weight:700;box-shadow:0 8px 30px rgba(0,0,0,.3);text-align:center;max-width:80%;pointer-events:none}
#cap small{display:block;font-size:15px;font-weight:400;opacity:.85;margin-top:4px}</style></head><body>
<div id=hdr><b>☁ Cloud</b>${apps}<div id=av>A</div></div>
<div id=nav>${(nav || ['*All files', 'Recent', 'Favorites', 'Shared with you', 'Tags']).map(x => x[0] === '*' ? `<div class=on>${x.slice(1)}</div>` : `<div>${x}</div>`).join('')}</div>
<div id=main><h1>All files</h1><table>${rows}</table></div>
${caption ? `<div id=cap>${caption}${sub ? `<small>${sub}</small>` : ''}</div>` : ''}
${body}</body></html>`
}
module.exports = { page }
