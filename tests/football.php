<?php
require __DIR__.'/../app/domain.php';require __DIR__.'/../app/accounts.php';
function ok($v){if(!$v)throw new RuntimeException('Football assertion failed');}
function rejects($fn){try{$fn();}catch(AppError){return;}throw new RuntimeException('Expected rejection');}
foreach(['https://youtu.be/M7lc1UVf-VE?si=abc','https://www.youtube.com/watch?v=M7lc1UVf-VE','https://m.youtube.com/shorts/M7lc1UVf-VE','https://youtube.com/live/M7lc1UVf-VE'] as $url)ok(football_youtube($url)==='M7lc1UVf-VE');
foreach(['https://youtube.com.evil.org/watch?v=M7lc1UVf-VE','javascript:alert(1)','https://youtube.com@evil.org/watch?v=M7lc1UVf-VE','https://youtube.com/watch?v[]=bad','https://youtu.be/short','http://youtu.be/M7lc1UVf-VE'] as $url)rejects(fn()=>football_youtube($url));
$s=initial_state();foreach($s['users'] as &$u)$u['role']='member';unset($u);$s['users'][0]['role']='admin';$s['users'][0]['memberType']='parent';$s['users'][2]['memberType']='child';$a=$s['users'][2];$other=$s['users'][3];
football_video($s,$a,'saveFootballVideo',['team'=>'bursa','url'=>'https://youtu.be/M7lc1UVf-VE','title'=>'Gol']);$a=$s['users'][2];$id=$a['footballVideos'][0]['id'];
rejects(fn()=>football_video($s,$other,'deleteFootballVideo',['id'=>$id]));rejects(fn()=>football_video($s,$a,'saveFootballVideo',['team'=>'invalid','url'=>'https://youtu.be/M7lc1UVf-VE']));
$v=member_snapshot($s,$a);ok(count($v['footballVideos'])===1);foreach($v['users'] as $u)ok(!isset($u['footballVideos']));ok(member_snapshot($s,$s['users'][0])['footballVideos']===[]);
football_video($s,$a,'deleteFootballVideo',['id'=>$id]);ok($s['users'][2]['footballVideos']===[]);
ok(fb_date('19 Ekim 2026','20.00')==='2026-10-19T20:00:00+03:00');ok(fb_date('30 Şubat 2026','20.00')===null);ok(fb_date('19 Ekim 2026','--')===null);
$fixture='<table><tr class="fixture-date-row"><td><div class="fixture-date-heading">19 Ekim 2026</div></td></tr><tr class="fixture-match-row"><td><a class="fixture-match" href="https://www.sporx.com/bursaspor-pendikspor-maci-canli-5908666"><span class="fixture-match-time">20.00</span><span class="fixture-match-league">Lig</span><span class="fixture-team-name">Bursaspor</span><span class="fixture-score">-</span><span class="fixture-team-name">Pendikspor</span></a></td></tr></table>';
$m=fb_fixtures($fixture)[0];ok(fb_fixtures('<meta charset="windows-1254">'.str_replace('Pendikspor','Keçiörengücü',$fixture))[0]['away']==='Keçiörengücü');ok($m['status']==='scheduled');ok($m['home']==='Bursaspor');
$tv='<ul id="tvdays"><li class="active">19 Ekim Pazartesi</li></ul><ul id="channelList"><li><span class="ch-type">Canlı</span><span class="ch-time">20:00</span><span class="ch-name">TRT Spor</span><span class="ch-text">Bursaspor - Pendikspor (Futbol)</span></li></ul>';
ok(fb_channels($tv,$m)===['TRT Spor']);ok(fb_channels(str_replace('Futbol','Basketbol',$tv),$m)===[]);ok(fb_channels(str_replace('Canlı','Tekrar',$tv),$m)===[]);ok(fb_channels(str_replace('19 Ekim','18 Ekim',$tv),$m)===[]);
ok(fb_detail('<div class="matchHero-status is-live"><span class="csdMin">42</span></div>')['status']==='live');ok(fb_detail('<span class="csdMin">Yarın</span>')['status']==='scheduled');ok(fb_detail('<span class="csdMin">MS</span>')['status']==='finished');
$db=new PDO('sqlite::memory:');$feed=new FootballFeed($db);$count=0;$load=function()use(&$count){$count++;return ['test'=>1];};ok($feed->cached('test',$load)['value']===['test'=>1]);$feed->cached('test',$load);ok($count===1);$db->exec('UPDATE homedojo_football_cache SET fetched_at=1,retry_at=0');$stale=$feed->cached('test',fn()=>throw new RuntimeException('offline'));ok($stale['stale']&&$stale['value']===['test'=>1]);
echo "Football video ownership, URL validation, source parsing, date, status, TV matching and cache tests passed.\n";
