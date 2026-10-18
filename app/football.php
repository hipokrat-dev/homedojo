<?php
declare(strict_types=1);
function football_enabled(array $u): bool { return ($u['id']??'')==='u3'&&($u['memberType']??'')==='child'; }
function football_teams(): array {return ['bursa'=>['name'=>'Bursaspor','slug'=>'bursaspor','table'=>'spor-toto-1-lig'],'besiktas'=>['name'=>'Beşiktaş','slug'=>'besiktas','table'=>'super-lig'],'bayern'=>['name'=>'Bayern Münih','slug'=>'bayern-munih','table'=>'bundesliga-i']];}
function football_youtube(mixed $url): string {
 if(!is_string($url)||strlen($url)>2048)throw new AppError('Geçerli bir YouTube bağlantısı yapıştır.');
 $p=parse_url(trim($url));if(!$p||($p['scheme']??'')!=='https'||isset($p['user'])||isset($p['pass'])||isset($p['port']))throw new AppError('HTTPS YouTube bağlantısı kullan.');
 $host=strtolower($p['host']??'');$path=$p['path']??'';$id='';
 if($host==='youtu.be')$id=ltrim($path,'/');
 elseif(in_array($host,['youtube.com','www.youtube.com','m.youtube.com','www.youtube-nocookie.com'],true)){
  if($path==='/watch'){parse_str($p['query']??'',$q);$id=$q['v']??'';}elseif(preg_match('~^/(?:shorts|embed|live)/([\w-]{11})/?$~D',$path,$m))$id=$m[1];
 }
 if(!is_string($id)||!preg_match('/^[A-Za-z0-9_-]{11}$/D',$id))throw new AppError('Tek bir videonun YouTube bağlantısını kullan.');return $id;
}
function football_video(array &$s,array $actor,string $action,array $in): void {
 if(!football_enabled($actor))throw new AppError('Bu alan Aey hesabına ait.',403);
 $i=active_user_index($s,$actor['id']);$videos=$s['users'][$i]['footballVideos']??[];
 if($action==='deleteFootballVideo'){$j=find_index($videos,$in['id']??null,'Video');array_splice($videos,$j,1);}
 else {$team=$in['team']??'';if(!is_string($team)||!isset(football_teams()[$team]))throw new AppError('Takım seç.');$id=football_youtube($in['url']??null);$title=$in['title']??'';if(!is_string($title)||mb_strlen($title)>100)throw new AppError('Başlık en fazla 100 karakter olabilir.');foreach($videos as $v)if($v['videoId']===$id&&$v['team']===$team)throw new AppError('Bu video zaten kayıtlı.');if(count($videos)>=100)throw new AppError('En fazla 100 video kaydedebilirsin.');$videos[]=['id'=>uid(),'videoId'=>$id,'team'=>$team,'title'=>trim($title)?:football_teams()[$team]['name'].' videosu'];}
 $s['users'][$i]['footballVideos']=$videos;
}
function fb_dom(string $html): DOMXPath {$html=preg_replace('/<meta\b[^>]*charset[^>]*>/i','',$html);$d=new DOMDocument();$old=libxml_use_internal_errors(true);$d->loadHTML('<?xml encoding="UTF-8">'.$html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);libxml_clear_errors();libxml_use_internal_errors($old);return new DOMXPath($d);}
function fb_class(string $name): string {return "contains(concat(' ',normalize-space(@class),' '),' $name ')";}
function fb_text(DOMXPath $x,string $class,?DOMNode $node=null): string {return trim(preg_replace('/\s+/u',' ',$x->query('.//*['.fb_class($class).']',$node)->item(0)?->textContent??''));}
function fb_date(string $day,string $time): ?string {
 $months=['Ocak'=>1,'Şubat'=>2,'Mart'=>3,'Nisan'=>4,'Mayıs'=>5,'Haziran'=>6,'Temmuz'=>7,'Ağustos'=>8,'Eylül'=>9,'Ekim'=>10,'Kasım'=>11,'Aralık'=>12];
 if(!preg_match('/^(\d{1,2}) (\S+) (\d{4})$/u',trim($day),$m)||!isset($months[$m[2]])||!preg_match('/^([01]\d|2[0-3])[.:]([0-5]\d)$/D',$time,$t)||!checkdate($months[$m[2]],(int)$m[1],(int)$m[3]))return null;
 return sprintf('%04d-%02d-%02dT%s:%s:00+03:00',$m[3],$months[$m[2]],$m[1],$t[1],$t[2]);
}
function fb_fixtures(string $html): array {
 $x=fb_dom($html);$rows=[];$day='';
 foreach($x->query('//tr['.fb_class('fixture-date-row').' or '.fb_class('fixture-match-row').']') as $r){if(str_contains($r->getAttribute('class'),'fixture-date-row')){$day=fb_text($x,'fixture-date-heading',$r);continue;}
 $a=$x->query('.//a['.fb_class('fixture-match').']',$r)->item(0);if(!$a)continue;$url=$a->getAttribute('href');if(!preg_match('~^https://www\.sporx\.com/[a-z0-9-]+-maci-canli-\d+$~D',$url))continue;
 $names=$x->query('.//*['.fb_class('fixture-team-name').']',$r);if($names->length!==2)continue;
 $score=fb_text($x,'fixture-score',$r);$rows[]=['id'=>basename($url),'home'=>trim($names->item(0)->textContent),'away'=>trim($names->item(1)->textContent),'kickoff'=>fb_date($day,fb_text($x,'fixture-match-time',$r)),'dateLabel'=>$day,'league'=>fb_text($x,'fixture-match-league',$r),'score'=>$score,'status'=>fb_text($x,'fixture-result-badge',$r)!==''?'finished':'scheduled','source'=>$url,'channels'=>[]];
 }if(!$rows)throw new RuntimeException('Fixture format unavailable');return $rows;
}
function fb_standings(string $html): array {
 $x=fb_dom($html);$rows=[];foreach($x->query('//table['.fb_class('standings-table').'][1]//tbody/tr') as $r){$td=$x->query('./td',$r);$name=fb_text($x,'standing-team-name',$r);if($td->length<11||!$name)continue;$rows[]=['position'=>(int)fb_text($x,'position-number',$r),'name'=>$name,'played'=>(int)$td->item(3)->textContent,'won'=>(int)$td->item(4)->textContent,'drawn'=>(int)$td->item(5)->textContent,'lost'=>(int)$td->item(6)->textContent,'difference'=>fb_text($x,'standing-average',$r),'points'=>(int)fb_text($x,'standing-points',$r),'selected'=>str_contains($r->getAttribute('class'),'selected-team')];}if(!$rows)throw new RuntimeException('Standings format unavailable');return $rows;
}
function fb_detail(string $html): array {
 $x=fb_dom($html);$status=fb_text($x,'csdMin');$live=$x->query('//*['.fb_class('matchHero-status').' and '.fb_class('is-live').']')->length>0;
 $data=[];if(preg_match('/var matchData = (\{[^\n]+\});/',$html,$m))$data=json_decode($m[1],true)['match']??[];
 $sid=(int)($data['statusId']??0);$finished=$sid===14||in_array(mb_strtolower($status),['ms','maç sonu','maç bitti'],true);
 return ['status'=>$finished?'finished':(($live||in_array($sid,[1,2,10],true))?'live':'scheduled'),'minute'=>mb_substr($status,0,40),'score'=>isset($data['score1'],$data['score2'])&&is_numeric($data['score1'])&&is_numeric($data['score2'])?$data['score1'].'-'.$data['score2']:null];
}
function fb_normal(string $s): string {return preg_replace('/[^a-z0-9]/','',strtr(mb_strtolower($s),['ş'=>'s','ı'=>'i','ğ'=>'g','ü'=>'u','ö'=>'o','ç'=>'c','eintracht'=>'e','munich'=>'munih']));}
function fb_channels(string $html,array $match): array {
 $x=fb_dom(str_replace('class=\\"ch-type\\"','class="ch-type"',$html));$rows=[];
 $selected=$x->query('//*[@id="tvdays"]/li[contains(@class,"active")]')->item(0)?->textContent??'';
 $months=['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
 if(!$match['kickoff']||!str_contains(preg_replace('/\s+/u',' ',trim($selected)),(int)substr($match['kickoff'],8,2).' '.$months[(int)substr($match['kickoff'],5,2)-1]))return [];
 // Accept only live football broadcasts; never confuse basketball or reruns.
 foreach($x->query('//*[@id="channelList"]/li') as $r){$title=fb_text($x,'ch-text',$r);$text=$r->textContent;if(!str_contains($title,'(Futbol)')||!str_contains($text,'Canlı'))continue;
 $parts=preg_split('/\s+-\s+/',str_replace('(Futbol)','',$title));if(count($parts)!==2)continue;
 if(fb_normal($parts[0])!==fb_normal($match['home'])||fb_normal($parts[1])!==fb_normal($match['away']))continue;
 $time=fb_text($x,'ch-time',$r);if($match['kickoff']&&$time!==substr($match['kickoff'],11,5))continue;
 $name=fb_text($x,'ch-name',$r);if($name!=='')$rows[]=$name;
 }return array_values(array_unique($rows));
}
final class FootballFeed {
 public function __construct(private PDO $db){$db->exec('CREATE TABLE IF NOT EXISTS homedojo_football_cache (cache_key VARCHAR(64) PRIMARY KEY, payload LONGTEXT NOT NULL, fetched_at BIGINT NOT NULL, retry_at BIGINT NOT NULL)');}
 public function cached(string $key,callable $load,int $ttl=60): array {
  $q=$this->db->prepare('SELECT * FROM homedojo_football_cache WHERE cache_key=?');$q->execute([$key]);$old=$q->fetch(PDO::FETCH_ASSOC);$now=time();
  if($old&&((int)$old['fetched_at']>$now-$ttl||(int)$old['retry_at']>$now))return ['value'=>json_decode($old['payload'],true),'at'=>(int)$old['fetched_at'],'stale'=>(int)$old['fetched_at']<$now-$ttl];
  $sql=$this->db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?'INSERT IGNORE':'INSERT OR IGNORE';$this->db->prepare($sql.' INTO homedojo_football_cache VALUES (?, ?, 0, 0)')->execute([$key,'null']);
  $lock=$this->db->prepare('UPDATE homedojo_football_cache SET retry_at=? WHERE cache_key=? AND retry_at<=?');$lock->execute([$now+60,$key,$now]);
  if(!$lock->rowCount())return ['value'=>$old?json_decode($old['payload'],true):null,'at'=>(int)($old['fetched_at']??0),'stale'=>true];
  try{$v=$load();$json=json_encode($v,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);$this->db->prepare('UPDATE homedojo_football_cache SET payload=?, fetched_at=?, retry_at=? WHERE cache_key=?')->execute([$json,$now,$now+$ttl,$key]);return ['value'=>$v,'at'=>$now,'stale'=>false];}
  catch(Throwable $e){error_log('Football feed unavailable: '.$key);return ['value'=>$old?json_decode($old['payload'],true):null,'at'=>(int)($old['fetched_at']??0),'stale'=>true];}
 }
 public static function get(string $url): string {
  if(!preg_match('~^https://www\.sporx\.com/[a-z0-9/?=\-]+$~D',$url))throw new RuntimeException('Invalid feed URL');
  $c=curl_init($url);$body='';curl_setopt_array($c,[CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>6,CURLOPT_USERAGENT=>'HomeDojo/1.0',CURLOPT_WRITEFUNCTION=>function($c,$chunk)use(&$body){if(strlen($body)+strlen($chunk)>1500000)return 0;$body.=$chunk;return strlen($chunk);}]);$ok=curl_exec($c);$code=curl_getinfo($c,CURLINFO_HTTP_CODE);unset($c);if(!$ok||$code!==200)throw new RuntimeException('Feed unavailable');return mb_check_encoding($body,'UTF-8')?$body:mb_convert_encoding($body,'UTF-8','Windows-1254');
 }
 public function team(string $id): array {
  $team=football_teams()[$id]??null;if(!$team)throw new AppError('Takım bulunamadı.');$base='https://www.sporx.com/'.$team['slug'];
  $fixtureUrl=$base.($id==='besiktas'?'-fiksturu-ve-mac-sonuclari':'-fiksturu');
  $fixtures=$this->cached('fixtures-v2-'.$id,fn()=>fb_fixtures(self::get($fixtureUrl)));$table=$this->cached('table-v2-'.$id,fn()=>fb_standings(self::get($base.'-puan-durumu-'.$team['table'])),600);
  $matches=$fixtures['value']??[];$detailCount=0;$tvDays=[];
  foreach($matches as &$m){$at=$m['kickoff']?strtotime($m['kickoff']):0;$m['liveVerifiedAt']=null;
   if($at&&abs(time()-$at)<14400&&$m['status']!=='finished'&&$detailCount++<2){$d=$this->cached('detail-v2-'.hash('sha1',$m['id']),fn()=>fb_detail(self::get($m['source'])));if($d['value']&&!$d['stale']){$m['status']=$d['value']['status'];$m['minute']=$d['value']['minute'];if($d['value']['score']!==null)$m['score']=$d['value']['score'];$m['liveVerifiedAt']=$d['at'];}}
   if($at&&$at>time()-14400&&$at<time()+7*86400){$day=substr($m['kickoff'],0,10);if(!isset($tvDays[$day])&&count($tvDays)<2)$tvDays[$day]=$this->cached('tv-'.$day,fn()=>self::get('https://www.sporx.com/tvdebugun/?date='.$day),1800);if(isset($tvDays[$day])&&!$tvDays[$day]['stale']&&$tvDays[$day]['value'])$m['channels']=fb_channels($tvDays[$day]['value'],$m);}
  }unset($m);
  $this->db->exec('DELETE FROM homedojo_football_cache WHERE retry_at < '.(time()-30*86400));
  return ['team'=>$id,'matches'=>$matches,'standings'=>$table['value']??[],'updatedAt'=>$fixtures['at'],'standingsAt'=>$table['at'],'stale'=>$fixtures['stale'],'standingsStale'=>$table['stale'],'source'=>$fixtureUrl,'tableSource'=>$base.'-puan-durumu-'.$team['table'],'tvSource'=>'https://www.sporx.com/tvdebugun/'];
 }
}
