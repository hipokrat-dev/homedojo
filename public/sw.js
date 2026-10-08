/* Keep private family data and authenticated responses out of offline storage. */
const CACHE='homedojo-public-v1';
const PUBLIC_FILES=['/offline.html','/icons/icon-192.png','/icons/icon-512.png','/icons/maskable-512.png','/icons/apple-touch-icon.png'];
self.addEventListener('install',event=>event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(PUBLIC_FILES))));
self.addEventListener('activate',event=>event.waitUntil((async()=>{
 for(const key of await caches.keys())if(key.startsWith('homedojo-public-')&&key!==CACHE)await caches.delete(key);
 await self.clients.claim();
})()));
self.addEventListener('message',event=>{if(event.data?.type==='ACTIVATE_UPDATE')self.skipWaiting();});
self.addEventListener('fetch',event=>{
 const {request}=event,url=new URL(request.url);
 if(request.method!=='GET'||url.origin!==self.location.origin)return;
 // API, login, setup and recovery always go to the server, even when opened directly.
 if(url.pathname.endsWith('.php')&&url.pathname!=='/index.php')return;
 if(request.mode==='navigate'){
  event.respondWith(fetch(request).catch(async()=>await caches.match('/offline.html')||Response.error()));return;
 }
 if(PUBLIC_FILES.includes(url.pathname)&&!url.search)event.respondWith(caches.match(request).then(hit=>hit||fetch(request)));
});
