(function(){
  'use strict';
  var D=document,W=window;
  function assets(){
    var data=W.amData||{},base=data.snapshotAssetBase,map=data.snapshotAssetMap||{};
    if(!base)return;
    var old='/wp-content/themes/alexandra-theme/dist';
    function replace(value){
      if(!value)return value;
      Object.keys(map).forEach(function(from){value=value.split(from).join(map[from]);});
      if(value.indexOf('/assets/')===0)return base+value;
      return value.replace(/(?:https?:\/\/[^/]+)?\/wp-content\/themes\/alexandra-theme\/dist\//g,base+'/').replace(/(["'(])\/assets\//g,'$1'+base+'/assets/');
    }
    D.querySelectorAll('[src],[srcset],[style],a[href]').forEach(function(el){
      ['src','srcset','style'].forEach(function(attr){var v=el.getAttribute(attr),n=replace(v);if(v!==n)el.setAttribute(attr,n);});
      var href=el.getAttribute('href');if(href && map[href])el.setAttribute('href',map[href]);
    });
  }
  var queued=false;
  new MutationObserver(function(){if(!queued){queued=true;requestAnimationFrame(function(){queued=false;assets();});}}).observe(D.documentElement,{childList:true,subtree:true,attributes:true,attributeFilter:['src','srcset','style']});
  D.addEventListener('DOMContentLoaded',assets);assets();
})();
