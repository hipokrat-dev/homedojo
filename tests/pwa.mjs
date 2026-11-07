import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import vm from 'node:vm';
const manifest=JSON.parse(await readFile('public/manifest.webmanifest','utf8'));
assert.equal(manifest.display,'standalone');assert.equal(manifest.scope,'/');assert.ok(manifest.start_url.startsWith('/'));assert.ok(manifest.icons.some(i=>i.purpose==='maskable'));
for(const icon of [...manifest.icons,{src:'/icons/apple-touch-icon.png',sizes:'180x180'}]){
 const data=await readFile('public'+icon.src);assert.equal(data.toString('hex',0,8),'89504e470d0a1a0a');assert.equal(`${data.readUInt32BE(16)}x${data.readUInt32BE(20)}`,icon.sizes);
}
const handlers={},deleted=[],network=[],cached=[],offline={offline:true};let offlineMode=false;
const context={URL,Response,self:{location:{origin:'https://example.com'},clients:{claim:async()=>{}},skipWaiting:()=>{},addEventListener:(name,fn)=>handlers[name]=fn},caches:{open:async()=>({addAll:async paths=>cached.push(...paths)}),keys:async()=>['homedojo-public-v0','unrelated-cache','homedojo-public-v1'],delete:async key=>deleted.push(key),match:async key=>key==='/offline.html'?offline:undefined},fetch:async request=>{network.push(request);if(offlineMode)throw Error('Offline');return {network:true};}};
vm.runInNewContext(await readFile('public/sw.js','utf8'),context);
let work;handlers.install({waitUntil:p=>work=p});await work;assert.ok(cached.every(p=>!p.includes('api')&&!p.includes('app.js')));
handlers.activate({waitUntil:p=>work=p});await work;assert.deepEqual(deleted,['homedojo-public-v0']);
for(const [path,method,mode] of [['/api.php?action=state','GET','navigate'],['/api.php?action=complete','POST','cors'],['/recover.php','GET','navigate'],['/setup.php','GET','navigate'],['/app.js?v=abc','GET','cors']]){
 let intercepted=false;handlers.fetch({request:{url:'https://example.com'+path,method,mode},respondWith:()=>intercepted=true});assert.equal(intercepted,false,`${path} must bypass cache`);
}
offlineMode=true;handlers.fetch({request:{url:'https://example.com/',method:'GET',mode:'navigate'},respondWith:p=>work=p});assert.equal(await work,offline);
offlineMode=false;handlers.fetch({request:{url:'https://example.com/',method:'GET',mode:'navigate'},respondWith:p=>work=p});assert.deepEqual(await work,{network:true});
console.log('PWA manifest, PNG sizes, private-data isolation, offline fallback and cache cleanup passed.');
let notification,opened,focused=false;
context.self.registration={showNotification:async(title,options)=>notification={title,options}};
handlers.push({waitUntil:p=>work=p});await work;assert.match(notification.title,/hatırlatman/);assert.equal(notification.options.data.url,'/#profile');
context.self.clients.matchAll=async()=>[];context.self.clients.openWindow=async url=>opened=url;
handlers.notificationclick({notification:{close:()=>{}},waitUntil:p=>work=p});await work;assert.equal(opened,'https://example.com/#profile');
context.self.clients.matchAll=async()=>[{url:'https://example.com/',navigate:async url=>opened=url,focus:async()=>focused=true}];
handlers.notificationclick({notification:{close:()=>{}},waitUntil:p=>work=p});await work;assert.equal(focused,true);
console.log('Background push displays a notification and opens/focuses the task page.');

