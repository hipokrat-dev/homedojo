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
