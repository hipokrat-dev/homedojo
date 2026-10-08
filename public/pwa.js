let installEvent,registration,installed=false;
const standalone=()=>matchMedia('(display-mode: standalone)').matches||navigator.standalone===true;
const ios=/iPad|iPhone|iPod/.test(navigator.userAgent)||(navigator.platform==='MacIntel'&&navigator.maxTouchPoints>1);
const installButton=document.querySelector('#pwa-install');
const dismissButton=document.querySelector('#pwa-dismiss');
let dismissed=false;try{dismissed=Number(localStorage.getItem('homedojo-install-dismissed'))>Date.now();}catch{}
function syncInstall(){installButton.hidden=installed||standalone()||dismissed;dismissButton.hidden=installButton.hidden;}
dismissButton.addEventListener('click',()=>{dismissed=true;try{localStorage.setItem('homedojo-install-dismissed',String(Date.now()+7*86400000));}catch{}syncInstall();});
document.addEventListener('click',event=>{if(event.target.closest('[data-install-app]')){if(standalone())return;installButton.click();}});
syncInstall();
matchMedia('(display-mode: standalone)').addEventListener('change',syncInstall);
window.addEventListener('beforeinstallprompt',event=>{event.preventDefault();installEvent=event;syncInstall();});
window.addEventListener('appinstalled',()=>{installed=true;installEvent=null;syncInstall();document.querySelector('#pwa-help').close();});
installButton.addEventListener('click',async()=>{
 if(installEvent){const prompt=installEvent;installEvent=null;try{await prompt.prompt();await prompt.userChoice;return;}catch{}}
 const help=document.querySelector('#pwa-help');
 document.querySelector('#pwa-instructions').textContent=ios?'Safari’de Paylaş düğmesine dokun, “Ana Ekrana Ekle”yi seç ve Ekle’ye dokun.':'Tarayıcının menüsünü açıp “Uygulamayı yükle” veya “Ana ekrana ekle” seçeneğini kullan. Bu seçenek görünmüyorsa Chrome, Edge veya Safari ile aç.';
 help.showModal();
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
