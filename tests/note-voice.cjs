const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const source=fs.readFileSync('public/app.js','utf8');
function setup(){
 const element=()=>({value:'Mevcut not.',maxLength:3000,disabled:false,readOnly:false,textContent:'',setAttribute(k,v){this[k]=v},dispatchEvent(){}});
 const selectors=['[data-note-speak]','[data-note-dictate]','[data-note-voice-status]','[data-note-mic-enable]'];
 const controls=Object.fromEntries(selectors.map(q=>[q,element()]));const body=element(),sessions=[],timers=new Map();let sequence=0,now=0,stoppedTracks=0;
 class Recognition{constructor(){sessions.push(this)}start(){this.onstart?.()}abort(){this.aborted=true}end(){this.onend?.()}}
 const context={window:{SpeechRecognition:Recognition,isSecureContext:true,SpeechSynthesisUtterance:class{},speechSynthesis:{cancel(){},getVoices(){return []},speak(){}},addEventListener(){},removeEventListener(){}},navigator:{mediaDevices:{async getUserMedia(){return {getTracks:()=>[{stop(){stoppedTracks++}}]}}}},document:{hidden:false,addEventListener(){},removeEventListener(){}},modal:{querySelector:q=>controls[q],addEventListener(){},removeEventListener(){}},Event:class{},Date:{now:()=>now},setTimeout(fn,ms){timers.set(++sequence,{fn,ms});return sequence},clearTimeout(id){timers.delete(id)}};
 vm.createContext(context);vm.runInContext(source.slice(source.indexOf('let disposeNoteVoice='),source.indexOf('function noteEditor(')),context);context.bindNoteVoice(()=>body.value,body);
 const event=(texts,final=true)=>({resultIndex:0,results:texts.map(text=>Object.assign([{transcript:text}],{isFinal:final}))});
 return {context,body,sessions,controls,event,get stoppedTracks(){return stoppedTracks},run(ms){const t=[...timers].find(([,t])=>t.ms===ms);assert(t,`Missing ${ms}ms timer`);timers.delete(t[0]);now+=ms;t[1].fn()},timers,dictate:controls['[data-note-dictate]'],status:controls['[data-note-voice-status]']};
}
(async()=>{
 let t=setup();t.dictate.onclick();const first=t.sessions[0];
 for(let i=0;i<10;i++)first.onresult(t.event(['Yarın kitap okuyacağım.']));
 assert.equal(t.body.value,'Mevcut not. Yarın kitap okuyacağım.','Replayed final results must not be appended');
 first.onresult(t.event(['Yarın kitap okuyacağım.','Yarın kitap okuyacağım.','Sonra ödev yapacağım.']));
 assert.equal(t.body.value,'Mevcut not. Yarın kitap okuyacağım. Sonra ödev yapacağım.');
 first.onresult(t.event(['Yarın kitap','Yarın kitap okuyacağım.']));assert.equal(t.body.value,'Mevcut not. Yarın kitap okuyacağım.','Cumulative results must replace');
 first.onresult(t.event(['Yeni cüm'],false));first.onresult(t.event(['Yeni cümle.']));assert.equal(t.body.value,'Mevcut not. Yeni cümle.','Interim revisions must replace');
 first.end();assert(t.body.readOnly);t.run(350);assert.equal(t.sessions.length,2);t.sessions[1].onresult(t.event(['İkinci cümle.']));assert.equal(t.body.value,'Mevcut not. Yeni cümle. İkinci cümle.');
 t.sessions[1].end();t.dictate.onclick();assert.equal(t.timers.size,0,'User stop must cancel restart and idle timers');assert(!t.body.readOnly);
 t.dictate.onclick();vm.runInContext('disposeNoteVoice()',t.context);assert.equal(t.timers.size,0);assert(t.sessions.at(-1).aborted);
 t=setup();t.dictate.onclick();t.sessions[0].onerror({error:'not-allowed'});t.sessions[0].end();assert.equal(t.timers.size,0);assert(!t.body.readOnly);assert(t.status.textContent.includes('engelli'));
 t=setup();t.dictate.onclick();t.run(60000);assert(t.sessions[0].aborted);assert.equal(t.timers.size,0);
 t=setup();t.dictate.onclick();t.sessions[0].onerror({error:'no-speech'});t.sessions[0].end();t.run(700);assert.equal(t.sessions.length,2);
 t=setup();t.dictate.onclick();t.sessions[0].end();t.run(700);t.sessions[1].end();t.run(1050);t.sessions[2].end();assert.equal(t.timers.size,0,'Rapid failures must not loop');
 t=setup();t.body.value='a'.repeat(2998);t.dictate.onclick();t.sessions[0].onresult(t.event(['uzun cümle']));assert.equal(t.body.value.length,3000);assert(!t.body.readOnly);
 t=setup();const format=t.context.formatSpokenNote;
 assert.equal(format('', 'bir kitap oku iki ödev yap üç çantanı hazırla'), '1. kitap oku\n2. ödev yap\n3. çantanı hazırla');
 assert.equal(format('', '1, kitap oku 2: ödev yap'), '1. kitap oku\n2. ödev yap');
 assert.equal(format('', '1 kitap oku 2 ödev yap'), '1. kitap oku\n2. ödev yap');
 assert.equal(format('', 'Birinci madde kitabı oku ikinci madde deftere yaz'), '1. kitabı oku\n2. deftere yaz');
 assert.equal(format('', 'Bugün bir kitap ve iki defter aldım.'), 'Bugün bir kitap ve iki defter aldım.');
 assert.equal(format('', 'Bir gün iki saat çalıştım.'), 'Bir gün iki saat çalıştım.');
 assert.equal(format('', 'bir süt al iki litre olsun iki ödev yap'), '1. süt al iki litre olsun\n2. ödev yap');
 assert.equal(format('1. Kitabı oku', 'iki ödev yap üç çantayı hazırla'), '1. Kitabı oku\n2. ödev yap\n3. çantayı hazırla');
 t.body.value='';t.dictate.onclick();t.sessions[0].onresult(t.event(['bir kitap oku']));t.sessions[0].end();t.run(350);t.sessions[1].onresult(t.event(['iki ödev yap']));assert.equal(t.body.value,'1. kitap oku\n2. ödev yap','Numbering survives automatic session restarts');
 for(let i=0;i<10;i++)t.sessions[1].onresult(t.event(['iki ödev yap']));assert.equal(t.body.value,'1. kitap oku\n2. ödev yap');
 t.dictate.onclick();t.dictate.onclick();t.sessions[2].onresult(t.event(['üç çantanı hazırla']));assert.equal(t.body.value,'1. kitap oku\n2. ödev yap\n3. çantanı hazırla','Continue an existing list after manual restart');
 t=setup();await t.controls['[data-note-mic-enable]'].onclick();assert.equal(t.stoppedTracks,1);assert(t.status.textContent.includes('erişimi açık'));
 let resolve;t.context.navigator.mediaDevices.getUserMedia=()=>new Promise(r=>resolve=r);const pending=t.controls['[data-note-mic-enable]'].onclick();vm.runInContext('disposeNoteVoice()',t.context);let stopped=false;resolve({getTracks:()=>[{stop(){stopped=true}}]});await pending;assert(stopped);
 console.log('PASS: spoken numbered lists and cross-session formatting; replayed and cumulative transcripts, interim revisions, restart, stop, permission failure, idle limit, retry bound, length limit, microphone cleanup');
})().catch(e=>{console.error(e);process.exitCode=1});
