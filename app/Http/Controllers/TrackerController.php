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
if(location.protocol==='https:'&&B.indexOf('http:')===0){console.warn('[indiestats] Page is HTTPS but APP_URL is HTTP: the browser blocks script and API calls (mixed content). Use HTTPS for the API (e.g. Herd/Valet) or test over HTTP.');}
var S='is_vid_'+K.replace(/-/g,'').slice(0,12);
function vid(){
try{
var v=localStorage.getItem(S);
if(!v){v=crypto.randomUUID();localStorage.setItem(S,v);}
return v;
}catch(e){return 'anon_'+Math.random().toString(36).slice(2);}
}
var origRefKey='is_origref_'+K.replace(/-/g,'').slice(0,12);
function referralOrigin(){
try{
var s=sessionStorage.getItem(origRefKey);
if(s!==null){
return s===''?null:s;
}
var v=document.referrer||'';
sessionStorage.setItem(origRefKey,v);
return v||null;
}catch(e){
return document.referrer||null;
}
}
function jsonPost(path,body){
return fetch(B+path,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(body),keepalive:true,credentials:'omit'});
}
function beacon(path,body){
var url=B+path;
var blob=new Blob([JSON.stringify(body)],{type:'application/json'});
if(navigator.sendBeacon&&navigator.sendBeacon(url,blob))return;
fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body),keepalive:true,credentials:'omit'});
}
var pvId=null;
var start=Date.now();
var pathSent=false;
var pendingDuration=false;
function pagePath(){
var p=location.pathname||'/';
return p;
}
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
referralOrigin();
var m=utm();
var u=new URL(location.href);
var sq=u.searchParams.get('q')||u.searchParams.get('query')||u.searchParams.get('s');
var body={site_key:K,visitor_id:vid(),path:pagePath(),referrer:document.referrer||null};
Object.assign(body,m);
if(sq)body.search_query=sq;
if(typeof navigator!=='undefined'&&navigator.userAgent){body.user_agent=navigator.userAgent;}
jsonPost('/collect/pageview',body).then(function(r){return r.json();}).then(function(d){
pvId=d.id;
if(pendingDuration){
if(document.visibilityState==='hidden'){sendDuration();}
else{pendingDuration=false;}
}
}).catch(function(){});
}
function sendDuration(){
if(!pvId){pendingDuration=true;return;}
pendingDuration=false;
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
beacon('/collect/outbound',{site_key:K,visitor_id:vid(),from_path:pagePath(),target_url:a.href,referrer:referralOrigin()});
}catch(err){}
},true);
if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',sendPageview);}
else{sendPageview();}
window.addEventListener('beforeunload',sendDuration);
if(document.readyState==='complete')sendPageview();
else window.addEventListener('load',sendPageview);
var NS='indiestats';
window[NS]=window[NS]||{};
window[NS].track=function(name,props){
if(!name||typeof name!=='string')return;
var n=name.trim().slice(0,128);
if(!n)return;
var p=null;
if(props&&typeof props==='object'&&!Array.isArray(props)){
p={};
var k,i=0;
for(k in props){
if(i>=20)break;
if(!Object.prototype.hasOwnProperty.call(props,k))continue;
if(typeof k!=='string'||k.length>64)continue;
var v=props[k];
if(typeof v==='boolean')p[k]=v;
else if(typeof v==='number'&&isFinite(v))p[k]=v;
else if(typeof v==='string')p[k]=v.slice(0,255);
i++;
}
if(Object.keys(p).length===0)p=null;
}
beacon('/collect/event',{site_key:K,visitor_id:vid(),name:n,path:pagePath(),properties:p,referrer:referralOrigin()});
};
})();
JS;

        return response($js, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
