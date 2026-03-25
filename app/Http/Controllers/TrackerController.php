<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Response;

class TrackerController extends Controller
{
    public function script(string $publicKey): Response
    {
        Site::query()->where('public_key', $publicKey)->firstOrFail();

        $base = json_encode(rtrim(config('app.url'), '/'));
        $key = json_encode($publicKey);

        $js = <<<JS
(function(){
var K={$key};
var B={$base};
var S='is_vid_'+K.replace(/-/g,'').slice(0,12);
function vid(){
try{
var v=localStorage.getItem(S);
if(!v){v=crypto.randomUUID();localStorage.setItem(S,v);}
return v;
}catch(e){return 'anon_'+Math.random().toString(36).slice(2);}
}
function jsonPost(path,body){
return fetch(B+path,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(body),keepalive:true,credentials:'omit'});
}
function beacon(path,body){
var url=B+path;
var blob=new Blob([JSON.stringify(body)],{type:'application/json'});
if(navigator.sendBeacon&&navigator.sendBeacon(url,blob))return;
fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body),keepalive:true});
}
var pvId=null;
var start=Date.now();
var pathSent=false;
function currentPath(){return location.pathname+location.search;}
function utm(){
var u=new URL(location.href);
return{
utm_source:u.searchParams.get('utm_source'),
utm_medium:u.searchParams.get('utm_medium'),
utm_campaign:u.searchParams.get('utm_campaign'),
utm_term:u.searchParams.get('utm_term'),
utm_content:u.searchParams.get('utm_content')
};
}
function sendPageview(){
if(pathSent)return;
pathSent=true;
var m=utm();
var u=new URL(location.href);
var sq=u.searchParams.get('q')||u.searchParams.get('query')||u.searchParams.get('s');
var body={site_key:K,visitor_id:vid(),path:currentPath(),referrer:document.referrer||null};
Object.assign(body,m);
if(sq)body.search_query=sq;
jsonPost('/collect/pageview',body).then(function(r){return r.json();}).then(function(d){pvId=d.id;}).catch(function(){});
}
function sendDuration(){
if(!pvId)return;
var dur=Math.round((Date.now()-start)/1000);
if(dur<0||dur>86400)return;
beacon('/collect/duration',{site_key:K,visitor_id:vid(),page_view_id:pvId,duration_seconds:dur});
}
document.addEventListener('visibilitychange',function(){
if(document.visibilityState==='hidden')sendDuration();
});
window.addEventListener('pagehide',sendDuration);
document.addEventListener('click',function(e){
var a=e.target&&e.target.closest&&e.target.closest('a');
if(!a||!a.href)return;
try{
var u=new URL(a.href,location.href);
if(u.hostname===location.hostname)return;
beacon('/collect/outbound',{site_key:K,visitor_id:vid(),from_path:location.pathname+location.search,target_url:a.href});
}catch(err){}
},true);
if(document.readyState==='complete')sendPageview();
else window.addEventListener('load',sendPageview);
})();
JS;

        return response($js, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