const pwaSource=await readFile('public/pwa.js','utf8');
function installHarness({ios=false,ipad=false,standalone=false,dismissed=false}={}){
 const elements=new Map(),windowHandlers={},documentHandlers={},mediaHandlers={};
 const makeElement=()=>({hidden:false,open:false,textContent:'',innerHTML:'',lastChild:{textContent:''},dataset:{},attributes:{},events:{},setAttribute(name,value){this.attributes[name]=value;},addEventListener(name,fn){this.events[name]=fn;},showModal(){this.open=true;},close(){this.open=false;},focus(){this.focused=true;},select(){this.selected=true;}});
 const get=id=>{if(!elements.has(id))elements.set(id,makeElement());return elements.get(id);};
 const platforms=['ios','other'].map(platform=>Object.assign(makeElement(),{dataset:{pwaPlatform:platform}}));
 const navigator={userAgent:ios?'iPhone':'Desktop',platform:ipad?'MacIntel':'',maxTouchPoints:ipad?5:0,standalone};
 const media={matches:false,addEventListener:(name,fn)=>mediaHandlers[name]=fn};
 const ctx={URL,Date,String,Number,navigator,location:{href:'https://example.com/?private=value#notes'},matchMedia:()=>media,localStorage:{getItem:()=>dismissed?String(Date.now()+86400000):null,setItem:()=>{}},window:{addEventListener:(name,fn)=>windowHandlers[name]=fn},document:{querySelector:get,querySelectorAll:()=>platforms,addEventListener:(name,fn)=>documentHandlers[name]=fn}};
 vm.runInNewContext(pwaSource,ctx);
 return {get,platforms,navigator,media,mediaHandlers,windowHandlers,documentHandlers};
}
for(const device of [{ios:true},{ipad:true}]){
 const h=installHarness(device);await h.get('#pwa-install').events.click();
 assert.equal(h.get('#pwa-help').open,true);
 assert.match(h.get('#pwa-instructions').innerHTML,/Safari/);
 assert.match(h.get('#pwa-instructions').innerHTML,/Web Uygulaması Olarak Aç/);
 assert.match(h.get('#pwa-instructions').innerHTML,/Eylemleri Düzenle/);
 assert.equal(h.platforms[0].attributes['aria-pressed'],'true');
 assert.equal(h.get('#pwa-address').value,'https://example.com/');
 h.platforms[1].events.click();assert.match(h.get('#pwa-instructions').innerHTML,/Chrome/);
 let copied;h.navigator.clipboard={writeText:async value=>copied=value};await h.get('#pwa-copy').events.click();assert.equal(copied,'https://example.com/');
 h.navigator.clipboard={writeText:async()=>{throw Error('Denied');}};await h.get('#pwa-copy').events.click();assert.equal(h.get('#pwa-address').selected,true);assert.match(h.get('#pwa-copy-status').textContent,/Basılı tut/);
 h.get('#pwa-close').events.click();assert.equal(h.get('#pwa-help').open,false);
}
const hiddenGuide=installHarness({ios:true,dismissed:true});assert.equal(hiddenGuide.get('#pwa-install').hidden,true);
hiddenGuide.documentHandlers.click({target:{closest:()=>true}});assert.equal(hiddenGuide.get('#pwa-help').open,true,'Settings must still open help after banner dismissal');
const installedGuide=installHarness({ios:true,standalone:true});assert.equal(installedGuide.get('#pwa-install').hidden,true);await installedGuide.get('#pwa-install').events.click();assert.equal(installedGuide.get('#pwa-help').open,false);
const androidGuide=installHarness();let prompted=0,prevented=false;
androidGuide.windowHandlers.beforeinstallprompt({preventDefault:()=>prevented=true,prompt:async()=>prompted++,userChoice:Promise.resolve({outcome:'accepted'})});
await androidGuide.get('#pwa-install').events.click();assert.equal(prevented,true);assert.equal(prompted,1);assert.equal(androidGuide.get('#pwa-help').open,false);
androidGuide.windowHandlers.appinstalled();assert.equal(androidGuide.get('#pwa-install').hidden,true);
const displayGuide=installHarness();displayGuide.media.matches=true;displayGuide.mediaHandlers.change();assert.equal(displayGuide.get('#pwa-install').hidden,true);
console.log('iPhone/iPad install steps, settings access, clipboard fallback, standalone detection and Android native installation passed.');
