let installEvent,registration,installed=false;
const standalone=()=>matchMedia('(display-mode: standalone)').matches||navigator.standalone===true;
const ios=/iPad|iPhone|iPod/.test(navigator.userAgent)||(navigator.platform==='MacIntel'&&navigator.maxTouchPoints>1);
const installButton=document.querySelector('#pwa-install');
const dismissButton=document.querySelector('#pwa-dismiss');
let dismissed=false;try{dismissed=Number(localStorage.getItem('homedojo-install-dismissed'))>Date.now();}catch{}
if(ios){installButton.lastChild.textContent=' Ana ekrana ekle';installButton.setAttribute('aria-label','HomeDojo uygulamasını ana ekrana ekle');}
function syncInstall(){installButton.hidden=installed||standalone()||dismissed;dismissButton.hidden=installButton.hidden;}
dismissButton.addEventListener('click',()=>{dismissed=true;try{localStorage.setItem('homedojo-install-dismissed',String(Date.now()+7*86400000));}catch{}syncInstall();});
document.addEventListener('click',event=>{if(event.target.closest('[data-install-app]')){requestInstall();}});
syncInstall();
matchMedia('(display-mode: standalone)').addEventListener('change',syncInstall);
window.addEventListener('beforeinstallprompt',event=>{event.preventDefault();installEvent=event;syncInstall();});
window.addEventListener('appinstalled',()=>{installed=true;installEvent=null;syncInstall();document.querySelector('#pwa-help').close();});
const help=document.querySelector('#pwa-help');
const address=document.querySelector('#pwa-address');
address.value=new URL('/',location.href).href;
function showPlatform(platform){
 document.querySelectorAll('[data-pwa-platform]').forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.pwaPlatform===platform)));
 document.querySelector('#pwa-instructions').innerHTML=platform==='ios'
  ? '<ol class="pwa-steps"><li><strong>Safari’de aç</strong><span>Bu adresi iPhone veya iPad’inde Safari ile aç.</span></li><li><strong>Paylaş düğmesine dokun ↑</strong><span>Yukarı oklu kare simgesini bul. Gerekirse önce tarayıcı menüsünü aç.</span></li><li><strong>Ana Ekrana Ekle’yi seç</strong><span>Paylaş menüsünde aşağı kaydır. Görünmüyorsa “Eylemleri Düzenle” bölümünden ekle.</span></li><li><strong>Uygulama olarak ekle</strong><span>Varsa “Web Uygulaması Olarak Aç” seçeneğini açık tut, ardından “Ekle”ye dokun.</span></li></ol><p class="pwa-tip">Sonrasında HomeDojo simgesinden aç. Eski simge tarayıcı sekmesi açıyorsa bu adımlarla yeniden ekleyebilirsin.</p>'
  : '<ol class="pwa-steps"><li><strong>Tarayıcı menüsünü aç</strong><span>Chrome veya Edge’de menüye dokun.</span></li><li><strong>Uygulamayı yükle’yi seç</strong><span>“Ana ekrana ekle” olarak da görünebilir. Kurulumu onayla.</span></li><li><strong>HomeDojo simgesinden aç</strong><span>Uygulaman ana ekranında seni bekliyor.</span></li></ol>';
}
async function requestInstall(){
 if(installed||standalone())return;
 if(installEvent){const prompt=installEvent;installEvent=null;try{await prompt.prompt();await prompt.userChoice;return;}catch{}}
 showPlatform(ios?'ios':'other');
 document.querySelector('#pwa-copy-status').textContent='';
 if(!help.open)help.showModal();
}
installButton.addEventListener('click',requestInstall);
document.querySelectorAll('[data-pwa-platform]').forEach(button=>button.addEventListener('click',()=>showPlatform(button.dataset.pwaPlatform)));
document.querySelector('#pwa-copy').addEventListener('click',async()=>{
 try{await navigator.clipboard.writeText(address.value);document.querySelector('#pwa-copy-status').textContent='Adres kopyalandı. Safari’ye yapıştırabilirsin.';}
 catch{address.focus();address.select();document.querySelector('#pwa-copy-status').textContent='Adresi seçtik. Basılı tutup Kopyala’yı seçebilirsin.';}
});
document.querySelector('#pwa-close').addEventListener('click',()=>document.querySelector('#pwa-help').close());
const updateButton=document.querySelector('#pwa-update');let updating=false;
updateButton.addEventListener('click',()=>{if(!registration?.waiting)return;updating=true;registration.waiting.postMessage({type:'ACTIVATE_UPDATE'});});
if('serviceWorker' in navigator){
 navigator.serviceWorker.addEventListener('controllerchange',()=>{if(updating)location.reload();});
 navigator.serviceWorker.register('/sw.js',{scope:'/',updateViaCache:'none'}).then(reg=>{
  registration=reg;
  const showUpdate=()=>{updateButton.hidden=!reg.waiting||!navigator.serviceWorker.controller;};
  showUpdate();reg.addEventListener('updatefound',()=>{const worker=reg.installing;worker?.addEventListener('statechange',()=>{if(worker.state==='installed')showUpdate();});});
  document.addEventListener('visibilitychange',()=>{if(!document.hidden)reg.update().catch(()=>{});});
 }).catch(()=>{/* The site remains usable if installation is unsupported. */});
}
