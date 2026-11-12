(function(){
  'use strict';
  var preview=window.ldrnSnapshot;
  if(!preview || !preview.session)return;
  function keepVersion(url){
    if(!url)return url;
    var u=new URL(url,location.href);
    if(u.origin!==location.origin || /^\/wp-/.test(u.pathname))return url;
    u.searchParams.set(preview.canvas?'ldrn_ws_studio':'ldrn_session',preview.canvas?'1':preview.session);
    return u.pathname+u.search+u.hash;
  }
  ['pushState','replaceState'].forEach(function(name){
    var original=history[name];
    history[name]=function(state,title,url){return original.call(history,state,title,keepVersion(url));};
  });
  document.addEventListener('click',function(event){
    var link=event.target.closest('a[href]');
    if(link && !link.closest('[aria-label="Saved website preview"]')){
      var href=link.getAttribute('href'),next=keepVersion(href);
      if(next!==href && !event.defaultPrevented && !event.ctrlKey && !event.metaKey && !event.shiftKey && !link.target){
        event.preventDefault();event.stopImmediatePropagation();location.assign(next);
      }else if(next!==href)link.setAttribute('href',next);
    }
  },true);
  var originalFetch=window.fetch;
  window.fetch=function(input,init){
    var url=new URL(typeof input==='string'?input:input.url,location.href);
    if(url.origin===location.origin && /\/wp-json\/ldrn\/v1\/(blogs|testimonials)(\/|$)/.test(url.pathname) && (!init || !init.method || init.method==='GET')){
      url.searchParams.set('ldrn_session',preview.session);
      init=Object.assign({},init,{headers:new Headers(init && init.headers)});
      init.headers.set('X-WP-Nonce',preview.nonce);
      input=url.href;
    }
    return originalFetch.call(window,input,init);
  };
})();
