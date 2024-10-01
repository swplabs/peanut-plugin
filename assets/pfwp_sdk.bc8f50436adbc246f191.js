(()=>{var e={156:e=>{const t="pfwp_",s=document.getElementsByTagName("head")[0];let n;const o=e=>{let t="";return e&&!e.id?console.log("dispatch|subscribe: element needs an id attribute"):e&&(t=`-${e.id.match(/[a-z0-9]+$/)}`),t},a=e=>{const t=new RegExp("/[a-zA-Z0-9-_]*(?<hash>.[a-zA-Z0-9]{20})?.(js|css)$"),{groups:s}=t.exec(e)||{};return s?.hash?e.replace(s.hash,""):e};let p="/wp-json/pfwp/v1/components/",c={};window.pfwp={state:{},eventStates:{},loadedAssets:{},dispatch:(e,s,n)=>{const a=`${t}${e}${o(n)}`;pfwp.eventStates[a]=!0,pfwp.state[a]=s;const p=new CustomEvent(a,{detail:s});n?n.dispatchEvent(p):document.dispatchEvent(p)},subscribe:(e,s,n)=>{const a=`${t}${e}${o(n)}`;pfwp.eventStates[a]&&s(pfwp.state[a]);const p=e=>{s(e.detail)};n?n.addEventListener(a,p):document.addEventListener(a,p)},assetStates:{},addAsset:({asset:e,component:t,index:o=0})=>new Promise(((p,c)=>{try{const r=e.endsWith("js")?"js":"css",d=`pfwp_${r}_${t}_${o}`,i=a(e);if(pfwp.loadedAssets[i])return void p();if("js"===r){const t=document.createElement("script");t.src=e,t.async=1,t.fetchPriority="low",t.id=d,t.onload=()=>{p()},t.onerror=e=>{c(e)},n&&(n.appendChild(t),pfwp.loadedAssets[i]=!0)}else{const t=document.createElement("link");t.id=d,t.rel="stylesheet",t.type="text/css",t.href=e,t.media="all",t.onload=()=>{p()},t.onerror=e=>{c(e)},s.appendChild(t),pfwp.loadedAssets[i]=!0}}catch(e){c(e)}})),getComponentAssets:async(e,t=[],s,n=!1)=>{const o="function"==typeof s,a=pfwp.assetStates[e],p=`component_loaded_${e}`;if("loaded"!==a){if(pfwp.subscribe(p,(()=>{o&&s()})),"loading"!==a){pfwp.assetStates[e]="loading";const s=[];t.forEach(((t,n)=>{s.push((async()=>{try{await pfwp.addAsset({asset:t,component:e,index:n})}catch(s){console.log("getComponentAssets error",{component:e,asset:t},s)}})())})),await Promise.all(s),n&&window.peanutSrcClientJs[`view_components_${e}`].default("",{}),pfwp.assetStates[e]="loaded",pfwp.dispatch(p,{})}}else o&&s()},setApiPath:e=>{p=e},getApiPath:()=>p,getComponentJs:e=>{const t=window.peanutSrcClientJs?.[`view_components_${e}`];let s;return"function"==typeof t?s=t:t&&t.hasOwnProperty("default")&&"function"==typeof t.default&&(s=t.default),s},runComponentJs:(e,t={})=>{const s=pfwp.getComponentJs(e);s&&Object.keys(t).forEach((e=>{s(document.getElementById(e),t[e])}))},lazyLoadObserver:new IntersectionObserver(((e,t)=>{e.forEach((e=>{const{target:s,isIntersecting:n}=e;n&&(t.unobserve(s),pfwp.dispatch("onObserve",{},s))}))})),asyncComponentLoad:async({instance:e,fetch_priority:t="low",componentName:s,component_data:n})=>{const{data_mode:o="path"}=c;let a="";if(n&&"object"==typeof n&&!Array.isArray(n)){const e=window.btoa(JSON.stringify({attributes:n}));a="path"!==o?`?data=${encodeURIComponent(e)}`:`${e}/`}const r=await fetch(`${p}${s}/${a}`,{method:"get",priority:t}),d=await r.json();if(!d)return;const{html:i,assets:l={},data:f}=d;if("string"!=typeof i||i.length<=0)return void console.log(`asyncComponentLoad: ${s} returned no html`);let w=document.createElement("div");w.innerHTML=i;const m=w.removeChild(w.firstChild);m.classList.add("lazy-load-loading"),w=null,e.replaceWith(m);const h=[];Object.keys(l).forEach((e=>{const t=l[e]?.assets,n=t&&Object.keys(t).reduce(((e,s)=>(e.push(...t[s]),e)),[]);Array.isArray(n)&&n.length&&h.push((async()=>{await pfwp.getComponentAssets(e,n,(()=>{const t=pfwp.getComponentJs(e);if(t){let n=[];n=e===s?[m]:m.querySelectorAll(`[id^="${e}"]`),n.length<=0||n.forEach((s=>t(s,f?.[e]?.[s.id])))}}))})())})),await Promise.all(h),m.classList.remove("lazy-load-loading")}},document.addEventListener("DOMContentLoaded",(()=>{pfwp.dispatch("pageDomLoaded",{})})),e.exports=(e,t)=>{const{components:{js:s,css:o},metadata:{js:p={}}}=t;c=window.pfwp_global_config,n=e,Object.keys(o).filter((e=>Array.isArray(o[e]))).forEach((e=>{o[e].forEach((e=>{pfwp.loadedAssets[a(e)]=!0}))})),Object.keys(p).forEach((e=>{!1===p[e].async&&(s[e].forEach((e=>{pfwp.loadedAssets[a(e)]=!0})),pfwp.runComponentJs(e,window.pfwp_comp_instances[e]))})),document.addEventListener("DOMContentLoaded",(()=>{Object.keys(s).filter((e=>!1!==p[e]?.async)).forEach((e=>{pfwp.getComponentAssets(e,s[e],(()=>{pfwp.runComponentJs(e,window.pfwp_comp_instances[e])}))}))}))}}},t={},s=function s(n){var o=t[n];if(void 0!==o)return o.exports;var a=t[n]={exports:{}};return e[n](a,a.exports,s),a.exports}(156);window.pfwpInitialize=s})();