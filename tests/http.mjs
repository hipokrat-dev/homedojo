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
 const initial=(await request('state')).body.state;assert.equal(initial.users.length,4);assert.equal(initial.tasks.length,10);
 assert.equal((await request('spin',{userId:'u1',frequency:'all',requestId:'no-goal-request-1234'})).status,409);
 assert.equal((await request('selectGoal',{userId:'u1',rewardId:'r1'})).status,200);
 const draws=await Promise.all([request('spin',{userId:'u1',frequency:'daily',requestId:'same-draw-1234567890'}),request('spin',{userId:'u1',frequency:'daily',requestId:'same-draw-1234567890'})]);
 assert.deepEqual(draws.map(r=>r.status),[200,200]);assert.equal(draws[0].body.assignment.id,draws[1].body.assignment.id);
 const assignment=draws[0].body.assignment;
 assert.equal((await request('state')).body.state.assignments.length,1,'concurrent retry persists one assignment');
 assert.equal((await request('complete',{userId:'u2',assignmentId:assignment.id})).status,403);
 const completions=await Promise.all([request('complete',{userId:'u1',assignmentId:assignment.id,points:100000}),request('complete',{userId:'u1',assignmentId:assignment.id})]);assert.deepEqual(completions.map(r=>r.status).sort(),[200,409]);
 assert.equal((await request('redeem',{userId:'u1',rewardId:'r1',requestId:'insufficient-1234567'})).status,409);
 // Keep one deterministic monthly template to exercise frozen assignment points through HTTP.
 for(const task of initial.tasks)assert.equal((await request('deleteTask',{id:task.id})).status,200);
 const added=await request('saveTask',{title:'API task',description:'',points:100,icon:'✨',frequency:'monthly'});assert.equal(added.status,200);const task=added.body.state.tasks.at(-1);
 const assigned=await request('spin',{userId:'u1',frequency:'monthly',requestId:'monthly-draw-1234567'});assert.equal(assigned.status,200);
 await request('saveTask',{...task,points:200});await request('deleteTask',{id:task.id});
 assert.equal((await request('complete',{userId:'u1',assignmentId:assigned.body.assignment.id})).status,200);
 const claims=await Promise.all([request('redeem',{userId:'u1',rewardId:'r1',requestId:'redemption-12345678'}),request('redeem',{userId:'u1',rewardId:'r1',requestId:'redemption-12345678'})]);assert.deepEqual(claims.map(r=>r.status).sort(),[200,409]);
 const result=await request('state');assert.equal(result.body.state.users[0].balance,assignment.points);assert.equal(result.body.state.users[0].earned,assignment.points+100);assert.equal(result.body.state.users[1].balance,0);assert.equal(result.body.state.users[0].goal,null);
 assert.equal(result.body.state.assignments.length,2);assert.equal(result.body.state.completions.length,2);
 const created=await request('createCompetition',{title:'Aile yarışı',prize:'Piknik',target:80,frequency:'weekly'});assert.equal(created.status,200);const contest=created.body.state.competitions.at(-1);
 assert.equal((await request('claimCompetition',{id:contest.id})).status,409);
 await request('saveTask',{title:'Birlikte görev',description:'',points:80,icon:'✨',frequency:'daily'});
 const cancelled=(await request('spin',{userId:'u3',frequency:'daily',requestId:'cancel-draw-12345678'})).body.assignment;
 assert.equal((await request('cancelAssignment',{userId:'u2',assignmentId:cancelled.id})).status,403);
 assert.equal((await request('cancelAssignment',{userId:'u3',assignmentId:cancelled.id})).status,200);
 assert.equal((await request('complete',{userId:'u3',assignmentId:cancelled.id})).status,409);
 const familyAssignment=(await request('spin',{userId:'u3',frequency:'daily',requestId:'family-draw-12345678'})).body.assignment;
 assert.equal(Date.parse(familyAssignment.dueAt)-Date.parse(familyAssignment.assignedAt),86400000,'daily deadline starts at assignment');
 const won=await request('complete',{userId:'u3',assignmentId:familyAssignment.id});assert.equal(won.body.state.competitions.at(-1).total,80);assert.equal(won.body.state.competitions.at(-1).status,'achieved');
 const sharedClaims=await Promise.all([request('claimCompetition',{id:contest.id}),request('claimCompetition',{id:contest.id})]);assert.deepEqual(sharedClaims.map(r=>r.status).sort(),[200,409]);
 assert.equal((await request('state')).body.state.users.find(u=>u.id==='u3').balance,80,'shared prize does not spend personal balance');
 await request('selectGoal',{userId:'u4',rewardId:'r1'});
 const raceAssignment=(await request('spin',{userId:'u4',frequency:'daily',requestId:'race-draw-1234567890'})).body.assignment;
 const race=await Promise.all([request('cancelAssignment',{userId:'u4',assignmentId:raceAssignment.id}),request('complete',{userId:'u4',assignmentId:raceAssignment.id})]);assert.deepEqual(race.map(r=>r.status).sort(),[200,409],'cancel versus complete has exactly one winner');
 assert.equal((await request('logout',{})).status,200);assert.equal((await request('state')).status,401);
 console.log('✓ HTTP authentication, CSRF, origin, session rotation, persisted goal and assignments, concurrent draw/completion/reward retries, frozen points, task CRUD, reward debit, relative deadlines, cancellation races, shared competition scoring/claims and logout passed.');
}finally{if(server){server.kill();await new Promise(r=>server.once('exit',r));}await rm(dir,{recursive:true,force:true});}
