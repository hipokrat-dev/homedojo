import {mkdtemp,cp,writeFile,rm} from 'node:fs/promises';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import {spawn,execFileSync} from 'node:child_process';
import assert from 'node:assert/strict';
const dir=await mkdtemp(join(tmpdir(),'homedojo-test-'));
const base='http://127.0.0.1:3097';let server,cookie='',csrf='';
try{
 await cp('app',join(dir,'app'),{recursive:true,filter:p=>!p.endsWith('config.local.php')&&!p.endsWith('setup-key.php')});await cp('public',join(dir,'public'),{recursive:true});
 const hash=execFileSync('php',['-r',"echo password_hash('test-household-password', PASSWORD_DEFAULT);"],{encoding:'utf8'});
 await writeFile(join(dir,'app','config.local.php'),`<?php return ['driver'=>'sqlite','environment'=>'development','sqlite_path'=>__DIR__.'/test.sqlite','public_url'=>'${base}','password_hash'=>'${hash}'];`);
 server=spawn('php',['-S','127.0.0.1:3097','-t',join(dir,'public')],{env:{...process.env,APP_ENV:'development'},stdio:'ignore'});
 let ready=false;for(let i=0;i<50;i++){try{await fetch(base);ready=true;break;}catch{await new Promise(r=>setTimeout(r,100));}}
 assert.ok(ready,'test server started');
 const shell=await fetch(base);assert.match(shell.headers.get('cache-control'),/no-store/);const html=await shell.text();assert.match(html,/app\.js\?v=[a-f0-9]{16}/);assert.match(html,/style\.css\?v=[a-f0-9]{16}/);
 async function request(action,data,overrides={}){const res=await fetch(`${base}/api.php?action=${action}`,{method:data===undefined?'GET':'POST',headers:{Cookie:cookie,Origin:base,'Content-Type':'application/json','X-CSRF-Token':csrf,...overrides},...(data===undefined?{}:{body:JSON.stringify(data)})});const set=res.headers.get('set-cookie');if(set)cookie=set.split(';')[0];return {status:res.status,body:await res.json()};}
 const session=await request('session');csrf=session.body.csrf;assert.equal(session.body.authenticated,false);
 assert.equal((await request('state')).status,401,'anonymous data access rejected');
 assert.equal((await request('login',{password:'wrong'})).status,422,'invalid password rejected');
 assert.equal((await request('login',{password:'test-household-password'},{'X-CSRF-Token':'wrong'})).status,403,'CSRF rejected');
 assert.equal((await request('login',{password:'test-household-password'},{Origin:'https://evil.example'})).status,403,'cross origin rejected');
 const oldCookie=cookie,login=await request('login',{password:'test-household-password'});assert.equal(login.status,200);csrf=login.body.csrf;assert.notEqual(cookie,oldCookie,'session regenerated');
 const setup=(await request('state')).body;assert.equal(setup.setupRequired,true);assert.equal(setup.profiles.length,4);
 const accounts=setup.profiles.map((u,i)=>({id:u.id,name:i===0?'Baba':['','Anne','Ada','Efe'][i],username:'member'+(i+1),password:'test-password-123'}));
 const activation=await request('setupAccounts',{adminId:'u1',accounts});assert.equal(activation.status,200);csrf=activation.body.csrf;
 const initial=activation.body.state;assert.equal(initial.viewerId,'u1');assert.equal(initial.viewerRole,'admin');assert.equal(initial.users.length,4);assert.equal(initial.tasks.length,40);
 assert.ok(!JSON.stringify(initial).includes('passwordHash'));
 const admin={cookie,csrf};
 async function loginAs(n){cookie='';csrf=(await request('session')).body.csrf;const r=await request('login',{username:'member'+n,password:'test-password-123'});assert.equal(r.status,200);csrf=r.body.csrf;return {cookie,csrf};}
 const member=await loginAs(2),other=await loginAs(3);
 function use(client){cookie=client.cookie;csrf=client.csrf;}
 use(member);
 const ownState=(await request('state')).body.state;const ownReward=ownState.rewards[0].id;assert.equal(ownState.tasks.length,10);assert.equal(ownState.rewards.length,4);assert.ok(ownState.tasks.every(t=>t.ownerId==='u2'));assert.ok(ownState.rewards.every(r=>r.ownerId==='u2'));
 assert.equal((await request('selectGoal',{rewardId:'r1'})).status,403);assert.equal((await request('redeem',{rewardId:'r1',requestId:'other-reward-123456'})).status,403);
 for(const action of ['saveTask','deleteTask','saveReward','deleteReward','saveUser','saveAccount','createCompetition','cancelCompetition','claimCompetition'])assert.equal((await request(action,{})).status,403,action+' requires admin');
 assert.equal((await request('spin',{userId:'u1',frequency:'all',requestId:'spoof-draw-12345678'})).status,403);
 assert.equal((await request('selectGoal',{rewardId:ownReward})).status,200);
 const draws=await Promise.all([request('spin',{frequency:'daily',requestId:'same-draw-1234567890'}),request('spin',{frequency:'daily',requestId:'same-draw-1234567890'})]);
 assert.deepEqual(draws.map(r=>r.status),[200,200]);assert.equal(draws[0].body.assignment.id,draws[1].body.assignment.id);
 const a=draws[0].body.assignment;assert.equal(Date.parse(a.dueAt)-Date.parse(a.assignedAt),86400000);
 assert.equal((await request('complete',{assignmentId:a.id,reviewerId:'u2'})).status,403);
 assert.equal((await request('complete',{assignmentId:a.id,reviewerId:'missing'})).status,404);
 const submissions=await Promise.all([request('complete',{assignmentId:a.id,reviewerId:'u1',points:100000}),request('complete',{assignmentId:a.id,reviewerId:'u1'})]);assert.deepEqual(submissions.map(r=>r.status).sort(),[200,409]);
 let state=(await request('state')).body.state;assert.equal(state.users.find(u=>u.id==='u2').balance,0);assert.equal(state.assignments[0].status,'pending');
 assert.equal((await request('cancelAssignment',{assignmentId:a.id})).status,409);
 assert.equal((await request('reviewAssignment',{assignmentId:a.id,decision:'approve',actorId:'u1'})).status,403,'client cannot spoof reviewer');
 use(other);state=(await request('state')).body.state;assert.equal(state.assignments.length,0);assert.equal(state.completions.length,0);assert.ok(!('username' in state.users[0]));
 assert.equal((await request('reviewAssignment',{assignmentId:a.id,decision:'approve'})).status,403);
 use(admin);state=(await request('state')).body.state;assert.equal(state.assignments.length,1);
 const approvals=await Promise.all([request('reviewAssignment',{assignmentId:a.id,decision:'approve'}),request('reviewAssignment',{assignmentId:a.id,decision:'approve'})]);assert.deepEqual(approvals.map(r=>r.status).sort(),[200,409]);
 assert.equal((await request('state')).body.state.users.find(u=>u.id==='u2').balance,a.points);
 // Admin can manage templates without rewriting historical assignment values.
 const added=await request('saveTask',{ownerId:'u2',title:'Approval task',description:'',points:100,icon:'✨',frequency:'monthly'});assert.equal(added.status,200);const task=added.body.state.tasks.at(-1);
 for(const t of initial.tasks)assert.equal((await request('deleteTask',{id:t.id})).status,200);
 use(member);const frozen=(await request('spin',{frequency:'monthly',requestId:'monthly-draw-1234567'})).body.assignment;
 use(admin);await request('saveTask',{...task,points:200});await request('deleteTask',{id:task.id});
 use(member);assert.equal((await request('complete',{assignmentId:frozen.id,reviewerId:'u1'})).status,200);
 use(admin);assert.equal((await request('reviewAssignment',{assignmentId:frozen.id,decision:'approve'})).status,200);
 use(member);const claims=await Promise.all([request('redeem',{rewardId:ownReward,requestId:'redemption-12345678'}),request('redeem',{rewardId:ownReward,requestId:'redemption-12345678'})]);assert.deepEqual(claims.map(r=>r.status).sort(),[200,409]);
 state=(await request('state')).body.state;assert.equal(state.users.find(u=>u.id==='u2').balance,a.points);assert.equal(state.completions.length,2);
 use(admin);const contest=(await request('createCompetition',{title:'Aile yarışı',prize:'Piknik',target:80,frequency:'weekly'})).body.state.competitions.at(-1);
 await request('saveTask',{ownerId:'u3',title:'Ortak görev',description:'',points:80,icon:'✨',frequency:'daily'});
 use(other);const shared=(await request('spin',{frequency:'daily',requestId:'shared-draw-1234567'})).body.assignment;
 assert.equal((await request('complete',{assignmentId:shared.id,reviewerId:'u2'})).status,200);
 use(member);const won=await request('reviewAssignment',{assignmentId:shared.id,decision:'approve'});assert.equal(won.body.state.familyGoal.total,80);
 use(admin);const prize=await Promise.all([request('claimCompetition',{id:contest.id}),request('claimCompetition',{id:contest.id})]);assert.deepEqual(prize.map(r=>r.status).sort(),[200,409]);
 assert.equal((await request('state')).body.state.users.find(u=>u.id==='u3').balance,80);
 // A shared task reaches only its selected members, while rewards remain personal.
 const common=await request('saveTask',{scope:'shared',participantIds:['u1','u2'],title:'Shared table',points:25,frequency:'daily'});assert.equal(common.status,200);const commonId=common.body.state.tasks.at(-1).id;
 use(member);assert.ok((await request('state')).body.state.tasks.some(t=>t.id===commonId));
 use(other);assert.ok(!(await request('state')).body.state.tasks.some(t=>t.id===commonId));
 use(admin);assert.equal((await request('saveTask',{scope:'shared',participantIds:['u1'],title:'Invalid shared',points:25,frequency:'daily'})).status,400);
 // Member self-service keeps current session, revokes other devices, and checks CSRF.
 use(member);const secondDevice=await loginAs(2);use(member);
 const wrongCredentials={username:'anne_new',currentPassword:'wrong',password:'replacement-password',passwordConfirm:'replacement-password'};
 assert.equal((await request('changeCredentials',wrongCredentials)).status,422);
 assert.equal((await request('changeCredentials',{...wrongCredentials,currentPassword:'test-password-123',userId:'u1'})).status,403);
 const changed=await request('changeCredentials',{...wrongCredentials,currentPassword:'test-password-123'});assert.equal(changed.status,200);assert.notEqual(changed.body.csrf,member.csrf);csrf=changed.body.csrf;const updatedMember={cookie,csrf};
 assert.equal((await request('state')).status,200,'current device stays signed in');
 use(secondDevice);assert.equal((await request('state')).status,401,'second device revoked');
 use(updatedMember);
 const imageData=execFileSync('php',['-r',"$i=imagecreatetruecolor(30,30);ob_start();imagepng($i);echo 'data:image/png;base64,'.base64_encode(ob_get_clean());"],{encoding:'utf8'});
 assert.equal((await request('savePhoto',{photo:imageData},{'X-CSRF-Token':'wrong'})).status,403);
 assert.equal((await request('savePhoto',{photo:imageData,userId:'u1'})).status,403);
 const uploaded=await request('savePhoto',{photo:imageData});assert.equal(uploaded.status,200);assert.match(uploaded.body.state.users.find(u=>u.id==='u2').photo,/^data:image\/jpeg;base64,/);
 const badImage=await request('savePhoto',{photo:'data:image/svg+xml;base64,PHN2Zy8+'});assert.equal(badImage.status,400);
 assert.equal((await request('savePhoto',{photo:'x'.repeat(230001)})).status,413);
 assert.equal((await request('savePhoto',{remove:true})).status,200);
 assert.ok(!(await request('state')).body.state.users.find(u=>u.id==='u2').photo);
 use(admin);
 const lastCookie=cookie,lastCsrf=csrf;cookie='';csrf=(await request('session')).body.csrf;
 assert.equal((await request('login',{username:'member2',password:'test-password-123'})).status,422);
 const renamedLogin=await request('login',{username:'anne_new',password:'replacement-password'});assert.equal(renamedLogin.status,200);csrf=renamedLogin.body.csrf;assert.equal((await request('state')).body.state.viewerId,'u2');cookie=lastCookie;csrf=lastCsrf;
 // A password reset invalidates previously authenticated sessions on the next request.
 assert.equal((await request('saveAccount',{id:'u2',username:'member2',name:'Anne',avatar:'🌷',password:'new-fixture-password'})).status,200);
 use(member);assert.equal((await request('state')).status,401);
 // Membership lifecycle and young-child routes use the same authenticated, locked API.
 use(other);assert.equal((await request('createAccount',{name:'Nope'})).status,403);
 use(admin);const created=await request('createAccount',{name:'Minik',username:'little_test',password:'little-fixture-password',avatar:'🐣',memberType:'young_child',guardianId:'u1'});assert.equal(created.status,200);
 const littleId=created.body.state.users.find(u=>u.username==='little_test').id;
 assert.equal((await request('saveRoutines',{id:littleId,routines:[{id:'all-day',title:'Test routine',icon:'🌟',time:'00:00',until:'23:59',points:15}]})).status,200);
 cookie='';csrf=(await request('session')).body.csrf;const childLogin=await request('login',{username:'little_test',password:'little-fixture-password'});assert.equal(childLogin.status,200);csrf=childLogin.body.csrf;const little={cookie,csrf};
 const childState=(await request('state')).body.state;assert.equal(childState.dailyRoutines.length,1);assert.equal(childState.guardianName,'Baba');assert.ok(!JSON.stringify(childState).includes('passwordHash'));
 assert.equal((await request('spin',{frequency:'daily',requestId:'child-block-spin-12345'})).status,403);
 assert.equal((await request('createAccount',{})).status,403);
 assert.equal((await request('archiveAccount',{id:'u1'})).status,403);
 assert.equal((await request('completeRoutine',{routineId:'all-day',day:'2000-01-01'})).status,409);
 if(childState.dailyRoutines[0].status==='active'){
  const posted=await Promise.all([request('completeRoutine',{routineId:'all-day',day:childState.today}),request('completeRoutine',{routineId:'all-day',day:childState.today})]);assert.deepEqual(posted.map(r=>r.status),[200,200]);
  const requests=posted[1].body.state.assignments.filter(a=>a.routineId==='all-day');assert.equal(requests.length,1);assert.equal(requests[0].status,'pending');
  use(admin);const reviewed=await request('reviewAssignment',{assignmentId:requests[0].id,decision:'approve'});assert.equal(reviewed.status,200);assert.equal(reviewed.body.state.users.find(u=>u.id===littleId).earned,15);
 }
 use(admin);assert.equal((await request('archiveAccount',{id:littleId})).status,200);use(little);assert.equal((await request('state')).status,401);
 cookie='';csrf=(await request('session')).body.csrf;assert.equal((await request('login',{username:'little_test',password:'little-fixture-password'})).status,422);
 use(admin);const restored=await request('restoreAccount',{id:littleId});assert.equal(restored.status,200);assert.ok(restored.body.state.users.some(u=>u.id===littleId));assert.equal((await request('archiveAccount',{id:'u1'})).status,409);
 // Older children can submit daily routines; only parents can inspect progress.
 use(other);const older=(await request('state')).body.state;assert.ok(older.dailyRoutines.length>0);assert.equal(older.canViewProgress,false);assert.equal((await request('progress&mode=week&date='+older.today)).status,403);
 use(admin);assert.equal((await request('saveRoutines',{id:'u3',routines:[{id:'older-day',title:'Older daily task',icon:'📚',time:'00:00',until:'23:59',points:20}]})).status,200);
 use(other);const olderPlan=(await request('state')).body.state;if(olderPlan.dailyRoutines[0].status==='active'){assert.equal((await request('completeRoutine',{routineId:'older-day',day:olderPlan.today})).status,200);}
 use(admin);const progress=await request('progress&mode=week&date='+olderPlan.today+'&childId=u3&source=daily');assert.equal(progress.status,200);assert.equal(progress.body.report.rows.length,1);assert.equal(progress.body.report.rows[0].userId,'u3');assert.ok(!JSON.stringify(progress.body).includes('passwordHash'));assert.equal((await request('progress&date=2026-02-30')).status,400);
 const anne=(await request('state')).body.state.users.find(u=>u.id==='u2');assert.equal((await request('saveAccount',{id:'u2',name:anne.name,username:anne.username,avatar:anne.avatar,memberType:'parent'})).status,200);
 cookie='';csrf=(await request('session')).body.csrf;const parentLogin=await request('login',{username:'member2',password:'new-fixture-password'});assert.equal(parentLogin.status,200);csrf=parentLogin.body.csrf;assert.equal((await request('progress&mode=day&date='+olderPlan.today)).status,200,'Nonadmin parent can inspect progress');assert.equal((await request('saveRoutines',{id:'u3',routines:[]})).status,403,'Report access does not grant admin rights');
 use(admin);assert.equal((await request('setupAccounts',{adminId:'u1',accounts})).status,409);
 assert.equal((await request('logout',{})).status,200);assert.equal((await request('state')).status,401);
 console.log('✓ HTTP personal login, migration, roles, ownership, privacy, CSRF, session rotation/revocation, concurrent draws/submissions/approvals, frozen points, reward debit and family scoring passed.');
}finally{if(server&&server.exitCode===null&&server.signalCode===null){const exited=new Promise(r=>server.once('exit',r));server.kill();await exited;}await rm(dir,{recursive:true,force:true});}
