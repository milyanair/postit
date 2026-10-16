const fs = require('fs'); const { page } = require('./frame')
const { chromium } = require(process.env.PLAYWRIGHT_PATH || 'playwright')
const mock = `<link rel="stylesheet" href="postit.css"><script>
var now=Math.floor(Date.now()/1000);
var D=[[1,'<h2>Launch checklist</h2><ul><li><b>Final QA</b> on staging</li><li>Update release notes</li><li>Announce in Talk 🎉</li></ul>','#fff59d',0,'me',380,90,300,250],
 [2,'<p>Call the print shop about the <u>event banners</u> before Friday ☎️</p>','#ffcc80',0,'me',720,130,260,200],
 [3,'<h2>Team ideas</h2><ol><li>Lunch &amp; learn</li><li>Hack day in November</li></ol>','#b3e5fc',0,'sam',1060,110,290,230],
 [4,'<p>Book flights for the offsite ✈️</p>','#c8e6c9',0,'me',420,400,260,190],
 [5,'<p>Draft newsletter intro</p>','#f8bbd0',0,'me',0,0,280,220],
 [6,'<p>Old sprint retro notes</p>','#e1bee7',1,'me',0,0,280,220],[7,'<p>Q3 vendor list</p>','#cfd8dc',1,'me',0,0,280,220]];
var notes=D.map(function(d,i){var mine=d[4]==='me';return {id:d[0],content:d[1],color:d[2],archived:!!d[3],pinned:i===0,x:d[5],y:d[6],w:d[7],h:d[8],open:false,
 owner:mine?'demo':'sam',ownerName:mine?'Alex Rivera':'Sam Lee',isOwner:mine,shared:!mine||i===0,shareCount:i===0?2:0,createdAt:now-86400*(12+i),updatedAt:now-3600*(i+1)}});
var SH={1:[{id:'sam',name:'Sam Lee'},{id:'jordan',name:'Jordan Park'}]};
window.OC={webroot:'',requestToken:'x',getCurrentUser:function(){return{uid:'demo'}}};
window.fetch=function(u,o){var m=o.method,id=+((u.match(/notes\\/(\\d+)/)||[])[1]),r;
 if(/users\\?/.test(u)){r=[{id:'jordan',name:'Jordan Park'},{id:'jamie',name:'Jamie Chen'},{id:'jo',name:'Jo Martin'}]}
 else if(/shares/.test(u)){r={owner:'demo',ownerName:'Alex Rivera',shares:SH[id]||[]}}
 else if(m==='GET'&&id){r=notes.find(function(x){return x.id===id})}
 else if(m==='GET')r={notes:notes,prefs:{w:280,h:240,color:'#fff59d',fontSize:15,palette:['#fff59d','#ffcc80','#f8bbd0','#b3e5fc','#c8e6c9','#e1bee7','#ffffff','#cfd8dc']}};
 else if(m==='PUT'&&id){var n=notes.find(function(x){return x.id===id});Object.assign(n,JSON.parse(o.body));r=n} else r={};
 return Promise.resolve({ok:true,json:function(){return Promise.resolve({ocs:{data:JSON.parse(JSON.stringify(r))}})}})};
</script>`
const html = (caption, sub) => page({ head: mock, body: '<script src="postit.js"></script>', caption, sub })
;(async () => {
  const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 1440, height: 900 } })
  p.on('pageerror', e => console.log('ERR', e.message))
  const out = require('path').join(__dirname, '../../screenshots/'); fs.mkdirSync(out, { recursive: true })
  async function load(c, s) { fs.writeFileSync(require('path').join(__dirname, 'pi.html'), html(c, s)); await p.goto('file://' + require('path').join(__dirname, 'pi.html')); await p.waitForTimeout(500) }
  const shot = async n => { await p.screenshot({ path: out + n + '.png' }); console.log('saved', n) }
  // 1 notes floating
  await load('Sticky notes on every page', 'Rich text, colors, emoji and images. Drag, resize, pin.')
  await p.locator('#postit-launcher').click({ button: 'right' }); await p.waitForTimeout(300)
  await p.click('button[title="Open all notes"]'); await p.waitForTimeout(400)
  await p.mouse.click(300, 820); await shot('1-notes')
  // 2 note menu
  await load('Pin, color, expand', 'Pinned notes float over every app as you navigate')
  await p.waitForTimeout(200)
  await p.locator('.postit-note').first().locator('button[title="Note menu"]').click(); await p.waitForTimeout(300); await shot('2-note-menu')
  // 3 list with edit toggle
  await load('All your notes, one right-click away', 'Quick delete and archive with the edit toggle')
  await p.locator('#postit-launcher').click({ button: 'right' }); await p.waitForTimeout(400)
  await p.click('.postit-toggle input'); await p.waitForTimeout(200); await shot('3-list')
  // 4 panel
  await load('Active and archived, side by side', 'Restore archived notes or export either list to CSV')
  await p.locator('#postit-launcher').click({ button: 'right' }); await p.waitForTimeout(400)
  await p.click('.postit-htitle'); await p.waitForTimeout(300); await shot('4-panel')
  // 5 share
  await load('Share notes with your team', 'Everyone edits the same note; position and size stay personal')
  await p.locator('.postit-note').first().locator('button[title="Note menu"]').click(); await p.waitForTimeout(200)
  await p.click('.postit-pop button:has-text("Share")'); await p.waitForTimeout(300)
  await p.fill('.postit-sinput', 'j'); await p.waitForTimeout(700); await shot('5-share')
  await b.close()
})()
