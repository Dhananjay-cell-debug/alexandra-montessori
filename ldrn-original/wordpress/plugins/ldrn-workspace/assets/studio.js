/**
 * Site Editor - the screen.
 *
 * Layout is Framer's: Pages / Layers / Assets on the left, the real site in
 * the middle at a real device width, and the selected layer's properties on
 * the right. The canvas is the site WordPress serves, loaded with
 * `?ldrn_ws_studio=1` so it draws the draft, and this script talks to the runtime
 * inside it (assets/studio-runtime.js) directly - same origin.
 *
 * All edits live in one document (`doc`). Every change:
 *   1. goes on the undo stack,
 *   2. is handed to the canvas, which repaints instantly,
 *   3. is autosaved as the draft a moment later.
 * Publish copies the draft to the live site.
 */
(function () {
  'use strict';

  var cfg = window.ldrnStudio;
  function routeAllowed(r) { return !cfg.scoped || (cfg.allowedRoutes || []).indexOf(r) !== -1; }
  function keyAllowed(k) {
    if (!cfg.scoped || !k) return true;
    if (/^t(?:>|$)/.test(k)) return false;
    if (/^[me]:/.test(k)) return routeAllowed(k.slice(2).split('>')[0].split('~')[0]);
    if (/^[nw]:/.test(k) && bridge) { var info=bridge.info(k); return !!info && info.path.some(function(p){return /^[me]:/.test(p.key) && routeAllowed(p.key.slice(2).split('>')[0]);}); }
    return false;
  }
  var root = document.getElementById('ldrn-studio');
  if (!cfg || !root) return;

  // Never an editor inside an editor: loaded in a frame (its own canvas
  // included), this page shows the site instead and the editor does not start.
  if (window.self !== window.top) {
    root.innerHTML = '';
    window.location.replace(cfg.base.replace(/\/+$/, '') + (cfg.start || '/') + '?ldrn_ws_studio=1');
    return;
  }

  /* ------------------------------------------------------------ state */

  var DEVICES = {
    d: { label: 'Desktop', width: 1440 },
    t: { label: 'Tablet', width: 834 },
    p: { label: 'Phone', width: 390 },
  };
  var FONTS = [
    ['', 'As designed'],
    ['display', 'Fraunces'],
    ['mark', 'Fraunces italic'],
    ['sans', 'Nunito Sans'],
  ];
  var FONT_FACES = {
    display: 'Fraunces, Georgia, serif',
    mark: 'Fraunces, Georgia, serif',
    sans: '"Nunito Sans", sans-serif',
  };
  var SHADOWS = [
    ['', 'As designed'],
    ['none', 'None'],
    ['0 1px 3px rgba(0,0,0,.35)', 'Soft'],
    ['0 8px 24px rgba(0,0,0,.45)', 'Medium'],
    ['0 24px 60px rgba(0,0,0,.6)', 'Deep'],
    ['0 0 24px rgba(255,121,10,.35)', 'Orange glow'],
  ];

  var doc = normalise(cfg.draft);
  var status = cfg.status || {};
  // The draft revision this screen is built on (see ldrn_ws_studio_rev()).
  var rev = status.rev || '';
  var conflict = null;
  var undoStack = [];
  var redoStack = [];
  var lastGroup = null;
  var lastGroupAt = 0;
  var bp = 'd';
  var zoom = 'fit';
  var mode = 'select';
  var leftTab = 'layers';
  var route = '/';
  var selected = null;
  var sel = null; // info() for the selected layer
  var uniformScale = true;
  var layerStackOpen = false;
  var layerStackExpanded = {};
  var layerStackDrag = null;
  var multi = []; // every selected layer; `selected` is the one the panel edits
  var expanded = {};
  var bridge = null;
  var saveTimer = null;
  var activeSave = null;
  var publishing = false;
  var saveState = 'saved';
  var localDirty = false;
  var discovered = {};
  var media = null;
  var lib = window.ldrnStudioLibrary || { templates: [], elements: [], instantiate: null, fonts: {} };
  function addSavedTemplate(saved) {
    lib.templates.push({id:saved.id,cat:'My sections',name:saved.name,build:function(){
      function walk(node) {
        var copy=JSON.parse(JSON.stringify(node));
        var edits=saved.nodes['n:'+node.id]||{};
        copy.s=edits.s||{};
        copy.edits=JSON.parse(JSON.stringify(edits));delete copy.edits.f;
        if(node.kids) copy.kids=node.kids.map(walk);
        delete copy.id;
        return copy;
      }
      return walk(saved.tree);
    }});
  }
  (cfg.savedTemplates||[]).forEach(addSavedTemplate);
  var paletteDrag = null;
  function beginPaletteDrag(event, entry, kind) {
    paletteDrag={entry:entry,kind:kind};
    event.dataTransfer.effectAllowed='copy';
    event.dataTransfer.setData('text/plain',entry.name||'Nursery element');
    // Drag the element itself, not a picture of its tile.
    var ghost=event.currentTarget && event.currentTarget.querySelector && event.currentTarget.querySelector('.wws-elx-stage');
    if(ghost && event.dataTransfer.setDragImage){
      var r=ghost.getBoundingClientRect();
      try{event.dataTransfer.setDragImage(ghost,Math.round(r.width/2),Math.round(r.height/2));}catch(e){}
    }
  }
  function hasFiles(event){return !!(event.dataTransfer && Array.prototype.indexOf.call(event.dataTransfer.types||[],'Files')!==-1);}
  function connectPaletteDrop() {
    var canvas=frame.contentDocument;
    canvas.addEventListener('dragover',function(event){if(paletteDrag||hasFiles(event)){event.preventDefault();event.dataTransfer.dropEffect='copy';}});
    canvas.addEventListener('drop',function(event){
      // Photos dragged straight from the computer's own folders.
      if(!paletteDrag && hasFiles(event)){
        event.preventDefault();event.stopPropagation();
        var point=dropPoint(event);
        var onImage=event.target && event.target.tagName==='IMG' && bridge.keyOf(event.target);
        uploadImages(event.dataTransfer.files).then(function(list){
          list.forEach(function(m,i){
            if(i===0 && onImage){select(onImage);if(selIsImage()){useImage(m.url,m.alt);return;}}
            floatInsert(floatImage(m.url,m.alt),point,i*24);
          });
        });
        return;
      }
      if(!paletteDrag) return;
      event.preventDefault();event.stopPropagation();
      var item=paletteDrag; paletteDrag=null;
      var target=bridge.keyOf(event.target);
      if(item.kind==='section'){
        if(target && bridge.exists(target)) select(target); else select('m:'+route);
        insertLibrary(item.entry.build(),'section');
        return;
      }
      if(item.kind==='image' && event.target && event.target.tagName==='IMG' && target){select(target);if(selIsImage()){useImage(item.entry.url,item.entry.alt);return;}}
      if(item.entry.needsUrl) { if(target && bridge.exists(target)) select(target); embedForm.open(item.entry); return; }
      var point=dropPoint(event);
      if(item.entry.needsFile){uploadForm.open(point);return;}
      var tree=item.kind==='image' ? floatImage(item.entry.url,item.entry.alt) : item.entry.build();
      floatInsert(tree,point,0);
    });
  }

  /**
   * Where something dropped on the canvas goes: the page's own block under
   * the pointer (a section of the site, or one added in the editor), and the
   * spot inside it. The element is laid over that block as a free layer, so
   * nothing below it moves down.
   */
  function dropPoint(event) {
    var D=frame.contentDocument, Wn=frame.contentWindow;
    var main=D.querySelector('main#main');
    var t=event.target && event.target.nodeType===1 ? event.target : main;
    var host=t;
    while(host && host.parentElement && host.parentElement!==main) host=host.parentElement;
    if(!host || !main || !main.contains(host) || host===main) host=main;
    return {x:event.clientX,y:event.clientY,host:host};
  }

  /** A block can hold a free layer if making it the layer's frame moves nothing already in it. */
  function canFrame(el) {
    var Wn=frame.contentWindow;
    if(Wn.getComputedStyle(el).position!=='static') return true;
    return !Array.prototype.some.call(el.querySelectorAll('*'),function(d){
      if(Wn.getComputedStyle(d).position!=='absolute') return false;
      var op=d.offsetParent; return !op || !el.contains(op);
    });
  }

  function floatImage(url,alt) {
    return {tag:'img',src:url,alt:alt||'',name:alt||'Photo',s:{width:'320px',height:'auto','max-width':'100%','object-fit':'cover','border-radius':'14px'}};
  }

  function floatInsert(tree, point, nudge) {
    if(!bridge || !lib.instantiate) return;
    var D=frame.contentDocument, main=D.querySelector('main#main');
    var host=point.host, hostKey=host===main ? 'm:'+route : bridge.keyOf(host);
    if(!hostKey || !keyAllowed(hostKey) || !canFrame(host)) { host=main; hostKey='m:'+route; }
    if(!main || !canFrame(main)) { insertLibrary(tree,'element'); return; }
    var r=host.getBoundingClientRect();
    var x=point.x-r.left-host.clientLeft+(nudge||0), y=point.y-r.top-host.clientTop+(nudge||0);
    var inst=lib.instantiate(tree);
    snapshot();
    lastGroup='insert';
    Object.keys(inst.nodes).forEach(function(k){doc.nodes[k]=inst.nodes[k];});
    var key='n:'+inst.tree.id, n=doc.nodes[key]=doc.nodes[key]||{};
    n.s=n.s||{};n.s.d=n.s.d||{};
    function at(l,tp){
      var spot={position:'absolute',left:Math.max(0,Math.round(l))+'px',top:Math.max(0,Math.round(tp))+'px','z-index':'20'};
      ['margin-top','margin-right','margin-bottom','margin-left'].forEach(function(m){delete n.s.d[m];});
      Object.keys(spot).forEach(function(k){n.s.d[k]=spot[k];});
      if(bp!=='d'){n.s[bp]=n.s[bp]||{};n.s[bp].left=spot.left;n.s[bp].top=spot.top;}
    }
    at(x-12,y-12);
    if(frame.contentWindow.getComputedStyle(host).position==='static'){
      var hn=nodeFor(hostKey,true);hn.s=hn.s||{};hn.s.d=hn.s.d||{};
      if(!hn.s.d.position) hn.s.d.position='relative';
    }
    placeTree(inst.tree,{key:hostKey,pos:'append'});
    changed(true);
    // Once it is drawn, centre it on the spot it was dropped on.
    setTimeout(function(){
      var el=D.querySelector('[data-ldrn-wsn="'+inst.tree.id+'"]');
      function centre(){if(el){at(x-el.offsetWidth/2,y-el.offsetHeight/2);changed();}}
      centre();
      // A photo only knows its height once it has loaded.
      var pending=el?Array.prototype.filter.call(el.tagName==='IMG'?[el]:el.querySelectorAll('img'),function(i){return !i.complete;}):[];
      pending.forEach(function(i){i.addEventListener('load',function(){centre();if(selected===key)select(key);},{once:true});});
      lastGroup=null;
      select(key);
    },140);
    toast('Placed on top of the page. Drag it to move it.');
  }

  /** Photos from the computer into the media library, one by one. */
  function uploadImages(files) {
    var list=Array.prototype.filter.call(files||[],function(f){return /^image\/(jpeg|png|webp|gif)$/.test(f.type);});
    if(!list.length){toast('Choose a JPG, PNG, WebP or GIF photo.',true);return Promise.resolve([]);}
    var done=[];
    toast('Uploading '+(list.length===1?'the photo':list.length+' photos')+'…');
    function one(file,again){
      var name=String(file.name||'photo').replace(/[^A-Za-z0-9._-]+/g,'-').slice(-90)||'photo.jpg';
      return fetch(cfg.restUrl,{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':cfg.restNonce,'Content-Disposition':'attachment; filename="'+name+'"','Content-Type':file.type},body:file})
        .then(function(r){
          if((r.status===401||r.status===403)&&!again) return refreshNonce().then(function(ok){if(!ok)throw new Error('Log in again to upload.');return one(file,true);});
          return r.json().then(function(m){if(!r.ok||!m||!m.source_url)throw new Error((m&&m.message)||'The photo could not be uploaded.');return m;});
        });
    }
    return list.reduce(function(chain,file){
      return chain.then(function(){return one(file,false).then(function(m){done.push({url:m.source_url,alt:(m.alt_text||'').trim(),id:m.id});});});
    },Promise.resolve()).then(function(){
      toast(done.length===1?'Photo uploaded.':done.length+' photos uploaded.');
      if(leftTab==='media')renderLeft();
      return done;
    }).catch(function(err){toast(err.message||'The photo could not be uploaded.',true);if(leftTab==='media')renderLeft();return done;});
  }

  /** "Upload image": choose photos on the computer, then they go where asked. */
  var uploadForm=(function(){
    var pending=null;
    var input=h('input',{type:'file',accept:'image/jpeg,image/png,image/webp,image/gif',multiple:'multiple',hidden:'hidden'});
    input.addEventListener('change',function(){
      var files=input.files,spot=pending;pending=null;
      if(!files||!files.length)return;
      uploadImages(files).then(function(list){
        list.forEach(function(m,i){
          if(spot) floatInsert(floatImage(m.url,m.alt),spot,i*24);
          else if(i===0 && selIsImage()) useImage(m.url,m.alt);
          else insertLibrary(floatImage(m.url,m.alt),'element');
        });
        input.value='';
      });
    });
    document.body.appendChild(input);
    return {open:function(spot){pending=spot||null;input.click();}};
  })();
  function saveReusable(key, name) {
    var existing=root.querySelector('.wws-reusable-dialog');if(existing)existing.remove();
    var input=h('input',{class:'wws-in',type:'text',maxlength:'80',value:name||'My section','aria-label':'Section name'});
    var panel=h('div',{class:'wws-menu wws-reusable-dialog',role:'dialog','aria-modal':'true','aria-label':'Save reusable section',style:'position:fixed;top:120px;right:24px;width:320px;z-index:10000',onclick:function(e){e.stopPropagation();}},[
      h('div',{class:'wws-menu-head',text:'Save to My sections'}),input,
      h('p',{class:'wws-hint',text:'Keep this section, its child edits and responsive styles for use on another page.'}),
      h('div',{class:'wws-btns'},[
        h('button',{type:'button',class:'wws-btn wws-btn-primary',onclick:function(){var title=input.value.trim();if(!title){input.focus();return;}panel.remove();saveReusableNamed(key,title);}},['Save section']),
        h('button',{type:'button',class:'wws-btn',onclick:function(){panel.remove();}},['Cancel'])])]);
    input.addEventListener('keydown',function(e){if(e.key==='Escape'){panel.remove();e.stopPropagation();}if(e.key==='Enter'){e.preventDefault();var title=input.value.trim();if(title){panel.remove();saveReusableNamed(key,title);}}});
    root.appendChild(panel);input.focus();input.select();
  }
  function saveReusableNamed(key, title) {
    var tree=bridge.snapshot(key);
    if(!tree){toast('Choose a section that can be copied.',true);return;}
    var nodes={};
    (function walk(node){
      var entry=doc.nodes[node.from||('n:'+node.id)];
      if(entry) {nodes['n:'+node.id]=JSON.parse(JSON.stringify(entry));delete nodes['n:'+node.id].f;}
      delete node.from;
      (node.kids||[]).forEach(walk);
    })(tree);
    post('ldrn_ws_template_save',{name:title,template:JSON.stringify({tree:tree,nodes:nodes})}).then(function(result){
      addSavedTemplate(result.template);leftTab='assets';assetsTab='templates';renderLeft();toast('Saved to My sections. Drag it onto any page.');
    }).catch(function(error){toast(error.message,true);});
  }
  var assetsTab = 'templates';
  var pageForm = null; // null | { mode: 'new' } | { mode: 'edit', id }

  function normalise(d) {
    d = d && typeof d === 'object' ? JSON.parse(JSON.stringify(d)) : {};
    if (!d.nodes || typeof d.nodes !== 'object' || Array.isArray(d.nodes)) d.nodes = {};
    if (!d.blocks || typeof d.blocks !== 'object' || Array.isArray(d.blocks)) d.blocks = {};
    if (!d.groups || typeof d.groups !== 'object' || Array.isArray(d.groups)) d.groups = {};
    if (!d.layerOrder || typeof d.layerOrder !== 'object' || Array.isArray(d.layerOrder)) d.layerOrder = {};
    Object.keys(d.nodes).forEach(function (k) {
      var n = d.nodes[k];
      if (n && n.s && Array.isArray(n.s)) n.s = {};
      if (n && n.s) ['d', 't', 'p'].forEach(function (b) { if (Array.isArray(n.s[b])) delete n.s[b]; });
    });
    return d;
  }

  /* ------------------------------------------------------------ helpers */

  function h(tag, attrs, kids) {
    var el = document.createElement(tag);
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        var v = attrs[k];
        if (v === null || v === undefined || v === false) return;
        if (k === 'class') el.className = v;
        else if (k === 'text') el.textContent = v;
        else if (k === 'html') el.innerHTML = v;
        else if (k.indexOf('on') === 0) el.addEventListener(k.slice(2), v);
        else if (k === 'style') el.style.cssText = v;
        else el.setAttribute(k, v === true ? '' : v);
      });
    }
    (kids || []).forEach(function (kid) {
      if (kid === null || kid === undefined || kid === false) return;
      el.appendChild(typeof kid === 'string' ? document.createTextNode(kid) : kid);
    });
    return el;
  }

  var ICONS = {
    text: '<path d="M4 4h8M8 4v8" />',
    image: '<rect x="2.5" y="3.5" width="11" height="9" rx="1.5"/><circle cx="6" cy="7" r="1.2"/><path d="M3 12l3.5-3.5L9 11l2-2 2.5 2.5"/>',
    link: '<path d="M7 9l2-2M6.5 5.5l1-1a2.5 2.5 0 013.5 3.5l-1 1M9.5 10.5l-1 1a2.5 2.5 0 01-3.5-3.5l1-1"/>',
    button: '<rect x="2.5" y="5" width="11" height="6" rx="3"/>',
    graphic: '<path d="M8 2.5l5.5 10h-11z"/>',
    input: '<rect x="2.5" y="4.5" width="11" height="7" rx="1"/><path d="M5 6.5v3"/>',
    stack: '<rect x="3" y="2.5" width="10" height="4" rx="1"/><rect x="3" y="9.5" width="10" height="4" rx="1"/>',
    grid: '<rect x="2.5" y="2.5" width="4.5" height="4.5" rx="1"/><rect x="9" y="2.5" width="4.5" height="4.5" rx="1"/><rect x="2.5" y="9" width="4.5" height="4.5" rx="1"/><rect x="9" y="9" width="4.5" height="4.5" rx="1"/>',
    section: '<rect x="2" y="3" width="12" height="10" rx="1"/><path d="M2 6h12"/>',
    frame: '<path d="M5 2v12M11 2v12M2 5h12M2 11h12"/>',
    page: '<path d="M4 2.5h5.5L12 5v8.5H4z"/><path d="M9.5 2.5V5H12"/>',
    eye: '<path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8 12 12.5 8 12.5 1.5 8 1.5 8z"/><circle cx="8" cy="8" r="2"/>',
    eyeOff: '<path d="M2 2l12 12M6.5 4a6 6 0 011.5-.5c4 0 6.5 4.5 6.5 4.5a11 11 0 01-1.8 2.3M4 5.5C2.4 6.7 1.5 8 1.5 8S4 12.5 8 12.5a6 6 0 002.5-.5"/>',
    up: '<path d="M8 12V4M4.5 7.5L8 4l3.5 3.5"/>',
    down: '<path d="M8 4v8M4.5 8.5L8 12l3.5-3.5"/>',
    caret: '<path d="M6 4l4 4-4 4"/>',
    undo: '<path d="M5 6.5H10a3 3 0 010 6H7"/><path d="M7 3.5l-3 3 3 3"/>',
    redo: '<path d="M11 6.5H6a3 3 0 000 6h3"/><path d="M9 3.5l3 3-3 3"/>',
    play: '<path d="M5 3.5l8 4.5-8 4.5z"/>',
    cursor: '<path d="M4 2.5l8 5.2-3.6.8-1.6 3.4z"/>',
    more: '<circle cx="4" cy="8" r=".9"/><circle cx="8" cy="8" r=".9"/><circle cx="12" cy="8" r=".9"/>',
    back: '<path d="M10 3.5L5.5 8l4.5 4.5"/>',
    open: '<path d="M9 3h4v4M13 3L7.5 8.5M11.5 9.5V13h-9V4H6"/>',
    reset: '<path d="M3 8a5 5 0 105-5H5"/><path d="M6.5 1.5L5 3l1.5 1.5"/>',
    close: '<path d="M4 4l8 8M12 4l-8 8"/>',
    upload: '<path d="M8 11V3M4.5 6.5L8 3l3.5 3.5M3 13h10"/>',
    trash: '<path d="M3 4.5h10M6.5 4.5V3h3v1.5M4.5 4.5l.7 8.5h5.6l.7-8.5"/>',
    copy: '<rect x="5" y="5" width="8" height="8" rx="1.2"/><path d="M3 10.5V3h7.5"/>',
    plus: '<path d="M8 3v10M3 8h10"/>',
    pen: '<path d="M10.5 3l2.5 2.5L6 12.5H3.5V10z"/>',
    menu: '<path d="M3 5h10M3 8h10M3 11h10"/>',
  };

  function icon(name) {
    var span = document.createElement('span');
    span.className = 'i';
    span.innerHTML = '<svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">' + (ICONS[name] || '') + '</svg>';
    return span;
  }

  function toast(message, bad) {
    var t = h('div', { class: 'wws-toast' + (bad ? ' is-bad' : ''), text: message });
    root.appendChild(t);
    setTimeout(function () { t.classList.add('is-in'); }, 10);
    // Something that went wrong stays long enough to be read.
    setTimeout(function () { t.classList.remove('is-in'); setTimeout(function () { t.remove(); }, 300); }, bad ? 7000 : 2800);
  }

  /**
   * WordPress answers "-1" or "0" (not JSON) when the login or its security
   * token has expired, and an HTML page when the server itself failed. Say
   * which, in words someone can act on.
   */
  function friendlyFailure(status, body) {
    var text = String(body || '').trim();
    var err;
    if (text === '-1' || text === '0' || status === 403) {
      err = new Error('You have been logged out. Log in again in another tab - this screen saves by itself as soon as you have, and nothing on it is lost.');
      err.auth = true;
    } else {
      err = new Error('The server did not answer properly (' + status + '). Your changes are kept here; trying again shortly.');
      err.offline = true;
    }
    return err;
  }

  /**
   * A fresh security token. The one the editor was opened with stops working
   * after a day, and as soon as the login is renewed in another tab (it
   * belongs to the old login). Resolves false when nobody is logged in.
   */
  function refreshNonce() {
    return fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=ldrn_ws_studio_nonce',
    }).then(function (r) { return r.json(); }).then(function (res) {
      if (!res || !res.success || !res.data || !res.data.nonce) return false;
      cfg.nonce = res.data.nonce;
      if (res.data.restNonce) cfg.restNonce = res.data.restNonce;
      return true;
    }).catch(function () { return false; });
  }

  /** send(), once more with a fresh token if the old one was refused. */
  function post(action, data) {
    return send(action, data).catch(function (e) {
      if (!e.auth) throw e;
      return refreshNonce().then(function (ok) {
        if (!ok) throw e;
        return send(action, data);
      });
    });
  }

  function send(action, data) {
    var body = new URLSearchParams();
    body.set('action', action);
    body.set('nonce', cfg.nonce);
    Object.keys(data || {}).forEach(function (k) { body.set(k, data[k]); });
    return fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
    }).then(function (r) {
      return r.text().then(function (body) {
        var res = null;
        try { res = JSON.parse(body); } catch (e) { /* not JSON - see below */ }
        // "-1" parses as JSON too - anything but an object is not an answer.
        if (!res || typeof res !== 'object') throw friendlyFailure(r.status, body);
        return res;
      });
    }, function () {
      var err = new Error('The connection dropped. Your changes are kept here and will be saved as soon as it is back.');
      err.offline = true;
      throw err;
    }).then(function (res) {
      if (!res || !res.success) {
        var err = new Error((res && res.data && res.data.message) || 'The server said no.');
        err.data = res && res.data;
        throw err;
      }
      if (res.data && res.data.status && res.data.status.rev) rev = res.data.status.rev;
      return res.data;
    });
  }

  /* ---------------------------------------------------- the document */

  function nodeFor(key, create) {
    if (!doc.nodes[key] && create) {
      doc.nodes[key] = {};
      // Remember what this edit is made on, so a rebuilt site cannot move it
      // onto something else (see resolveKeys() in the runtime).
      var f = bridge && bridge.fingerprint ? bridge.fingerprint(key) : '';
      if (f) doc.nodes[key].f = f;
    }
    return doc.nodes[key] || null;
  }

  function prune(key) {
    var n = doc.nodes[key];
    if (!n) return;
    if (n.s) {
      ['d', 't', 'p'].forEach(function (b) { if (n.s[b] && !Object.keys(n.s[b]).length) delete n.s[b]; });
      if (!Object.keys(n.s).length) delete n.s;
    }
    // A fingerprint on its own is not an edit.
    if (!Object.keys(n).filter(function (k) { return k !== 'f'; }).length) delete doc.nodes[key];
  }

  /** What this layer is set to on this breakpoint - explicitly, not inherited. */
  function own(key, prop, b) {
    var n = doc.nodes[key];
    var v = n && n.s && n.s[b || bp] && n.s[b || bp][prop];
    return v === undefined ? '' : String(v);
  }

  /** What applies on this breakpoint once inheritance from larger ones is counted. */
  function effective(key, prop) {
    var order = bp === 'p' ? ['p', 't', 'd'] : bp === 't' ? ['t', 'd'] : ['d'];
    for (var i = 0; i < order.length; i++) {
      var v = own(key, prop, order[i]);
      if (v !== '') return v;
    }
    return '';
  }

  function snapshot(group) {
    var now = Date.now();
    if (group && group === lastGroup && now - lastGroupAt < 900) { lastGroupAt = now; return; }
    lastGroup = group || null;
    lastGroupAt = now;
    undoStack.push(JSON.stringify(doc));
    if (undoStack.length > 200) undoStack.shift();
    redoStack = [];
  }

  function setStyle(key, prop, value, group) {
    if (!keyAllowed(key)) return;
    value = value === null || value === undefined ? '' : String(value).trim();
    if (own(key, prop) === value) return;
    snapshot(group || key + '|' + prop + '|' + bp);
    var n = nodeFor(key, true);
    n.s = n.s || {};
    n.s[bp] = n.s[bp] || {};
    if (value === '') delete n.s[bp][prop];
    else n.s[bp][prop] = value;
    prune(key);
    changed();
  }

  /**
   * Several style edits as one step - what a drag, a resize or a group
   * action on the canvas produces. `changes` is [{ key, s: { prop: value } }]
   * for the breakpoint being edited; an empty value removes the edit.
   * Nudges with the arrow keys in quick succession count as one step.
   */
  function setStyles(changes, group) {
    changes = (changes || []).filter(function (c) { return c && keyAllowed(c.key) && typeof c.key === 'string' && c.s && typeof c.s === 'object'; });
    if (!changes.length) return;
    snapshot(group === 'nudge' ? 'nudge|' + changes.map(function (c) { return c.key; }).join(',') + '|' + bp : group || null);
    changes.forEach(function (c) {
      var n = nodeFor(c.key, true);
      n.s = n.s || {};
      n.s[bp] = n.s[bp] || {};
      Object.keys(c.s).forEach(function (prop) {
        var v = c.s[prop] === null || c.s[prop] === undefined ? '' : String(c.s[prop]).trim();
        if (v === '') delete n.s[bp][prop];
        else n.s[bp][prop] = v;
      });
      prune(c.key);
    });
    changed();
  }

  var GEOMETRY = ['position', 'top', 'right', 'bottom', 'left', 'width', 'height', 'max-width', 'min-width', 'max-height',
    'min-height', 'flex', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left'];

  function hasGeometry(key) {
    return GEOMETRY.some(function (p) { return own(key, p) !== ''; });
  }

  /** Back to where and how big the design had it, on this breakpoint. */
  function resetGeometry(keys) {
    setStyles(keys.map(function (k) {
      var s = {};
      GEOMETRY.forEach(function (p) { s[p] = ''; });
      return { key: k, s: s };
    }));
    toast('Position and size are back to the design on ' + DEVICES[bp].label + '.');
  }

  function hideMany(keys) {
    var infos = keys.map(function (k) { return bridge ? bridge.info(k) : null; });
    var hide = keys.filter(function (k, i) { return !(infos[i] && infos[i].inserted) && !/^m:[^>]*$/.test(k); });
    setStyles(hide.map(function (k) { return { key: k, s: { display: 'none' } }; }));
    keys.forEach(function (k, i) { if (infos[i] && infos[i].inserted) removeInserted(k.slice(2), true); });
    select(null);
    toast(keys.length + ' layers hidden on ' + DEVICES[bp].label + '. Undo brings them back.');
  }

  function setContent(key, field, value, group) {
    if (!keyAllowed(key)) return;
    var existing = doc.nodes[key];
    if (existing && existing[field] === value) return;
    if (!existing && (value === null || value === undefined)) return;
    snapshot(group || key + '|' + field);
    var n = nodeFor(key, true);
    // Plain text from the panel replaces styled runs typed on the canvas.
    if (field === 'text') delete n.rich;
    if (value === null || value === undefined) delete n[field];
    else n[field] = value;
    prune(key);
    changed();
  }

  function resetLayer(key, allBreakpoints) {
    snapshot();
    var n = doc.nodes[key];
    if (!n) return;
    if (allBreakpoints) delete doc.nodes[key];
    else if (n.s) { delete n.s[bp]; prune(key); }
    changed(true);
  }

  /**
   * On Tablet and Phone, say so when an edit runs off the screen - the
   * thing a desktop-sized drag most easily does - and offer to select it.
   */
  var overflowList = [];
  function checkOverflow() {
    overflowList = bridge && bridge.overflow && bp !== 'd' ? bridge.overflow() : [];
    var pill = document.querySelector('.wws-overflow:not(.wws-orphans)');
    if (!overflowList.length) { if (pill) pill.remove(); return; }
    if (!pill) {
      pill = h('button', { type: 'button', class: 'wws-overflow', onclick: function () {
        var first = overflowList[0];
        if (first) { select(first.key); if (bridge) bridge.scrollTo(first.key); }
      } });
      var c = document.querySelector('.wws-top-c');
      if (c) c.appendChild(pill);
    }
    pill.textContent = '⚠ ' + overflowList.length + (overflowList.length === 1 ? ' layer runs' : ' layers run') + ' off the ' + DEVICES[bp].label + ' screen';
    pill.title = overflowList.map(function (o) { return o.name + ' (' + o.over + 'px)'; }).join('\n') + '\nClick to select it.';
  }
  var overflowTimer = null;
  function checkOverflowSoon() { clearTimeout(overflowTimer); overflowTimer = setTimeout(checkOverflow, 250); }

  /** Edits made before fingerprints existed get one, from the page as it is now. */
  function backfillFingerprints() {
    if (cfg.scoped) return false;
    if (!bridge || !bridge.fingerprint) return;
    var lost = bridge.orphans ? bridge.orphans() : [];
    var added = 0;
    Object.keys(doc.nodes).forEach(function (k) {
      var n = doc.nodes[k];
      if (!n || n.f || lost.indexOf(k) !== -1) return;
      if (k.indexOf('m:') === 0 && k.split('>')[0] !== 'm:' + route) return;
      var f = bridge.fingerprint(k);
      if (f) { n.f = f; added++; }
    });
    Object.keys(doc.blocks).forEach(function (id) {
      var b = doc.blocks[id];
      if (!b || b.af || !b.at || lost.indexOf(b.at) !== -1) return;
      if (b.at.indexOf('m:') === 0 && b.at.split('>')[0] !== 'm:' + route) return;
      var f = bridge.fingerprint(b.at);
      if (f) { b.af = f; added++; }
    });
    return added;
  }

  /**
   * Edits whose element is gone from this page (the site was rebuilt and it
   * was removed or reshaped). They are applied nowhere - say so, and offer
   * to clear them.
   */
  var orphanTimer = null;
  function checkOrphans(retry) {
    var lost = bridge && bridge.orphans ? bridge.orphans() : [];
    var pill = document.querySelector('.wws-orphans');
    if (!lost.length) { if (pill) pill.remove(); return; }
    // A page that is still loading its content looks as if every edit lost
    // its element. Look again shortly so the warning only stays when real.
    retry = typeof retry === 'number' ? retry : 0;
    clearTimeout(orphanTimer);
    if (retry < 8) orphanTimer = setTimeout(function () { checkOrphans(retry + 1); }, 750);
    if (!pill) {
      pill = h('button', { type: 'button', class: 'wws-overflow wws-orphans', onclick: function () {
        var now = bridge.orphans();
        if (!now.length) return;
        if (!window.confirm(now.length + (now.length === 1 ? ' edit was' : ' edits were') + ' made on parts of this page that are no longer there (the site was rebuilt since). They are not showing anywhere.\n\nClear ' + (now.length === 1 ? 'it' : 'them') + '? Undo brings ' + (now.length === 1 ? 'it' : 'them') + ' back.')) return;
        snapshot();
        now.forEach(function (k) { delete doc.nodes[k]; });
        changed(true);
      } });
      var c = document.querySelector('.wws-top-c');
      if (c) c.appendChild(pill);
    }
    pill.textContent = '⚠ ' + lost.length + (lost.length === 1 ? ' edit no longer fits' : ' edits no longer fit') + ' this page';
  }

  function changed(rebuild) {
    localDirty = true;
    backfillFingerprints();
    if (bridge) bridge.setDoc(doc);
    checkOverflowSoon();
    scheduleSave();
    renderTop();
    refreshSelection(rebuild);
    renderLayersSoon();
    updateInsertHint();
  }

  function undo() {
    if (!undoStack.length) return;
    redoStack.push(JSON.stringify(doc));
    doc = normalise(JSON.parse(undoStack.pop()));
    lastGroup = null;
    changed(true);
  }

  function redo() {
    if (!redoStack.length) return;
    undoStack.push(JSON.stringify(doc));
    doc = normalise(JSON.parse(redoStack.pop()));
    lastGroup = null;
    changed(true);
  }

  /* ------------------------------------------------------------ saving */

  function scheduleSave() {
    if (conflict) { renderTop(); return; }
    saveState = 'unsaved';
    clearTimeout(saveTimer);
    if (!publishing) saveTimer = setTimeout(saveNow, 900);
    renderTop();
  }

  function saveNow() {
    clearTimeout(saveTimer);
    if (activeSave) return activeSave.then(function () { if (localDirty && !conflict) return saveNow(); });
    saveState = 'saving';
    renderTop();
    var sent = JSON.stringify(doc);
    activeSave = post('ldrn_ws_studio_save', { doc: sent, rev: rev })
      .then(function (data) {
        status = data.status;
        retryDelay = 0;
        if (JSON.stringify(doc) === sent) { saveState = 'saved'; localDirty = false; dropKept(); }
        renderTop();
      })
      .catch(function (e) {
        keepLocally();
        if (e.data && e.data.conflict) { showConflict(e.data); return; }
        saveState = 'error';
        renderTop();
        toast('Draft not saved: ' + e.message, true);
        // A dropped connection or a server hiccup is retried without anyone
        // having to make another edit; a logged-out editor saves as soon as
        // the login is back.
        if (e.auth) waitForLogin();
        else {
          clearTimeout(retryTimer);
          retryDelay = Math.min(60000, retryDelay ? retryDelay * 2 : 4000);
          retryTimer = setTimeout(function () { if (saveState === 'error' && !conflict) saveNow(); }, retryDelay);
        }
      }).finally(function () { activeSave = null; });
    return activeSave;
  }

  var retryTimer = null;
  var retryDelay = 0;

  /** Logged out: look now and then (quietly) whether they are back. */
  var loginTimer = null;
  function waitForLogin() {
    clearTimeout(loginTimer);
    loginTimer = setTimeout(function () {
      refreshNonce().then(function (ok) {
        if (!ok) { waitForLogin(); return; }
        if (saveState === 'error' && !conflict) saveNow();
      });
    }, 8000);
  }

  /**
   * Work that could not be saved is kept in this browser too, so a closed
   * tab or a crash while the connection or the login is down loses nothing;
   * the editor offers it back when it next opens (see offerKept()).
   */
  var KEPT = 'ldrnStudioKept:' + cfg.base + ':' + cfg.userId;
  function keepLocally() {
    try { localStorage.setItem(KEPT, JSON.stringify({ doc: doc, rev: rev, t: Date.now() })); } catch (e) { /* private window or full */ }
  }
  function dropKept() {
    try { localStorage.removeItem(KEPT); } catch (e) {}
  }

  function offerKept() {
    var kept = null;
    try { kept = JSON.parse(localStorage.getItem(KEPT) || 'null'); } catch (e) {}
    if (!kept || !kept.doc || typeof kept.t !== 'number') return;
    // Saved after all (the tab closed while the save was on its way), or too
    // old to be what anyone remembers doing.
    if (JSON.stringify(normalise(kept.doc)) === JSON.stringify(doc) || Date.now() - kept.t > 30 * 864e5) { dropKept(); return; }
    var when = new Date(kept.t);
    var box = h('div', { class: 'wws-conflict wws-kept', role: 'alertdialog' }, [
      h('div', { class: 'wws-conflict-card' }, [
        h('p', { class: 'wws-conflict-title', text: 'Unsaved changes were found in this browser' }),
        h('p', { text: 'On ' + when.toLocaleDateString() + ' at ' + when.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ', changes made here could not be saved before the editor was closed - the connection or the login had dropped.' + (kept.rev && kept.rev !== rev ? ' The draft has been saved since then, from here or somewhere else.' : '') + ' Bringing them back replaces what is on the canvas now; Undo goes back to it.' }),
        h('div', { class: 'wws-btns' }, [
          h('button', {
            type: 'button', class: 'wws-btn wws-btn-primary', onclick: function () {
              box.remove();
              snapshot();
              doc = normalise(kept.doc);
              lastGroup = null;
              changed(true);
              toast('Your unsaved changes are back. Undo returns to the saved draft.');
            },
          }, ['Bring them back']),
          h('button', { type: 'button', class: 'wws-btn', onclick: function () { box.remove(); dropKept(); } }, ['Throw them away']),
        ]),
      ]),
    ]);
    document.body.appendChild(box);
  }

  /**
   * Someone else moved the draft on - another tab, another person, a
   * restored session. Nothing is saved until the person here decides, and
   * neither version is lost whichever they choose.
   */
  function showConflict(data) {
    conflict = data;
    clearTimeout(saveTimer);
    saveState = 'error';
    renderTop();
    var old = document.querySelector('.wws-conflict');
    if (old) old.remove();
    var box = h('div', { class: 'wws-conflict', role: 'alertdialog' }, [
      h('div', { class: 'wws-conflict-card' }, [
        h('p', { class: 'wws-conflict-title', text: 'This draft was changed somewhere else' }),
        h('p', { text: 'Since you opened the editor, the draft was saved from another tab or by someone else, or a saved session was restored. To keep both safe, nothing has been saved from here yet.' }),
        h('div', { class: 'wws-btns' }, [
          h('button', {
            type: 'button', class: 'wws-btn wws-btn-primary', onclick: function () {
              var mine = JSON.stringify(doc);
              box.remove();
              conflict = null;
              if (data.status) { status = data.status; rev = data.status.rev || rev; }
              replaceDoc(data.doc, 'Loaded the latest draft. Undo brings your version back.');
              void mine;
            },
          }, ['Load the latest']),
          h('button', {
            type: 'button', class: 'wws-btn', onclick: function () {
              box.remove();
              conflict = null;
              saveState = 'saving';
              renderTop();
              post('ldrn_ws_studio_save', { doc: JSON.stringify(doc), rev: rev, force: '1' }).then(function (res) {
                status = res.status;
                saveState = 'saved';
                localDirty = false;
                dropKept();
                renderTop();
                toast('Your version is saved over the other one. It is in All saved sessions only once published.');
              }).catch(function (e) { saveState = 'error'; keepLocally(); renderTop(); toast('Draft not saved: ' + e.message, true); });
            },
          }, ['Keep mine']),
        ]),
      ]),
    ]);
    document.body.appendChild(box);
  }

  function publish(name) {
    if (publishing) return;
    clearTimeout(saveTimer);
    clearTimeout(retryTimer);
    publishing = true;
    saveState = 'saving';
    renderTop();
    if (conflict) { publishing = false; showConflict(conflict); return; }
    var sent;
    // Wait for an in-flight autosave to return its new revision, then
    // publish the current document without racing this tab's own save.
    (activeSave || Promise.resolve()).then(function () {
      if (conflict) { showConflict(conflict); return null; }
      sent = JSON.stringify(doc);
      return post('ldrn_ws_studio_publish', { doc: sent, name: name || '', rev: rev });
    })
      .then(function (data) {
        if (!data) return;
        publishName = '';
        status = data.status;
        saveState = 'saved';
        localDirty = JSON.stringify(doc) !== sent;
        if (!localDirty) dropKept();
        renderTop();
        toast('Published' + (name ? ' as “' + name + '”' : '') + ' and saved to All saved sessions.');
      })
      .catch(function (e) {
        if (e.data && e.data.conflict) { showConflict(e.data); return; }
        saveState = 'error';
        keepLocally();
        renderTop();
        toast('Not published: ' + e.message, true);
      }).finally(function () {
        publishing = false;
        if (localDirty && !conflict && saveState !== 'error') scheduleSave();
        else renderTop();
      });
  }

  function replaceDoc(next, message) {
    snapshot();
    doc = normalise(next);
    lastGroup = null;
    if (bridge) bridge.setDoc(doc);
    localDirty = false;
    saveState = 'saved';
    renderTop();
    refreshSelection(true);
    renderLayers();
    if (message) toast(message);
  }

  /* ------------------------------------------------------------ skeleton */

  root.innerHTML = '';
  var elTop = h('header', { class: 'wws-top' });
  var elLeft = h('aside', { class: 'wws-left' });
  var elCanvas = h('main', { class: 'wws-canvas' });
  var elRight = h('aside', { class: 'wws-right' });
  var shell = h('div', { class: 'wws' }, [elTop, elLeft, elCanvas, elRight]);
  root.appendChild(shell);

  var frameLabel = h('div', { class: 'wws-frame-label' });
  var frame = h('iframe', { class: 'wws-frame', title: 'Site canvas' });
  var device = h('div', { class: 'wws-device' }, [frame]);
  var stage = h('div', { class: 'wws-stage' }, [frameLabel, device]);
  var loading = h('div', { class: 'wws-loading', text: 'Loading the page…' });
  elCanvas.appendChild(stage);
  elCanvas.appendChild(loading);

  /* ------------------------------------------------------------ top bar */

  var menuOpen = false;

  function renderTop() {
    // The warning pills live in this bar: carry them across the redraw so
    // they never blink out (the checks bring them up to date right after).
    var pills = Array.prototype.slice.call(elTop.querySelectorAll('.wws-overflow'));
    elTop.innerHTML = '';
    setTimeout(checkOverflow, 0);
    setTimeout(checkOrphans, 0);
    var pageLabel = labelForRoute(route);

    var left = h('div', { class: 'wws-top-l' }, [
      h('a', { class: 'wws-icon-btn', href: cfg.exit, title: 'Back to WordPress' }, [icon('back')]),
      h('span', { class: 'wws-brand', text: 'Little Rainbows' }),
      h('span', { class: 'wws-divider' }),
      h('button', {
        class: 'wws-page-pill', type: 'button', title: 'Pages',
        onclick: function () { leftTab = 'pages'; renderLeft(); },
      }, [icon('page'), h('span', { text: pageLabel })]),
      h('button', {
        class: 'wws-sessions-btn', type: 'button', title: 'Every published version of the whole site - open or restore any of them',
        onclick: openSessions,
      }, [icon('undo'), h('span', { text: 'All saved sessions' })]),
    ]);

    var devices = h('div', { class: 'wws-seg wws-devices' }, Object.keys(DEVICES).map(function (k) {
      return h('button', {
        type: 'button', class: bp === k ? 'is-on' : '', title: DEVICES[k].label + ' · ' + DEVICES[k].width,
        onclick: function () { setBreakpoint(k); },
      }, [DEVICES[k].label]);
    }));
    var zoomSel = h('select', {
      class: 'wws-zoom', title: 'Zoom',
      onchange: function (e) { zoom = e.target.value === 'fit' ? 'fit' : parseFloat(e.target.value); layoutCanvas(); },
    }, zoomOptions().map(function (o) {
      var opt = h('option', { value: o[0], text: o[1] });
      if (String(zoom) === o[0]) opt.selected = true;
      return opt;
    }));
    var centre = h('div', { class: 'wws-top-c' }, [devices, zoomSel]);

    var saveText = saveState === 'saving' ? 'Saving…' :
      saveState === 'unsaved' ? 'Unsaved' :
      saveState === 'error' ? 'Not saved' :
      status.unpublished ? 'Draft saved · not published' : 'Published';

    var unpublished = localDirty || status.unpublished;

    var modes = h('div', { class: 'wws-seg wws-modes' }, [
      h('button', { type: 'button', class: mode === 'select' ? 'is-on' : '', title: 'Select (V)', onclick: function () { setMode('select'); } }, [icon('cursor')]),
      h('button', { type: 'button', class: mode === 'preview' ? 'is-on' : '', title: 'Preview - click through the site (P)', onclick: function () { setMode('preview'); } }, [icon('play')]),
    ]);

    var right = h('div', { class: 'wws-top-r' }, [
      h('button', { class: 'wws-icon-btn', type: 'button', title: 'Undo (Ctrl+Z)', disabled: !undoStack.length, onclick: undo }, [icon('undo')]),
      h('button', { class: 'wws-icon-btn', type: 'button', title: 'Redo (Ctrl+Shift+Z)', disabled: !redoStack.length, onclick: redo }, [icon('redo')]),
      h('span', { class: 'wws-save wws-save-' + saveState, text: saveText }),
      modes,
      h('button', { class: 'wws-icon-btn', type: 'button', title: 'More', onclick: function (e) { e.stopPropagation(); menuOpen = !menuOpen; renderTop(); } }, [icon('more')]),
      h('a', { class: 'wws-icon-btn', href: pageUrl(route), target: '_blank', rel: 'noopener', title: 'Open the live page' }, [icon('open')]),
      h('button', {
        class: 'wws-publish', type: 'button', disabled: publishing || (!unpublished && saveState !== 'error'),
        onclick: function (e) { e.stopPropagation(); publishOpen = !publishOpen; menuOpen = false; renderTop(); },
      }, ['Publish']),
    ]);

    elTop.appendChild(left);
    elTop.appendChild(centre);
    pills.forEach(function (p) { centre.appendChild(p); });
    elTop.appendChild(right);
    if (cfg.scoped) elTop.querySelectorAll('.wws-sessions-btn,[title="More"]').forEach(function(el){el.remove();});
    if (menuOpen) elTop.appendChild(renderMenu());
    if (publishOpen) elTop.appendChild(renderPublishBox());
  }

  var publishOpen = false;
  var publishName = '';

  /** Publish asks for a name, the way a save point in version history should. */
  function renderPublishBox() {
    var input = h('input', { type: 'text', class: 'wws-in', placeholder: 'Name this version (optional)', maxlength: '80', value: publishName,
      oninput: function (event) { publishName = event.target.value; } });
    function go() { publishOpen = false; publish(input.value.trim()); }
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') go();
      if (e.key === 'Escape') { publishOpen = false; publishName = ''; renderTop(); }
    });
    setTimeout(function () { input.focus(); }, 0);
    return h('div', { class: 'wws-menu wws-publish-box', onclick: function (e) { e.stopPropagation(); } }, [
      h('div', { class: 'wws-menu-head', text: 'Publish to the live site' }),
      input,
      h('p', { class: 'wws-hint', text: 'Saved to All saved sessions, so this exact site can be opened or restored later.' }),
      h('div', { class: 'wws-btns' }, [
        h('button', { type: 'button', class: 'wws-btn wws-btn-primary', onclick: go }, ['Publish']),
        h('button', { type: 'button', class: 'wws-btn', onclick: function () { publishOpen = false; publishName = ''; renderTop(); } }, ['Cancel']),
      ]),
    ]);
  }

  function openSessions() {
    if (cfg.scoped) return;
    var go = function () { window.location.href = cfg.sessionsUrl; };
    if (saveState === 'unsaved' || saveState === 'saving') saveNow().then(go, go);
    else go();
  }

  function renderMenu() {
    return h('div', { class: 'wws-menu', onclick: function (e) { e.stopPropagation(); } }, [
      h('button', { type: 'button', class: 'wws-menu-item', onclick: openSessions }, [
        h('span', { text: 'All saved sessions' }),
        h('small', { text: 'Every published version of the whole site' }),
      ]),
      h('div', { class: 'wws-menu-sep' }),
      h('button', {
        type: 'button', class: 'wws-menu-item',
        onclick: function () {
          menuOpen = false;
          if (!window.confirm('Throw away every change since the last publish?')) { renderTop(); return; }
          post('ldrn_ws_studio_discard', {rev:rev}).then(function (data) {
            status = data.status;
            pagesChanged(data.pages);
            replaceDoc(data.doc, 'Back to what is live.');
          }).catch(function (e) { toast(e.message, true); });
        },
      }, [h('span', { text: 'Discard unpublished changes' })]),
      h('div', { class: 'wws-menu-sep' }),
      h('div', { class: 'wws-menu-keys', html:
        '<b>V</b> select · <b>P</b> preview · <b>Double-click</b> type<br>' +
        '<b>Drag</b> a selected layer to move · <b>Handles</b> resize (<b>Shift</b> keeps proportions)<br>' +
        '<b>Drag</b> elsewhere or <b>Ctrl drag</b> to box-select · <b>Shift click</b> add to selection<br>' +
        '<b>Arrows</b> nudge 1px · <b>Shift arrows</b> 10px<br>' +
        '<b>Esc</b> parent layer · <b>Delete</b> delete / hide · <b>Alt ↑↓</b> reorder<br>' +
        '<b>Ctrl D</b> duplicate · <b>Enter</b> type into selected text<br>' +
        '<b>Ctrl Z</b> undo · <b>Ctrl ⇧ Z</b> redo · <b>Ctrl S</b> save' }),
    ]);
  }

  document.addEventListener('click', function () { if (menuOpen || publishOpen) { menuOpen = false; publishOpen = false; renderTop(); } });

  /* ------------------------------------------------------------ canvas */

  var ZOOMS = [0.25, 0.5, 0.75, 1, 1.5, 2];

  /** The zoom menu - the usual steps, plus wherever Ctrl+wheel left it. */
  function zoomOptions() {
    var list = [['fit', 'Fit']].concat(ZOOMS.map(function (z) { return [String(z), Math.round(z * 100) + '%']; }));
    if (zoom !== 'fit' && ZOOMS.indexOf(zoom) === -1) list.push([String(zoom), Math.round(zoom * 100) + '%']);
    return list;
  }

  function currentScale() {
    var dev = DEVICES[bp];
    var box = elCanvas.getBoundingClientRect();
    return zoom === 'fit' ? Math.min(1, Math.max(200, box.width - 64) / dev.width) : zoom;
  }

  /**
   * Zoom so the point under the pointer stays under the pointer - how every
   * design tool behaves. `x` is in the editor's own coordinates.
   */
  function zoomAt(next, x) {
    var before = currentScale();
    next = Math.max(0.1, Math.min(3, Math.round(next * 100) / 100));
    if (next === before) return;
    var devRect = device.getBoundingClientRect();
    var anchor = x === undefined ? devRect.left + devRect.width / 2 : x;
    var inside = (anchor - devRect.left) / before; // device px under the pointer
    zoom = next;
    layoutCanvas();
    renderTop();
    var after = device.getBoundingClientRect();
    stage.scrollLeft += (after.left + inside * next) - anchor;
  }

  function layoutCanvas() {
    var dev = DEVICES[bp];
    var box = elCanvas.getBoundingClientRect();
    var availW = Math.max(200, box.width - 64);
    var availH = Math.max(200, box.height - 56);
    var scale = zoom === 'fit' ? Math.min(1, availW / dev.width) : zoom;
    frame.style.width = dev.width + 'px';
    frame.style.height = Math.round(availH / scale) + 'px';
    frame.style.transform = 'scale(' + scale + ')';
    device.style.width = Math.round(dev.width * scale) + 'px';
    device.style.height = Math.round(availH) + 'px';
    frameLabel.textContent = dev.label + '  ' + dev.width + (scale !== 1 ? '  ·  ' + Math.round(scale * 100) + '%' : '');
  }

  window.addEventListener('resize', layoutCanvas);
  stage.addEventListener('wheel', function (e) {
    if (!(e.ctrlKey || e.metaKey)) return;
    e.preventDefault();
    zoomAt(currentScale() * Math.pow(1.0018, -e.deltaY), e.clientX);
  }, { passive: false });

  function setBreakpoint(next) {
    bp = next;
    layoutCanvas();
    renderTop();
    checkOverflowSoon();
    setTimeout(function () { refreshSelection(true); renderLayers(); }, 60);
  }

  function setMode(next) {
    mode = next;
    if (bridge) bridge.setMode(mode);
    shell.classList.toggle('is-preview', mode === 'preview');
    renderTop();
  }

  /**
   * Every INU page is its own document at its own address, so changing page
   * reloads the canvas. The runtime says `ready` again once the new page is
   * up, and that is where the layers are read.
   */
  function pageUrl(r) {
    return cfg.base.replace(/\/$/, '') + (r === '/' ? '/' : r);
  }

  function go(to) {
    if (!to) return;
    if (!routeAllowed(to)) { toast('This page is not allocated to your Site Editor.', true); return; }
    if (to === route && bridge) return;
    loading.hidden = false;
    bridge = null;
    // replace(), not a new src: moving between pages in the canvas must not
    // stack up entries that the browser's Back button would walk through.
    try { frame.contentWindow.location.replace(pageUrl(to) + '?ldrn_ws_studio=1'); }
    catch (e) { frame.src = pageUrl(to) + '?ldrn_ws_studio=1'; }
  }

  function labelForRoute(r) {
    var hit = (cfg.pages || []).filter(function (p) { return p.route === r; })[0];
    if (hit) return hit.label;
    if (discovered[r]) return discovered[r];
    return r;
  }

  window.__ldrnStudioHost = function (type, payload) {
    if (payload && payload.route && !routeAllowed(payload.route)) { go(cfg.start); return; }
    if (payload && payload.key && !keyAllowed(payload.key)) return;
    if (type === 'ready') {
      bridge = frame.contentWindow.__ldrnStudioBridge;
      connectPaletteDrop();
      loading.hidden = true;
      bridge.setDoc(doc);
      bridge.setMode(mode);
      var next = payload.route || '/';
      var moved = next !== route;
      route = next;
      if (moved) select(null);
      // The same page loaded again: carry the selection into the new document.
      else if (selected) select(bridge.exists(selected) ? selected : null);
      afterRoute();
    } else if (type === 'route') {
      route = payload.route;
      select(null);
      afterRoute();
    } else if (type === 'select') {
      select(payload.key, true);
    } else if (type === 'hover') {
      markHover(payload.key);
    } else if (type === 'text') {
      setContent(payload.key, 'text', payload.text);
    } else if (type === 'label') {
      setContent(payload.key, 'label', payload.text);
    } else if (type === 'rich') {
      // Typed on the canvas into words with a styled one among them.
      snapshot(payload.key + '|text');
      var rn = nodeFor(payload.key, true);
      rn.rich = payload.rich;
      delete rn.text;
      prune(payload.key);
      changed();
    } else if (type === 'selectMany') {
      selectMany(payload.keys, payload.primary, true);
    } else if (type === 'geometry') {
      setStyles(payload.changes, payload.group);
    } else if (type === 'key') {
      shortcut(payload);
    } else if (type === 'zoom') {
      // Ctrl+wheel inside the canvas - the page would otherwise zoom itself.
      var fr = frame.getBoundingClientRect();
      zoomAt(currentScale() * Math.pow(1.0018, -payload.dy), fr.left + payload.x * currentScale());
    } else if (type === 'pan') {
      stage.scrollLeft += payload.dx;
    } else if (type === 'navigate') {
      // A link followed in Preview: stay in the editor, on the new page.
      go(payload.route);
    }
  };

  function afterRoute() {
    renderTop();
    // Edits made on this page before fingerprints existed get theirs now,
    // once the page has drawn - and are saved with them.
    setTimeout(function () { if (backfillFingerprints()) { localDirty = true; scheduleSave(); } }, 1600);
    // Read the layers once the page has had a moment, and again once images
    // and the reveal animations have settled.
    setTimeout(function () { collectRoutes(); renderLayers(); renderLeftIfPages(); }, 250);
    setTimeout(function () { collectRoutes(); renderLayers(); renderLeftIfPages(); }, 1500);
  }

  function collectRoutes() {
    if (!bridge) return;
    var found = bridge.routes();
    Object.keys(found).forEach(function (r) { if(routeAllowed(r)) discovered[r] = found[r]; });
  }

  frame.src = pageUrl(cfg.start || '/') + '?ldrn_ws_studio=1';

  /* ------------------------------------------------------------ left */

  function renderLeftIfPages() { if (leftTab === 'pages') renderLeft(); }

  function renderLeft() {
    elLeft.innerHTML = '';
    var tabs = h('div', { class: 'wws-tabs' }, [['pages', 'Pages'], ['layers', 'Layers'], ['assets', 'Assets'], ['media', 'Media']].map(function (t) {
      return h('button', { type: 'button', class: leftTab === t[0] ? 'is-on' : '', onclick: function () { leftTab = t[0]; renderLeft(); } }, [t[1]]);
    }));
    elLeft.appendChild(tabs);
    var body = h('div', { class: 'wws-left-body' });
    elLeft.appendChild(body);
    if (leftTab === 'pages') renderPages(body);
    else if (leftTab === 'layers') { layersBody = body; renderLayers(); }
    else if (leftTab === 'assets') renderInsert(body);
    else renderMedia(body);
  }

  function customPages() {
    return (cfg.pages || []).filter(function (p) { return p.custom; }).map(function (p) { return { route: p.route, label: p.label }; });
  }

  function slugify(text) {
    return String(text || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  }

  function renderPages(body) {
    if (!cfg.scoped) {
    body.appendChild(h('div', { class: 'wws-panel-actions' }, [
      h('button', { type: 'button', class: 'wws-btn wws-btn-primary', onclick: function () { pageForm = { mode: 'new' }; renderLeft(); } }, [icon('plus'), ' New page']),
    ]));
    if (pageForm && pageForm.mode === 'new') body.appendChild(pageFormEl(null));

    }
    var known = {};
    (cfg.pages || []).forEach(function (p) { known[p.route] = true; });
    var list = h('div', { class: 'wws-pages' });
    (cfg.pages || []).forEach(function (p) {
      if (pageForm && pageForm.mode === 'edit' && pageForm.id === p.id) { list.appendChild(pageFormEl(p)); return; }
      list.appendChild(pageRow(p.route, p.label, 0, p));
      // Project pages live under the portfolio, the way Framer nests a CMS
      // collection's pages under their parent.
      Object.keys(discovered).filter(function (r) {
        return !known[r] && p.route !== '/' && r.indexOf(p.route + '/') === 0;
      }).sort().forEach(function (r) { list.appendChild(pageRow(r, discovered[r], 1, null)); });
    });
    body.appendChild(list);
    if ((cfg.contentLinks || []).length) body.appendChild(h('div', {class:'wws-group-head',text:'Manage content'}));
    (cfg.contentLinks||[]).forEach(function(item){body.appendChild(h('a',{class:'wws-btn wws-btn-block',href:item.url,target:'_blank',rel:'noopener',text:item.label}));});
    body.appendChild(h('p', { class: 'wws-hint', text: cfg.scoped ? 'Only your allocated pages appear here. Shared navigation and whole-site history are managed by the Administrator.' : 'A dot means the page is in the site menu. New pages start out of the menu - add them once they are published.' }));
  }

  function pageRow(r, label, depth, page) {
    var tools = [];
    if (page && page.editUrl) tools.push(h('a', {href:page.editUrl, target:'_blank', rel:'noopener', title:'Edit WordPress content', onclick:function(e){e.stopPropagation();}}, [icon('pen')]));
    if (page && !cfg.scoped) {
      tools.push(h('button',{type:'button',title:page.visible?'Hide page on publication':'Show page on publication',onclick:function(e){e.stopPropagation();updatePage(page,{visible:page.visible?'0':'1'});}},[icon(page.visible?'eye':'eyeOff')]));
      tools.push(h('button', {
        type: 'button', title: page.menu ? 'Take out of the menu' : 'Put in the menu', class: page.menu ? 'is-on' : '',
        onclick: function (e) { e.stopPropagation(); updatePage(page, { menu: page.menu ? '0' : '1' }); },
      }, [icon('menu')]));
      tools.push(h('button', { type: 'button', title: 'Rename', onclick: function (e) { e.stopPropagation(); pageForm = { mode: 'edit', id: page.id }; renderLeft(); } }, [icon('pen')]));
      tools.push(h('button', { type: 'button', title: page.custom ? 'Delete page' : 'Archive page', onclick: function (e) {
        e.stopPropagation();
        if(page.custom)deletePage(page);
        else if(window.confirm('Archive "'+page.label+'" from the published site? Its original content is kept. The eye control can bring it back.'))updatePage(page,{visible:'0',menu:'0'});
      } }, [icon('trash')]));
    }
    return h('div', {
      class: 'wws-page-row' + (route === r ? ' is-on' : '') + (page && !page.visible ? ' is-off' : ''), title:label+' · '+r, style: 'padding-left:' + (10 + depth * 16) + 'px',
      onclick: function () { go(r); },
    }, [
      icon('page'),
      h('span', { class: 'n', text: label }),
      page && page.menu ? h('span', { class: 'wws-menu-dot', title: 'In the menu' }) : null,
      page && !page.visible ? h('span', { class: 'wws-badge', text: 'Hidden' }) : null,
      h('span', { class: 'r', text: r }),
      tools.length ? h('span', { class: 'wws-row-tools' }, tools) : null,
    ]);
  }

  function pageFormEl(page) {
    var editing = !!page;
    var name = h('input', { type: 'text', class: 'wws-in', placeholder: 'Page name, e.g. Journal', value: editing ? page.label : '' });
    var slug = h('input', { type: 'text', class: 'wws-in', placeholder: 'address', value: editing ? page.route.replace(/^\//, '') : '' });
    if(editing && !page.custom) {slug.readOnly=true;slug.title='This original page keeps its address; its display name is editable.';}
    var slugTouched = editing;
    name.addEventListener('input', function () { if (!slugTouched) slug.value = slugify(name.value); });
    slug.addEventListener('input', function () { slugTouched = true; });
    var start = h('select', { class: 'wws-in' }, [
      ['header', 'Start with a page header'],
      ['story', 'Header, text and a call to action'],
      ['blank', 'Start blank'],
    ].map(function (o) { return h('option', { value: o[0], text: o[1] }); }));
    var menu = h('input', { type: 'checkbox' });
    var kids = [
      h('div', { class: 'wws-form-title', text: editing ? 'Rename page' : 'New page' }),
      name,
      h('div', { class: 'wws-slug' }, [h('span', { text: '/' }), slug]),
    ];
    if (!editing) {
      kids.push(start);
      kids.push(h('label', { class: 'wws-check' }, [menu, h('span', { text: 'Put it in the menu now' })]));
    }
    kids.push(h('div', { class: 'wws-btns' }, [
      h('button', {
        type: 'button', class: 'wws-btn wws-btn-primary', onclick: function () {
          if (!name.value.trim()) { toast('Give the page a name.', true); return; }
          if (editing) updatePage(page, { label: name.value.trim(), slug: slugify(slug.value) });
          else createPage(name.value.trim(), slugify(slug.value || name.value), start.value, menu.checked);
        },
      }, [editing ? 'Save' : 'Create page']),
      h('button', { type: 'button', class: 'wws-btn', onclick: function () { pageForm = null; renderLeft(); } }, ['Cancel']),
    ]));
    setTimeout(function () { name.focus(); }, 0);
    return h('div', { class: 'wws-form' }, kids);
  }

  function pagesChanged(pages) {
    cfg.pages = pages;
    pageForm = null;
    if (bridge) { bridge.setPages(customPages()); if(bridge.setDirectory) bridge.setDirectory(pages); }
    renderLeft();
    renderTop();
  }

  function createPage(label, slug, start, inMenu) {
    post('ldrn_ws_studio_page_add', { label: label, slug: slug, menu: inMenu ? '1' : '', rev:rev }).then(function (data) {
      rev=data.status.rev; pagesChanged(data.pages);
      var at = 'm:' + data.route;
      var starters = start === 'blank' ? [] : start === 'story' ? ['page-intro', 'article-intro', 'closing'] : ['page-intro'];
      if (starters.length && lib.instantiate) {
        snapshot();
        lastGroup = 'page';
        starters.forEach(function (id) {
          var t = lib.templates.filter(function (x) { return x.id === id; })[0];
          if (!t) return;
          var node = t.build();
          // The header of a new page names the page.
          if (id === 'page-intro') retitle(node, label);
          var inst = lib.instantiate(node);
          Object.keys(inst.nodes).forEach(function (k) { doc.nodes[k] = inst.nodes[k]; });
          addBlock(at, 'append', inst.tree);
        });
        changed(true);
        lastGroup = null;
      }
      go(data.route);
      toast('Page created at ' + data.route + '. It goes live when you Publish.');
    }).catch(function (e) { toast(e.message, true); });
  }

  /** Put the page's own name into a page-header template's heading and label. */
  function retitle(node, label) {
    var n = 0;
    (function walk(x) {
      if ((x.tag === 'h1' || (x.tag === 'h2' && n < 3)) && typeof x.text === 'string') x.text = label;
      if (x.tag === 'p' && typeof x.text === 'string' && /^0 \/ /.test(x.text)) x.text = label;
      (x.kids || []).forEach(walk);
      n++;
    })(node);
  }

  function updatePage(page, fields) {
    var data = { id: page.id, rev:rev };
    Object.keys(fields).forEach(function (k) { data[k] = fields[k]; });
    post('ldrn_ws_studio_page_update', data).then(function (res) {
      rev=res.status.rev;
      if (res.from !== res.route) {
        moveRoute(res.from, res.route);
        if (route === res.from) go(res.route);
      }
      pagesChanged(res.pages);
      if (fields.menu !== undefined) toast('Menu change saved in the draft. Publish when ready.');
    }).catch(function (e) { toast(e.message, true); });
  }

  function deletePage(page) {
    if (!window.confirm('Delete the page "' + page.label + '" and everything on it?')) return;
    post('ldrn_ws_studio_page_delete', { id: page.id, rev:rev }).then(function (res) {
      rev=res.status.rev;
      snapshot();
      clearRoute(page.route);
      changed(true);
      if (route === page.route) go('/');
      pagesChanged(res.pages);
      toast('Page deleted. Undo brings its content back; Site Settings can restore the page itself.');
    }).catch(function (e) { toast(e.message, true); });
  }

  function underRoute(key, r) {
    var a = 'm:' + r;
    return key === a || key.indexOf(a + '>') === 0;
  }

  /** A page moved address: everything keyed to the old one follows it. */
  function moveRoute(from, to) {
    snapshot();
    var nodes = {};
    Object.keys(doc.nodes).forEach(function (k) {
      nodes[underRoute(k, from) ? 'm:' + to + k.slice(('m:' + from).length) : k] = doc.nodes[k];
    });
    doc.nodes = nodes;
    Object.keys(doc.blocks).forEach(function (id) {
      var b = doc.blocks[id];
      if (underRoute(b.at, from)) b.at = 'm:' + to + b.at.slice(('m:' + from).length);
    });
    changed(true);
  }

  function clearRoute(r) {
    var guard = 0;
    var hit;
    while ((hit = Object.keys(doc.blocks).filter(function (id) { return underRoute(doc.blocks[id].at, r); })[0]) && guard++ < 1000) {
      removeInserted(hit, true);
    }
    Object.keys(doc.nodes).forEach(function (k) { if (underRoute(k, r)) delete doc.nodes[k]; });
  }

  /* ------------------------------------------------------------ layers */

  var layersBody = null;
  var layersTimer = null;

  function renderLayersSoon() {
    clearTimeout(layersTimer);
    layersTimer = setTimeout(renderLayers, 120);
  }

  function renderLayers() {
    if (leftTab !== 'layers' || !layersBody) return;
    var scroll = layersBody.scrollTop;
    layersBody.innerHTML = '';
    if (!bridge) { layersBody.appendChild(h('p', { class: 'wws-hint', text: 'Waiting for the page…' })); return; }
    var list = h('div', { class: 'wws-layers', role: 'tree' });
    bridge.tree(null).forEach(function (item) { addLayer(list, item, 0); });
    layersBody.appendChild(list);
    layersBody.scrollTop = scroll;
    var on = list.querySelector('.is-selected');
    if (on && layersScrollToSelected) {
      layersScrollToSelected = false;
      on.scrollIntoView({ block: 'nearest' });
    }
  }

  var layersScrollToSelected = false;

  function addLayer(list, item, depth) {
    var isOpen = !!expanded[item.key];
    var row = h('div', {
      class: 'wws-layer' + (item.key === selected || multi.indexOf(item.key) !== -1 ? ' is-selected' : '') + (item.hidden ? ' is-hidden' : '') + (item.edited && !item.inserted ? ' is-edited' : '') + (item.inserted ? ' is-inserted' : ''),
      'data-key': item.key,
      style: 'padding-left:' + (6 + depth * 14) + 'px',
      onclick: function (e) {
        if (e.shiftKey && selected) {
          var keys = multi.slice();
          var at = keys.indexOf(item.key);
          if (at === -1) keys.push(item.key); else keys.splice(at, 1);
          selectMany(keys, at === -1 ? item.key : null);
          return;
        }
        select(item.key);
        if (bridge) bridge.scrollTo(item.key);
      },
      onmouseenter: function () { if (bridge) bridge.hover(item.key); },
      onmouseleave: function () { if (bridge) bridge.hover(null); },
    }, [
      item.kids ? h('button', {
        type: 'button', class: 'wws-caret' + (isOpen ? ' is-open' : ''),
        onclick: function (e) { e.stopPropagation(); expanded[item.key] = !isOpen; renderLayers(); },
      }, [icon('caret')]) : h('span', { class: 'wws-caret-gap' }),
      h('span', { class: 'wws-layer-icon t-' + item.type }, [icon(item.type === 'section' && depth === 0 && /^m:/.test(item.key) && item.key.indexOf('>') === -1 ? 'page' : item.type)]),
      h('span', { class: 'wws-layer-name', text: item.name }),
      h('span', { class: 'wws-layer-tools' }, [
        h('button', { type: 'button', title: 'Move up', onclick: function (e) { e.stopPropagation(); move(item.key, -1); } }, [icon('up')]),
        h('button', { type: 'button', title: 'Move down', onclick: function (e) { e.stopPropagation(); move(item.key, 1); } }, [icon('down')]),
        h('button', { type: 'button', title: item.hidden ? 'Show' : 'Hide', onclick: function (e) { e.stopPropagation(); toggleHidden(item.key, item.hidden); } }, [icon(item.hidden ? 'eyeOff' : 'eye')]),
        item.inserted ? h('button', { type: 'button', title: 'Delete', onclick: function (e) { e.stopPropagation(); removeInserted(item.key.slice(2)); } }, [icon('trash')]) : null,
      ]),
    ]);
    list.appendChild(row);
    if (isOpen && item.kids) {
      bridge.tree(item.key).forEach(function (kid) { addLayer(list, kid, depth + 1); });
    }
  }

  function markHover(key) {
    if (!layersBody) return;
    Array.prototype.forEach.call(layersBody.querySelectorAll('.wws-layer.is-hover'), function (r) { r.classList.remove('is-hover'); });
    if (!key) return;
    var row = layersBody.querySelector('.wws-layer[data-key="' + (window.CSS && CSS.escape ? CSS.escape(key) : key) + '"]');
    if (row) row.classList.add('is-hover');
  }

  function toggleHidden(key, isHidden) {
    if (isHidden) {
      // Shown again: drop our own "none" if there is one; otherwise the site
      // itself hides it here, and it needs an explicit display to override.
      if (own(key, 'display') === 'none') setStyle(key, 'display', '');
      else setStyle(key, 'display', 'block');
    } else {
      setStyle(key, 'display', 'none');
    }
    toast(isHidden ? 'Shown on ' + DEVICES[bp].label : 'Hidden on ' + DEVICES[bp].label + (bp === 'd' ? ' (and smaller, unless shown there)' : ''));
  }

  /**
   * Reorder among siblings. The DOM belongs to React, so nothing is moved:
   * the parent becomes a column and each sibling gets a CSS `order`, which
   * is exactly what the layout engine then honours - and what survives every
   * re-render.
   */
  function move(key, delta) {
    if (!bridge) return;
    var info = bridge.info(key);
    if (!info || !info.parentKey) return;
    var list = info.siblings.slice();
    var from = list.indexOf(key);
    var to = from + delta;
    if (from < 0 || to < 0 || to >= list.length || list.indexOf(null) !== -1) return;
    list.splice(to, 0, list.splice(from, 1)[0]);
    snapshot();
    lastGroup = 'move';
    var parent = nodeFor(info.parentKey, true);
    parent.s = parent.s || {};
    parent.s[bp] = parent.s[bp] || {};
    if (!/flex|grid/.test(info.parentDisplay)) {
      parent.s[bp].display = 'flex';
      parent.s[bp]['flex-direction'] = 'column';
    }
    list.forEach(function (k, i) {
      var n = nodeFor(k, true);
      n.s = n.s || {};
      n.s[bp] = n.s[bp] || {};
      n.s[bp].order = String(i + 1);
    });
    changed(true);
    lastGroup = null;
  }

  /* ------------------------------------------------------------ assets */

  /** Where an insert lands, given what is selected - said in the panel before clicking. */
  function insertTarget(kind) {
    var pageKey = 'm:' + route;
    if (!sel || !selected) return { key: pageKey, pos: 'append', label: 'at the end of this page' };
    var inMain = sel.path.length && /^m:/.test(sel.path[0].key) && sel.path[0].key.indexOf('>') === -1;
    if (kind === 'section') {
      if (!inMain) return { key: pageKey, pos: 'append', label: 'at the end of this page' };
      if (sel.path.length < 2) return { key: pageKey, pos: 'append', label: 'at the end of this page' };
      return { key: sel.path[1].key, pos: 'after', label: 'after “' + sel.path[1].name + '”' };
    }
    var leaf = sel.textual || sel.image || sel.embed !== null || /^(link|button|input|graphic)$/.test(sel.type);
    if (!leaf) return { key: selected, pos: 'append', label: 'inside “' + sel.name + '”' };
    if (selected === pageKey) return { key: pageKey, pos: 'append', label: 'at the end of this page' };
    return { key: selected, pos: 'after', label: 'after “' + sel.name + '”' };
  }

  var insertHintEl = null;
  function updateInsertHint() {
    if (!insertHintEl) return;
    insertHintEl.textContent = 'Adds ' + insertTarget(assetsTab === 'templates' ? 'section' : 'element').label;
  }

  function renderInsert(body) {
    body.appendChild(h('div', { class: 'wws-subtabs' }, [['templates', 'Templates'], ['elements', 'Elements']].map(function (t) {
      return h('button', { type: 'button', class: assetsTab === t[0] ? 'is-on' : '', onclick: function () { assetsTab = t[0]; renderLeft(); } }, [t[1]]);
    })));
    insertHintEl = h('p', { class: 'wws-insert-hint' });
    body.appendChild(insertHintEl);
    updateInsertHint();

    if (!lib.instantiate) { body.appendChild(h('p', { class: 'wws-hint', text: 'The library did not load.' })); return; }

    if (assetsTab === 'templates') {
      var cats = [];
      lib.templates.forEach(function (t) { if (cats.indexOf(t.cat) === -1) cats.push(t.cat); });
      cats.forEach(function (cat) {
        body.appendChild(h('div', { class: 'wws-group-head', text: cat }));
        lib.templates.filter(function (t) { return t.cat === cat; }).forEach(function (t) {
          body.appendChild(h('button', { type: 'button', class: 'wws-tpl', draggable:'true', ondragstart:function(event){beginPaletteDrag(event,t,'section');}, ondragend:function(){paletteDrag=null;}, title: 'Drag or add ' + t.name, onclick: function () { insertLibrary(t.build(), 'section'); } }, [
            preview(t.build()),
            h('span', { class: 'wws-tpl-name', text: t.name }),
          ]));
        });
      });
      body.appendChild(h('p', { class: 'wws-hint', text: lib.templates.length + ' templates. Everything they add is edited like the rest of the site.' }));
    } else {
      renderElements(body);
    }
  }

  /** The inline "paste a link" step for Video and Map, instead of a dialog. */
  var embedForm = (function () {
    var current = null;
    var el = h('div', { class: 'wws-form wws-embed-form' });
    function render() {
      el.innerHTML = '';
      el.hidden = !current;
      if (!current) return;
      var isMap = current.needsUrl === 'map';
      var input = h('input', {
        type: 'text', class: 'wws-in',
        placeholder: isMap ? 'An address, or a Google Maps embed link' : 'A YouTube link',
        value: isMap ? '64 Pears Road, Hounslow, TW3 1SR' : '',
      });
      el.appendChild(h('div', { class: 'wws-form-title', text: isMap ? 'Add a map' : 'Add a video' }));
      el.appendChild(input);
      el.appendChild(h('div', { class: 'wws-btns' }, [
        h('button', { type: 'button', class: 'wws-btn wws-btn-primary', onclick: function () {
          var url = embedUrl(input.value, current.needsUrl);
          if (!url) { toast(isMap ? 'That did not look like an address or a map link.' : 'Paste a YouTube link, like youtube.com/watch?v=…', true); return; }
          var e = current;
          current = null;
          render();
          insertLibrary(e.build(url), 'element');
        } }, ['Add']),
        h('button', { type: 'button', class: 'wws-btn', onclick: function () { current = null; render(); } }, ['Cancel']),
      ]));
      setTimeout(function () { input.focus(); input.select(); }, 0);
    }
    return { el: el, render: render, open: function (e) { current = e; render(); } };
  })();

  /** Turn whatever someone pastes into an embed address the site allows. */
  function embedUrl(raw, kind) {
    raw = String(raw || '').trim();
    var src = raw.match(/src="([^"]+)"/);
    if (src) raw = src[1].replace(/&amp;/g, '&');
    if (kind === 'video') {
      var id = raw.match(/(?:youtu\.be\/|v=|embed\/|shorts\/)([A-Za-z0-9_-]{6,20})/);
      return id ? 'https://www.youtube-nocookie.com/embed/' + id[1] : '';
    }
    if (/^https:\/\/www\.google\.com\/maps\/embed\?/.test(raw)) return raw;
    if (/^https?:\/\//.test(raw)) return '';
    return raw ? 'https://maps.google.com/maps?q=' + encodeURIComponent(raw).replace(/%20/g, '+') + '&output=embed' : '';
  }

  var elementQuery = '';
  /**
   * Every element of the site, drawn as it really looks. Drag one onto the
   * page to lay it on top where it is dropped; click to add it after the
   * selection instead.
   */
  function renderElements(body) {
    var search = h('input', { type: 'search', class: 'wws-in wws-elx-search', placeholder: 'Search elements', value: elementQuery, 'aria-label': 'Search elements' });
    body.appendChild(h('div', { class: 'wws-elx-tools' }, [search, h('p', { class: 'wws-hint', text: 'Drag onto the page to place it on top, or click to add it after the selection.' })]));
    var cats = [], groups = [];
    lib.elements.forEach(function (e) { if (cats.indexOf(e.cat) === -1) cats.push(e.cat); });
    cats.forEach(function (cat) {
      var head = h('div', { class: 'wws-group-head', text: cat });
      var grid = h('div', { class: 'wws-elx' }, lib.elements.filter(function (e) { return e.cat === cat; }).map(elementTile));
      body.appendChild(head); body.appendChild(grid);
      groups.push({ head: head, grid: grid });
    });
    body.appendChild(embedForm.el);
    embedForm.render();
    function filter() {
      elementQuery = search.value;
      var q = elementQuery.trim().toLowerCase();
      groups.forEach(function (g) {
        var shown = 0;
        Array.prototype.forEach.call(g.grid.children, function (tile) {
          var hit = !q || tile.getAttribute('data-search').indexOf(q) !== -1;
          tile.hidden = !hit; if (hit) shown++;
        });
        g.head.hidden = g.grid.hidden = !shown;
      });
    }
    search.addEventListener('input', filter);
    filter();
    requestAnimationFrame(function () { fitTiles(body); });
    setTimeout(function () { fitTiles(body); }, 600);
  }

  function elementTile(e) {
    var drawn = !e.needsUrl && !e.needsFile && !/^(spacer|divider|section)$/.test(e.id);
    var stage = drawn ? h('div', { class: 'wws-elx-stage' }, [drawNode(e.build())]) : h('span', { class: 'wws-el-icon', text: e.icon });
    var view = h('div', { class: 'wws-elx-view' + (drawn ? '' : ' is-icon'), style: e.back && e.back !== 'transparent' ? 'background:' + e.back : '' }, [stage]);
    return h('button', {
      type: 'button', class: 'wws-elx-tile' + (e.wide ? ' is-wide' : '') + (e.tall ? ' is-tall' : ''), draggable: 'true', title: e.name + ' - drag onto the page, or click to add',
      'data-search': (e.name + ' ' + e.cat).toLowerCase(),
      ondragstart: function (event) { beginPaletteDrag(event, e, 'element'); }, ondragend: function () { paletteDrag = null; },
      onclick: function () {
        if (e.needsUrl) { embedForm.open(e); return; }
        if (e.needsFile) { uploadForm.open(null); return; }
        insertLibrary(e.build(), 'element');
      },
    }, [view, h('span', { class: 'wws-elx-name', text: e.name })]);
  }

  /** Scale each drawn element down (never up) to sit centred in its tile. */
  function fitTiles(root) {
    Array.prototype.forEach.call(root.querySelectorAll('.wws-elx-view'), function (view) {
      var stage = view.querySelector('.wws-elx-stage');
      if (!stage || !view.clientWidth) return;
      stage.style.transform = 'none';
      var w = stage.scrollWidth, ht = stage.scrollHeight;
      if (!w || !ht) return;
      var s = Math.min(1, (view.clientWidth - 16) / w, (view.clientHeight - 14) / ht);
      stage.style.transform = 'translate(-50%,-50%) scale(' + s + ')';
    });
  }

  /** One node of the editor's tree as a real element, SVG icons included. */
  function drawNode(x) {
    var svg = /^(svg|g|path|circle|ellipse|rect|line|polyline|polygon)$/.test(x.tag);
    var el = svg ? document.createElementNS('http://www.w3.org/2000/svg', x.tag) : document.createElement(x.tag === 'iframe' ? 'div' : x.tag);
    Object.keys(x.attrs || {}).forEach(function (k) { try { el.setAttribute(k, x.attrs[k]); } catch (err) {} });
    var st = x.s && (x.s.d || x.s.t || x.s.p) ? x.s.d || {} : x.s || {};
    Object.keys(st).forEach(function (prop) {
      var v = st[prop];
      if (prop === 'font-family') v = lib.fonts[v] || '';
      try { el.style.setProperty(prop, v); } catch (err) {}
    });
    if (x.tag === 'img') { el.src = x.src || ''; el.alt = ''; el.loading = 'lazy'; el.draggable = false; }
    else if (typeof x.text === 'string') {
      x.text.split('\n').forEach(function (line, i) {
        if (i) el.appendChild(document.createElement('br'));
        el.appendChild(document.createTextNode(line));
      });
    }
    (x.kids || []).forEach(function (k) { el.appendChild(drawNode(k)); });
    return el;
  }

  /** A live miniature of a template, drawn in the editor with its Desktop styles. */
  function preview(node) {
    function draw(x) {
      var el = document.createElement(x.tag === 'iframe' ? 'div' : x.tag);
      var st = x.s && (x.s.d || x.s.t || x.s.p) ? x.s.d || {} : x.s || {};
      Object.keys(st).forEach(function (prop) {
        var v = st[prop];
        if (prop === 'font-family') v = lib.fonts[v] || '';
        try { el.style.setProperty(prop, v); } catch (e) {}
      });
      if (x.tag === 'img') { el.src = x.src || ''; el.alt = ''; el.loading = 'lazy'; }
      else if (typeof x.text === 'string') {
        x.text.split('\n').forEach(function (line, i) {
          if (i) el.appendChild(document.createElement('br'));
          el.appendChild(document.createTextNode(line));
        });
      }
      (x.kids || []).forEach(function (k) { el.appendChild(draw(k)); });
      return el;
    }
    var page = h('div', { class: 'wws-tpl-page' }, [draw(node)]);
    return h('div', { class: 'wws-tpl-thumb' }, [page]);
  }

  /* ---- the document side of inserting, deleting and duplicating ---- */

  function newId() {
    var id = '';
    while (id.length < 8) id += Math.random().toString(36).slice(2);
    return id.slice(0, 8);
  }

  /** Where an inserted node lives: which block, its parent and index in the tree. */
  function locate(id) {
    var found = null;
    Object.keys(doc.blocks).some(function (bid) {
      (function walk(node, parent, index) {
        if (found || !node) return;
        if (node.id === id) { found = { block: bid, node: node, parent: parent, index: index }; return; }
        (node.kids || []).forEach(function (k, i) { walk(k, node, i); });
      })(doc.blocks[bid].tree, null, -1);
      return !!found;
    });
    return found;
  }

  function followerOf(at, pos) {
    return Object.keys(doc.blocks).filter(function (id) { return doc.blocks[id].at === at && doc.blocks[id].pos === pos; })[0] || null;
  }

  /**
   * One block per anchor and position: a second insert after the same thing
   * slots in between, and the one that was there follows the new one. That
   * keeps every block's place on the page unambiguous.
   */
  function addBlock(at, pos, tree) {
    if (pos === 'append') {
      var head = followerOf(at, 'append');
      if (head) {
        var last = head;
        var next;
        while ((next = followerOf('n:' + last, 'after'))) last = next;
        at = 'n:' + last;
        pos = 'after';
      }
    }
    if (pos === 'after') {
      var existing = followerOf(at, 'after');
      if (existing) { doc.blocks[existing].at = 'n:' + tree.id; delete doc.blocks[existing].af; }
    }
    doc.blocks[tree.id] = { at: at, pos: pos, tree: tree };
    var blockPrint = bridge && bridge.fingerprint ? bridge.fingerprint(at) : '';
    if (blockPrint) doc.blocks[tree.id].af = blockPrint;
  }

  function insertLibrary(node, kind) {
    if (!bridge) return;
    var target = insertTarget(kind);
    var inst = lib.instantiate(node);
    snapshot();
    lastGroup = 'insert';
    Object.keys(inst.nodes).forEach(function (k) { doc.nodes[k] = inst.nodes[k]; });
    placeTree(inst.tree, target);
    changed(true);
    lastGroup = null;
    var key = 'n:' + inst.tree.id;
    setTimeout(function () { select(key); bridge.scrollTo(key); }, 80);
    toast('Added ' + target.label + '.');
  }

  /** Inside an inserted container it joins that tree; anywhere else it becomes a block. */
  function placeTree(tree, target) {
    var id = target.key.indexOf('n:') === 0 ? target.key.slice(2) : null;
    var loc = id ? locate(id) : null;
    if (loc && target.pos === 'append') {
      loc.node.kids = loc.node.kids || [];
      loc.node.kids.push(tree);
      delete loc.node.text;
    } else if (loc && target.pos === 'after' && loc.parent) {
      loc.parent.kids.splice(loc.index + 1, 0, tree);
    } else {
      addBlock(target.key, target.pos, tree);
    }
  }

  function removeInserted(id, quiet) {
    var loc = locate(id);
    if (!loc) return;
    if (!quiet) snapshot();
    var gone = [];
    (function walk(n) { gone.push(n.id); (n.kids || []).forEach(walk); })(loc.node);
    if (doc.blocks[id]) {
      var b = doc.blocks[id];
      // Whatever was chained after this block takes its place.
      var f = followerOf('n:' + id, 'after');
      if (f) { doc.blocks[f].at = b.at; doc.blocks[f].pos = b.pos; }
      delete doc.blocks[id];
    } else if (loc.parent) {
      loc.parent.kids.splice(loc.index, 1);
      if (!loc.parent.kids.length) delete loc.parent.kids;
    }
    gone.forEach(function (g) { delete doc.nodes['n:' + g]; });
    // Blocks that were placed inside what just went have nowhere to be.
    Object.keys(doc.blocks).forEach(function (bid) {
      var at = doc.blocks[bid] && doc.blocks[bid].at;
      if (at && at.indexOf('n:') === 0 && gone.indexOf(at.slice(2)) !== -1) removeInserted(bid, true);
    });
    if (!quiet) {
      if (selected && gone.indexOf(selected.slice(2)) !== -1) { selected = null; sel = null; if (bridge) bridge.select(null); }
      changed(true);
      toast('Deleted. Ctrl+Z brings it back.');
    }
  }

  function cloneTree(node, map) {
    var id = newId();
    map[node.id] = id;
    var out = {};
    Object.keys(node).forEach(function (k) { if (k !== 'kids') out[k] = node[k]; });
    out.id = id;
    if (node.kids) out.kids = node.kids.map(function (k) { return cloneTree(k, map); });
    return out;
  }

  function duplicate(key) {
    if (!bridge || !key) return;
    snapshot();
    lastGroup = 'dup';
    var copy;
    if (key.indexOf('n:') === 0 && key.indexOf('~label')===-1) {
      var loc = locate(key.slice(2));
      if (!loc) return;
      copy=bridge.snapshot(key);
      if(!copy)return;
      carryEdits(copy);
      if (doc.blocks[loc.node.id] || !loc.parent) addBlock(key, 'after', copy);
      else loc.parent.kids.splice(loc.index + 1, 0, copy);
    } else {
      if (/^m:[^>]*$/.test(key) || key === 't') { toast('The page itself cannot be duplicated - add a template instead.', true); return; }
      copy = bridge.snapshot(key);
      if (!copy) { toast('That could not be copied.', true); return; }
      carryEdits(copy);
      copy.name = 'Copy of ' + (sel && sel.key === key ? sel.name : 'section');
      addBlock(key, 'after', copy);
    }
    changed(true);
    lastGroup = null;
    var nk = 'n:' + copy.id;
    setTimeout(function () { select(nk); bridge.scrollTo(nk); }, 80);
    toast('Duplicated.');
  }

  /**
   * A copy of the site's own element starts with the styles edited on it and
   * on everything inside it - on every breakpoint - so it looks like what
   * was copied, not like the design before anyone touched it.
   */
  function carryEdits(node) {
    if (!node) return;
    if (node.from) {
      var was = doc.nodes[node.from];
      if (was) {doc.nodes['n:' + node.id]=JSON.parse(JSON.stringify(was));delete doc.nodes['n:' + node.id].f;}
      delete node.from;
    }
    (node.kids || []).forEach(carryEdits);
  }

  /** Change an inserted node's own tree field (an embed address). */
  function setTreeField(key, field, value) {
    var loc = key.indexOf('n:') === 0 ? locate(key.slice(2)) : null;
    if (!loc || loc.node[field] === value) return;
    snapshot();
    loc.node[field] = value;
    changed(true);
  }

  /* ---- Media ---- */

  function renderMedia(body) {
    var hint = selIsImage() ? 'Click a picture to put it in the selected image.' : 'Select an image on the canvas, then click a picture here to swap it in.';
    body.appendChild(h('p', { class: 'wws-hint', text: hint }));
    var drop = h('button', { type: 'button', class: 'wws-upload-drop', onclick: function () { uploadForm.open(null); } }, [
      icon('upload'), h('strong', { text: 'Upload from your computer' }), h('span', { text: 'or drop photos here, or straight onto the page' }),
    ]);
    drop.addEventListener('dragover', function (ev) { if (hasFiles(ev)) { ev.preventDefault(); drop.classList.add('is-over'); } });
    drop.addEventListener('dragleave', function () { drop.classList.remove('is-over'); });
    drop.addEventListener('drop', function (ev) {
      if (!hasFiles(ev)) return;
      ev.preventDefault(); drop.classList.remove('is-over');
      uploadImages(ev.dataTransfer.files).then(function (list) { if (list[0] && selIsImage()) useImage(list[0].url, list[0].alt); });
    });
    body.appendChild(drop);
    body.appendChild(h('button', { type: 'button', class: 'wws-btn wws-btn-block', onclick: openLibrary }, [icon('upload'), ' Choose from the Media Library']));

    var libHead = h('div', { class: 'wws-group-head', text: 'Media library' });
    var libGrid = h('div', { class: 'wws-assets' }, [h('p', { class: 'wws-hint', text: 'Loading…' })]);
    body.appendChild(libHead);
    body.appendChild(libGrid);

    function readLibrary(again) {
      return fetch(cfg.restUrl + '?media_type=image&per_page=60&_fields=id,source_url,alt_text,title,media_details', {
        credentials: 'same-origin', headers: { 'X-WP-Nonce': cfg.restNonce },
      }).then(function (r) {
        if ((r.status === 401 || r.status === 403) && !again) {
          return refreshNonce().then(function (ok) { if (!ok) throw new Error('logged out'); return readLibrary(true); });
        }
        if (!r.ok) throw new Error(String(r.status));
        return r.json();
      });
    }
    readLibrary(false).then(function (items) {
      libGrid.innerHTML = '';
      if (!Array.isArray(items) || !items.length) { libGrid.appendChild(h('p', { class: 'wws-hint', text: 'Nothing uploaded yet.' })); return; }
      items.forEach(function (m) {
        var sizes = m.media_details && m.media_details.sizes;
        var thumb = sizes && (sizes.medium || sizes.thumbnail) ? (sizes.medium || sizes.thumbnail).source_url : m.source_url;
        libGrid.appendChild(assetTile(m.source_url, thumb, m.alt_text || (m.title && m.title.rendered) || ''));
      });
    }).catch(function () { libGrid.innerHTML = ''; libGrid.appendChild(h('p', { class: 'wws-hint', text: 'The media library could not be read.' })); });

    var groups = {};
    (cfg.images || []).forEach(function (img) { (groups[img.group] = groups[img.group] || []).push(img); });
    Object.keys(groups).forEach(function (g) {
      body.appendChild(h('div', { class: 'wws-group-head', text: 'Site pictures · /' + g }));
      body.appendChild(h('div', { class: 'wws-assets' }, groups[g].map(function (img) { return assetTile(img.url, img.url, ''); })));
    });
  }

  function assetTile(url, thumb, alt) {
    return h('button', {
      type: 'button', class: 'wws-asset', title: url.split('/').pop(),
      draggable:'true', ondragstart:function(event){beginPaletteDrag(event,{url:url,alt:alt,name:alt||'Nursery image'},'image');}, ondragend:function(){paletteDrag=null;},
      onclick: function () { useImage(url, alt); },
    }, [h('img', { src: thumb, alt: '', loading: 'lazy' })]);
  }

  function selIsImage() { return !!(sel && sel.image); }

  function useImage(url, alt) {
    if (!selIsImage()) { toast('Select an image on the canvas first.', true); return; }
    snapshot();
    lastGroup = 'image';
    var n = nodeFor(selected, true);
    n.src = url;
    if (alt && !n.alt) n.alt = alt;
    changed(true);
    lastGroup = null;
  }

  function openLibrary() {
    if (!window.wp || !window.wp.media) { toast('The media library did not load.', true); return; }
    if (!media) {
      media = window.wp.media({ title: 'Choose a picture', library: { type: 'image' }, button: { text: 'Use this picture' }, multiple: false });
      media.on('select', function () {
        var chosen = media.state().get('selection').first().toJSON();
        if (selIsImage()) useImage(chosen.url, chosen.alt);
        if (leftTab === 'media') renderLeft();
      });
    }
    media.open();
  }

  /* ------------------------------------------------------------ selection */

  function select(key, fromCanvas) {
    if (!keyAllowed(key)) key = null;
    multi = key ? [key] : [];
    selected = key || null;
    if (bridge && !fromCanvas) bridge.select(selected);
    sel = selected && bridge ? bridge.info(selected) : null;
    if (sel) {
      // Open the tree down to the selected layer, the way Framer does.
      sel.path.forEach(function (p) { if (p.key !== selected) expanded[p.key] = true; });
      layersScrollToSelected = true;
    }
    renderRight(true);
    renderLayers();
    if (leftTab === 'media') renderLeft();
    updateInsertHint();
  }

  /** Several layers at once - from the selection box, Shift+click, or the layers list. */
  function selectMany(keys, primary, fromCanvas) {
    keys = (keys || []).filter(function (k, i, all) { return k && keyAllowed(k) && all.indexOf(k) === i; });
    if (keys.length < 2) {
      select(keys[0] || null, fromCanvas);
      return;
    }
    multi = keys;
    selected = primary && keys.indexOf(primary) !== -1 ? primary : keys[keys.length - 1];
    if (bridge && !fromCanvas) bridge.selectMany(keys, selected);
    sel = bridge ? bridge.info(selected) : null;
    if (sel) {
      sel.path.forEach(function (p) { if (p.key !== selected) expanded[p.key] = true; });
      layersScrollToSelected = true;
    }
    renderRight(true);
    renderLayers();
    if (leftTab === 'media') renderLeft();
    updateInsertHint();
  }

  function refreshSelection(rebuild) {
    if (!selected || !bridge) { if (rebuild) renderRight(); return; }
    sel = bridge.info(selected);
    if (rebuild) renderRight();
    else renderRightSoon();
  }

  /* ------------------------------------------------------------ right */

  var rightTimer = null;
  function renderRightSoon() {
    clearTimeout(rightTimer);
    rightTimer = setTimeout(renderRight, 60);
  }

  var rightPending = false;

  function renderRight(force) {
    // Rebuilt wholesale - but never out from under someone mid-typing or
    // mid-drag on a slider or colour picker. It catches up when they leave.
    var active = document.activeElement;
    if (force !== true && active && elRight.contains(active) && (active.tagName === 'TEXTAREA' || active.tagName === 'INPUT')) {
      rightPending = true;
      return;
    }
    rightPending = false;
    var focusId = active && elRight.contains(active) ? active.getAttribute('data-f') : null;
    var caret = focusId && typeof active.selectionStart === 'number' ? [active.selectionStart, active.selectionEnd] : null;
    var scroll = elRight.scrollTop;

    elRight.innerHTML = '';
    if (!selected || !sel) {
      elRight.appendChild(h('div', { class: 'wws-empty' }, [
        h('p', { class: 'wws-empty-title', text: DEVICES[bp].label + ' · ' + labelForRoute(route) }),
      ]));
      return;
    }

    var s = sel;
    var key = selected;

    var crumbs = h('div', { class: 'wws-crumbs' }, s.path.map(function (p, i) {
      return h('button', {
        type: 'button', class: p.key === key ? 'is-on' : '', title: p.name,
        onclick: function () { select(p.key); },
      }, [i === 0 ? 'Page' : p.name]);
    }));
    elRight.appendChild(crumbs);

    elRight.appendChild(h('div', { class: 'wws-head' }, [
      h('span', { class: 'wws-layer-icon t-' + s.type }, [icon(s.type)]),
      h('span', { class: 'wws-head-name', text: s.name }),
      h('span', { class: 'wws-head-size', text: s.width + ' × ' + s.height }),
    ]));

    if (bp !== 'd') {
      elRight.appendChild(h('div', { class: 'wws-bp-banner' }, [
        h('b', { text: DEVICES[bp].label }), ' · changes here apply to ' + DEVICES[bp].label + (bp === 't' ? ' and Phone' : '') + ' only',
      ]));
    }

    if (multi.length > 1) elRight.appendChild(sectionGroup());
    elRight.appendChild(sectionArrange(key, s));
    if (s.textual || s.labelText !== null || s.placeholder !== null || s.options || s.image || s.href !== null || s.embed !== null) elRight.appendChild(sectionContent(key, s));
    if(s.tag==='svg')elRight.appendChild(section('Icon',[row('Icon colour',colorField('color')),row('Stroke colour',colorField('stroke')),row('Stroke width',numField('stroke-width','')),row('Shape fill',selectField('fill',[['','As designed'],['none','None'],['currentColor','Icon colour']]))]));
    if (s.textual || s.labelText !== null || s.type === 'link' || s.type === 'button' || s.type === 'input') elRight.appendChild(sectionText(key, s));
    elRight.appendChild(sectionTransform(key, s));
    elRight.appendChild(sectionLayout(key, s));
    elRight.appendChild(sectionStyles(key, s));
    elRight.appendChild(sectionBasicAlignment(key));

    var n = doc.nodes[key];
    // An inserted layer's Desktop styles are its design, not a change to
    // one - "resetting" them would strip it bare - so only the smaller
    // breakpoints can be cleared back to it.
    elRight.appendChild(h('div', { class: 'wws-foot' }, s.inserted ? [
      bp !== 'd' ? h('button', { type: 'button', class: 'wws-btn', disabled: !(n && n.s && n.s[bp]), onclick: function () { resetLayer(key, false); } }, [icon('reset'), ' Clear ' + DEVICES[bp].label + ' changes']) : null,
    ] : [
      h('button', { type: 'button', class: 'wws-btn', disabled: !(n && n.s && n.s[bp]), onclick: function () { resetLayer(key, false); } }, [icon('reset'), ' Reset ' + DEVICES[bp].label]),
      h('button', { type: 'button', class: 'wws-btn', disabled: !n, onclick: function () { resetLayer(key, true); } }, [icon('reset'), ' Reset everything']),
    ]));

    elRight.scrollTop = scroll;
    if (focusId) {
      var again = elRight.querySelector('[data-f="' + focusId + '"]');
      if (again) {
        again.focus();
        if (caret && typeof again.setSelectionRange === 'function') {
          try { again.setSelectionRange(caret[0], caret[1]); } catch (e) {}
        }
      }
    }
  }

  elRight.addEventListener('focusout', function () {
    setTimeout(function () {
      var a = document.activeElement;
      if (rightPending && !(a && elRight.contains(a) && (a.tagName === 'TEXTAREA' || a.tagName === 'INPUT'))) {
        if (selected && bridge) sel = bridge.info(selected);
        renderRight();
      }
    }, 0);
  });

  function section(title, kids, extra) {
    return h('section', { class: 'wws-sec' }, [h('div', { class: 'wws-sec-head' }, [h('span', { text: title }), extra || null])].concat(kids));
  }

  function row(label, control, prop) {
    var controls = /^(INPUT|TEXTAREA|SELECT)$/.test(control.tagName) ? [control] : Array.prototype.slice.call(control.querySelectorAll('input,textarea,select'));
    controls.forEach(function(input,index){if(!input.getAttribute('aria-label'))input.setAttribute('aria-label',label+(controls.length>1?' '+(index+1):''));});
    var mark = null;
    if (prop && selected) {
      if (own(selected, prop) !== '') {
        mark = h('button', { type: 'button', class: 'wws-dot', title: 'Changed on ' + DEVICES[bp].label + ' - click to reset', onclick: function () { setStyle(selected, prop, ''); refreshSelection(true); } });
      } else if (effective(selected, prop) !== '') {
        mark = h('span', { class: 'wws-dot is-inherited', title: 'Inherited from a larger breakpoint' });
      }
    }
    return h('div', { class: 'wws-row' }, [h('label', { class: 'wws-label' }, [mark, h('span', { text: label })]), h('div', { class: 'wws-ctl' }, [control])]);
  }

  /* - controls - */

  function withUnit(raw, unit) {
    raw = String(raw).trim();
    if (raw === '') return '';
    if (/^-?\d*\.?\d+$/.test(raw)) return raw + (unit || '');
    return raw;
  }

  function display(v) {
    v = String(v || '');
    var m = v.match(/^(-?\d*\.?\d+)px$/);
    if (m) return String(Math.round(parseFloat(m[1]) * 10) / 10);
    return v;
  }

  // A short horizontal drag on a number changes its value; a click still
  // leaves the field available for precise typing.
  function scrubNumber(field, options) {
    var drag = null;
    field.classList.add('wws-scrub');
    field.addEventListener('pointerdown', function (e) {
      if (e.button !== 0 || field.disabled) return;
      var start = parseFloat(field.value || field.placeholder);
      if (!isFinite(start)) return;
      field.focus({ preventScroll: true });
      drag = { id: e.pointerId, x: e.clientX, value: start, moved: false };
      field.setPointerCapture(e.pointerId);
    });
    field.addEventListener('pointermove', function (e) {
      if (!drag || drag.id !== e.pointerId) return;
      var dx = e.clientX - drag.x;
      if (!drag.moved && Math.abs(dx) < 4) return;
      drag.moved = true;
      e.preventDefault();
      var step = options.step || 1;
      if (e.shiftKey) step *= 10;
      if (e.altKey) step /= 10;
      var next = drag.value + dx * step;
      if (options.min !== undefined) next = Math.max(options.min, next);
      if (options.max !== undefined) next = Math.min(options.max, next);
      next = Math.round(next * 100) / 100;
      if (field.value === String(next)) return;
      field.value = String(next);
      field.classList.add('is-scrubbing');
      options.commit();
    });
    function stop(e) {
      if (!drag || drag.id !== e.pointerId) return;
      if (field.hasPointerCapture(e.pointerId)) field.releasePointerCapture(e.pointerId);
      drag = null;
      field.classList.remove('is-scrubbing');
    }
    field.addEventListener('pointerup', stop);
    field.addEventListener('pointercancel', stop);
  }

  function commitNumber(prop, raw, unit) {
    var value = withUnit(raw, unit);
    if (prop !== 'width' && prop !== 'height') { setStyle(selected, prop, value); return; }
    var max = prop === 'width' ? 'max-width' : 'max-height';
    var min = prop === 'width' ? 'min-width' : 'min-height';
    var styles = {};
    styles[prop] = value;
    // An explicit size must not be silently capped by the site's design CSS.
    styles[max] = value ? 'none' : own(selected, max) === 'none' ? '' : own(selected, max);
    styles[min] = value ? '0px' : own(selected, min) === '0px' ? '' : own(selected, min);
    if (own(selected, prop) === styles[prop] && own(selected, max) === styles[max] && own(selected, min) === styles[min]) return;
    setStyles([{ key: selected, s: styles }], selected + '|' + prop + '|' + bp);
  }

  function numField(prop, unit, opts) {
    opts = opts || {};
    var key = selected;
    var value = effective(selected, prop);
    var computed = sel.computed[prop] || '';
    var fallback = opts.placeholder !== undefined ? opts.placeholder : computed;
    if (prop === 'line-height' && !value) {
      var line = parseFloat(computed);
      var font = parseFloat(sel.computed['font-size']);
      if (isFinite(line) && isFinite(font) && font > 0) fallback = String(Math.round(line / font * 100) / 100);
    }
    if (prop === 'letter-spacing' && fallback === 'normal') fallback = '0';
    var input = h('input', {
      type: 'text', class: 'wws-in', inputmode: 'decimal', 'data-f': prop,
      value: display(value || fallback), placeholder: display(fallback),
      oninput: function (e) { if (selected === key && !/^[-.]$/.test(e.target.value.trim())) commitNumber(prop, e.target.value, unit); },
      onchange: function (e) { if (selected === key) commitNumber(prop, e.target.value, unit); },
      onkeydown: function (e) {
        if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') return;
        var cur = parseFloat(e.target.value !== '' ? e.target.value : e.target.placeholder);
        if (isNaN(cur)) return;
        e.preventDefault();
        var step = (opts.step || 1) * (e.shiftKey ? 10 : 1);
        var next = Math.round((cur + (e.key === 'ArrowUp' ? step : -step)) * 100) / 100;
        e.target.value = next;
        if (selected === key) commitNumber(prop, String(next), unit);
      },
    });
    scrubNumber(input, { step: opts.step || (prop === 'font-size' ? 0.2 : 1), min: /^(font-size|line-height|padding-|border-)/.test(prop) ? 0 : undefined,
      commit: function () { if (selected === key) commitNumber(prop, input.value, unit); } });
    return input;
  }

  function selectField(prop, options) {
    var value = effective(selected, prop);
    return h('select', {
      class: 'wws-in', 'data-f': prop,
      onchange: function (e) { setStyle(selected, prop, e.target.value); refreshSelection(true); },
    }, options.map(function (o) {
      var opt = h('option', { value: o[0], text: o[1] });
      if (o[0] === value) opt.selected = true;
      return opt;
    }));
  }

  function segField(prop, options) {
    var value = effective(selected, prop);
    var computed = sel.computed[prop] || '';
    return h('div', { class: 'wws-seg wws-seg-full' }, options.map(function (o) {
      var on = value ? o[0] === value : o[0] === computed;
      return h('button', {
        type: 'button', class: (on ? 'is-on' : '') + (value && on ? ' is-set' : ''), title: o[2] || o[1],
        onclick: function () { setStyle(selected, prop, value === o[0] ? '' : o[0]); refreshSelection(true); },
      }, [o[1]]);
    }));
  }

  function toHex(rgb) {
    var m = String(rgb || '').match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?/);
    if (!m) return /^#/.test(rgb || '') ? rgb : '';
    if (m[4] !== undefined && parseFloat(m[4]) === 0) return '';
    return '#' + [m[1], m[2], m[3]].map(function (n) { return ('0' + parseInt(n, 10).toString(16)).slice(-2); }).join('').toUpperCase();
  }

  function colorField(prop) {
    var value = effective(selected, prop);
    var shown = value || toHex(sel.computed[prop]);
    var swatch = h('input', {
      type: 'color', class: 'wws-swatch', value: /^#[0-9a-f]{6}$/i.test(shown) ? shown : '#000000',
      oninput: function (e) { text.value = e.target.value.toUpperCase(); setStyle(selected, prop, e.target.value.toUpperCase()); },
    });
    var text = h('input', {
      type: 'text', class: 'wws-in', 'data-f': prop, value: value, placeholder: shown || 'none',
      oninput: function (e) {
        var v = e.target.value.trim();
        if (!v || /^#[0-9a-f]{3,8}$/i.test(v) || /^(transparent|rgba?\([\d\s.,%]+\))$/i.test(v)) setStyle(selected, prop, v);
      },
      onchange: function (e) {
        var v = e.target.value.trim();
        if (v && !/^#[0-9a-f]{3,8}$/i.test(v) && !/^(transparent|rgba?\([\d\s.,%]+\))$/i.test(v)) { toast('Use a colour like #6F4A2E.', true); return; }
        setStyle(selected, prop, v);
      },
    });
    var chips = h('div', { class: 'wws-palette' }, (cfg.palette || []).map(function (c) {
      return h('button', { type: 'button', class: 'wws-chip', title: c, style: 'background:' + c, onclick: function () { setStyle(selected, prop, c); refreshSelection(true); } });
    }));
    return h('div', { class: 'wws-color' }, [h('div', { class: 'wws-color-row' }, [swatch, text]), chips]);
  }

  function sidesField(base) {
    var sides = ['top', 'right', 'bottom', 'left'];
    return h('div', { class: 'wws-sides' }, sides.map(function (side) {
      var prop = base + '-' + side;
      var input = numField(prop, 'px');
      input.title = base + ' ' + side;
      return h('label', { class: 'wws-side' }, [input, h('span', { text: side.charAt(0).toUpperCase() })]);
    }));
  }

  /* - sections - */

  function sectionContent(key, s) {
    // Keep a visible entry into canvas typing for labels that share a button
    // with an icon, as well as ordinary paragraphs and headings.
    var kids = [];
    var n = doc.nodes[key] || {};
    if (s.textual) {
      kids.push(h('textarea', {
        class: 'wws-in wws-textarea', 'data-f': 'text', rows: Math.min(6, Math.max(2, Math.ceil((s.text || '').length / 32))),
        'aria-label':'Text content',
        oninput: function (e) { setContent(key, 'text', e.target.value); },
      }, [typeof n.text === 'string' ? n.text : s.text || '']));
    }
    if(s.labelText!==null)kids.push(row('Text content',h('textarea',{class:'wws-in wws-textarea',rows:2,'data-f':'label',oninput:function(e){setContent(key,'label',e.target.value);}},[typeof n.label==='string'?n.label:s.labelText])));
    if(s.textual || s.labelText!==null)kids.push(h('button',{type:'button',class:'wws-btn',onclick:function(){if(bridge)bridge.edit(key);}},['Edit text on canvas']));
    if(s.placeholder!==null)kids.push(row('Placeholder',h('input',{type:'text',class:'wws-in',value:typeof n.placeholder==='string'?n.placeholder:s.placeholder,oninput:function(e){setContent(key,'placeholder',e.target.value);}})));
    if(s.options) {
      s.options.forEach(function(option,index){
        kids.push(row('Option '+(index+1),h('input',{type:'text',class:'wws-in',value:(n.options && n.options[index] && n.options[index].value===option.value)?n.options[index].label:option.label,oninput:function(e){
          var options=(doc.nodes[key] && doc.nodes[key].options) || JSON.parse(JSON.stringify(s.options));
          options=JSON.parse(JSON.stringify(options));options[index]={value:option.value,label:e.target.value};setContent(key,'options',options);
        }})));
      });
    }
    if (s.image) {
      var src = n.src || s.image.src;
      kids.push(h('div', { class: 'wws-img' }, [h('img', { src: src, alt: '' })]));
      kids.push(h('div', { class: 'wws-btns' }, [
        h('button', { type: 'button', class: 'wws-btn', onclick: openLibrary }, ['Replace…']),
        h('button', { type: 'button', class: 'wws-btn', onclick: function () { leftTab = 'media'; renderLeft(); } }, ['Media']),
        n.src ? h('button', { type: 'button', class: 'wws-btn', onclick: function () { setContent(key, 'src', null); refreshSelection(true); } }, ['Original']) : null,
      ]));
      kids.push(row('Alt', h('input', {
        type: 'text', class: 'wws-in', 'data-f': 'alt', value: typeof n.alt === 'string' ? n.alt : s.image.alt,
        oninput: function (e) { setContent(key, 'alt', e.target.value); },
        onchange: function (e) { setContent(key, 'alt', e.target.value); },
      })));
      var ik = s.image.key || key;
      var fitNow = effective(ik, 'object-fit');
      kids.push(row('Fit', h('div', { class: 'wws-seg wws-seg-full' }, [['cover', 'Fill'], ['contain', 'Fit'], ['fill', 'Stretch']].map(function (o) {
        var on = fitNow ? fitNow === o[0] : s.image.fit === o[0];
        return h('button', {
          type: 'button', class: (on ? 'is-on' : '') + (fitNow && on ? ' is-set' : ''),
          onclick: function () { setStyle(ik, 'object-fit', fitNow === o[0] ? '' : o[0]); refreshSelection(true); },
        }, [o[1]]);
      }))));
      var pos = (effective(ik, 'object-position') || s.image.position || '50% 50%').split(/\s+/);
      var px = parseFloat(pos[0]); var py = parseFloat(pos[1]);
      if (isNaN(px)) px = 50; if (isNaN(py)) py = 50;
      var fx = h('input', { type: 'range', min: 0, max: 100, value: px, class: 'wws-range', 'data-f': 'fx',
        oninput: function () { setStyle(ik, 'object-position', fx.value + '% ' + fy.value + '%', ik + '|focal'); } });
      var fy = h('input', { type: 'range', min: 0, max: 100, value: py, class: 'wws-range', 'data-f': 'fy',
        oninput: function () { setStyle(ik, 'object-position', fx.value + '% ' + fy.value + '%', ik + '|focal'); } });
      kids.push(row('Focus X', fx));
      kids.push(row('Focus Y', fy));
    }
    if (s.embed !== null) {
      var isVideo = /youtube/.test(s.embed);
      kids.push(row(isVideo ? 'Video' : 'Map', h('input', {
        type: 'text', class: 'wws-in', 'data-f': 'embed', value: s.embed,
        placeholder: isVideo ? 'A YouTube link' : 'An address or a map link',
        oninput: function (e) {
          var url = embedUrl(e.target.value, isVideo ? 'video' : 'map');
          if (url) setTreeField(key, 'src', url);
        },
        onchange: function (e) {
          var url = embedUrl(e.target.value, isVideo ? 'video' : 'map');
          if (!url) { toast('That link cannot be embedded.', true); return; }
          setTreeField(key, 'src', url);
        },
      })));
    }
    if (s.href !== null || s.linkable) {
      var linkKey=s.linkKey||key;
      var linkNode=doc.nodes[linkKey]||{};
      var routes = [['', 'Link to a page…']];
      (cfg.pages || []).forEach(function (p) { routes.push([p.route, p.label]); });
      Object.keys(discovered).forEach(function (r) {
        if (!(cfg.pages || []).some(function (p) { return p.route === r; })) routes.push([r, discovered[r]]);
      });
      var hrefValue = typeof linkNode.href === 'string' ? linkNode.href : s.href || '';
      kids.push(row('Link', h('input', {
        type: 'text', class: 'wws-in', 'data-f': 'href', value: hrefValue,
        oninput: function (e) { setContent(linkKey, 'href', e.target.value.trim()); },
        onchange: function (e) { setContent(linkKey, 'href', e.target.value.trim()); },
      })));
      kids.push(row('Choose page', h('select', {
        class: 'wws-in', onchange: function (e) { if (e.target.value) { setContent(linkKey, 'href', e.target.value); refreshSelection(true); } },
      }, routes.map(function (r) { return h('option', { value: r[0], text: r[1] }); }))));
      kids.push(row('Open in new tab',h('input',{type:'checkbox',checked:typeof linkNode.newTab==='boolean'?linkNode.newTab:s.newTab,onchange:function(e){setContent(linkKey,'newTab',e.target.checked);}})));
    }
    return section('Content', kids);
  }

  function sectionText(key, s) {
    var fontValue = effective(key, 'font-family');
    var font = h('select', {
      class: 'wws-in', 'data-f': 'font-family',
      onchange: function (e) { setStyle(key, 'font-family', e.target.value); refreshSelection(true); },
    }, FONTS.map(function (f) {
      var opt = h('option', { value: f[0], text: f[1] });
      if (f[0] === fontValue) opt.selected = true;
      if (f[0]) opt.style.fontFamily = FONT_FACES[f[0]];
      return opt;
    }));
    return section('Text', [
      row('Font', font, 'font-family'),
      h('div', { class: 'wws-pair' }, [
        row('Size', numField('font-size', 'px'), 'font-size'),
        row('Weight', selectField('font-weight', [['', 'As designed'], ['300', 'Light'], ['400', 'Regular'], ['500', 'Medium'], ['600', 'Semibold'], ['700', 'Bold']]), 'font-weight'),
      ]),
      row('Colour', colorField('color'), 'color'),
      h('div', { class: 'wws-pair' }, [
        row('Line', numField('line-height', '', { step: 0.05 }), 'line-height'),
        row('Letter', numField('letter-spacing', 'px', { step: 0.1 }), 'letter-spacing'),
      ]),
      row('Align', segField('text-align', [['left', '⟸', 'Left'], ['center', '≡', 'Centre'], ['right', '⟹', 'Right'], ['justify', '☰', 'Justify']]), 'text-align'),
      row('Case', segField('text-transform', [['none', '—', 'As typed'], ['uppercase', 'AA', 'Uppercase'], ['lowercase', 'aa', 'Lowercase'], ['capitalize', 'Aa', 'Capitalise']]), 'text-transform'),
      row('Style', segField('font-style', [['normal', 'Regular'], ['italic', 'Italic']]), 'font-style'),
      row('Line', segField('text-decoration-line', [['none', 'None'], ['underline', 'Under'], ['line-through', 'Strike']]), 'text-decoration-line'),
    ]);
  }

  function canvasElement(key) {
    var page = frame.contentDocument;
    if (!page || !key) return null;
    var label=key.match(/~label([1-9][0-9]?)$/);
    if(label){var owner=canvasElement(key.slice(0,-label[0].length));return owner ? owner.querySelector(':scope > [data-ldrn-ws-label="'+label[1]+'"]') : null;}
    if (key.indexOf('n:') === 0) return page.querySelector('[data-ldrn-wsn="' + key.slice(2) + '"]');
    var parts = key.split('>');
    var anchor = parts[0];
    var selector = anchor === 't' ? '#root'
      : anchor.indexOf('e:') === 0 ? '.end-reveal'
      : anchor.indexOf('m:') === 0 ? 'main#main'
      : anchor.indexOf('w:') === 0 ? '[data-ldrn-ws="' + anchor.slice(2) + '"]' : '';
    if (!selector) return null;
    if (parts[1]) parts[1].split('.').forEach(function (n) {
      selector += ' > :nth-child(' + parseInt(n, 10) + ' of :not([data-ldrn-wsn]):not([data-ldrn-ws-label]))';
    });
    try { return page.querySelector(selector); } catch (e) { return null; }
  }

  function canvasPosition(key) {
    var el = canvasElement(key);
    if (!el) return null;
    var r = el.getBoundingClientRect();
    var win = frame.contentWindow;
    var fixed = win.getComputedStyle(el).position === 'fixed';
    return { x: r.left + r.width / 2 + (fixed ? 0 : win.scrollX), y: r.top + r.height / 2 + (fixed ? 0 : win.scrollY) };
  }

  function positionField(key, axis, initial) {
    var timer = null;
    var field = h('input', {
      type: 'text', inputmode: 'decimal', class: 'wws-in', 'data-f': 'motion-' + axis,
      'aria-label': 'Position ' + axis.toUpperCase(), value: String(Math.round(initial)),
      oninput: function () { clearTimeout(timer); timer = setTimeout(commit, 180); },
      onchange: function () { clearTimeout(timer); commit(); },
      onkeydown: function (e) {
        if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') return;
        e.preventDefault();
        field.value = String((parseFloat(field.value) || 0) + (e.key === 'ArrowUp' ? 1 : -1) * (e.shiftKey ? 10 : 1));
        clearTimeout(timer); commit();
      },
    });
    function commit() {
      if (selected !== key || !bridge || !bridge.nudge) return;
      var target = parseFloat(field.value);
      var now = canvasPosition(key);
      if (!isFinite(target) || !now || Math.abs(target) > 100000) return;
      var delta = target - now[axis];
      if (Math.abs(delta) < 0.5) return;
      bridge.nudge(axis === 'x' ? delta : 0, axis === 'y' ? delta : 0);
    }
    scrubNumber(field, { step: 1, commit: function () { clearTimeout(timer); commit(); } });
    return h('label', { class: 'wws-motion-coordinate' }, [h('span', { text: axis.toUpperCase() }), field]);
  }

  function scaleField(key, s, widthOnly) {
    var applied = null;
    var base = null;
    var field = h('input', {
      type: 'text', inputmode: 'decimal', class: 'wws-in', 'data-f': widthOnly ? 'motion-scale-width' : 'motion-scale',
      'aria-label': widthOnly ? 'Scale Width percent' : 'Scale percent', value: '100',
      onchange: commit,
      onkeydown: function (e) { if (e.key === 'Enter') commit(); },
    });
    function commit() {
      if (selected !== key || !bridge) return;
      var raw = field.value.trim();
      if (raw === applied || !/^\d+(?:\.\d+)?$/.test(raw)) return;
      var percent = parseFloat(raw);
      if (percent <= 0 || percent > 1000 || percent === 100) return;
      var el = canvasElement(key);
      if (!el) return;
      if (!base) {
        var liveStyle = frame.contentWindow.getComputedStyle(el);
        base = { width: el.offsetWidth || s.width, height: el.offsetHeight || s.height, font: parseFloat(liveStyle.fontSize) };
      }
      var factor = percent / 100;
      var styles = {};
      var width = Math.round(base.width * (widthOnly || uniformScale ? factor : 1) * 10) / 10;
      var height = Math.round(base.height * (!widthOnly ? factor : 1) * 10) / 10;
      // Lock both dimensions even when only one axis changes. An image with
      // width:auto would otherwise grow sideways when Height is increased.
      styles.width = width + 'px'; styles['max-width'] = 'none'; styles['min-width'] = '0px';
      styles.height = height + 'px'; styles['max-height'] = 'none'; styles['min-height'] = '0px';
      if (/flex/.test(s.parentDisplay || '')) styles.flex = 'none';
      if (!widthOnly && s.textual && isFinite(base.font)) styles['font-size'] = (Math.round(base.font * factor * 10) / 10) + 'px';
      applied = raw;
      setStyles([{ key: key, s: styles }], key + '|scale|' + bp);
    }
    scrubNumber(field, { step: 1, min: 1, max: 1000, commit: commit });
    return field;
  }

  function sectionTransform(key, s) {
    var pos = canvasPosition(key);
    if (!pos) return section('Transform', []);
    function motionRow(label, control) {
      var element = row(label, control);
      element.classList.add('wws-motion-row');
      return element;
    }
    var uniform = h('input', {
      type: 'checkbox', checked: uniformScale, 'aria-label': 'Uniform Scale',
      onchange: function (e) { uniformScale = e.target.checked; renderRight(true); },
    });
    return section('Transform', [
      motionRow('Position', h('div', { class: 'wws-motion-coordinates' }, [positionField(key, 'x', pos.x), positionField(key, 'y', pos.y)])),
      motionRow('Scale by', scaleField(key, s, false)),
      !uniformScale ? motionRow('Scale Width', scaleField(key, s, true)) : null,
      motionRow('Uniform Scale', h('label', { class: 'wws-motion-check' }, [uniform, h('span', { text: 'Keep proportions' })])),
    ]);
  }

  function sizeMode(v) {
    if (!v) return '';
    if (v === '100%') return 'fill';
    if (v === 'fit-content' || v === 'auto' || v === 'max-content') return 'fit';
    if (/px$/.test(v)) return 'fixed';
    return 'rel';
  }

  function sizeRow(label, prop) {
    var value = effective(selected, prop);
    var modeNow = sizeMode(value);
    var px = Math.round(prop === 'width' ? sel.width : sel.height);
    var modes = h('div', { class: 'wws-seg wws-seg-full' }, [['fixed', 'Fixed'], ['fill', 'Fill'], ['fit', 'Fit']].map(function (m) {
      return h('button', {
        type: 'button', class: modeNow === m[0] ? 'is-on is-set' : '',
        onclick: function () {
          var next = m[0] === 'fixed' ? px + 'px' : m[0] === 'fill' ? '100%' : prop === 'width' ? 'fit-content' : 'auto';
          commitNumber(prop, modeNow === m[0] ? '' : next, 'px');
          refreshSelection(true);
        },
      }, [m[1]]);
    }));
    var input = numField(prop, 'px', { placeholder: String(px) });
    return row(label, h('div', { class: 'wws-size' }, [input, modes]), prop);
  }

  function sectionSize(key, s) {
    return section('Size', [
      sizeRow('Width', 'width'),
      sizeRow('Height', 'height'),
      h('div', { class: 'wws-pair' }, [
        row('Min H', numField('min-height', 'px', { placeholder: '' }), 'min-height'),
        row('Max W', numField('max-width', 'px', { placeholder: '' }), 'max-width'),
      ]),
      row('Aspect', selectField('aspect-ratio', [['', 'As designed'], ['auto', 'Free'], ['1 / 1', '1:1'], ['4 / 3', '4:3'], ['3 / 2', '3:2'], ['16 / 9', '16:9'], ['3 / 4', '3:4'], ['4 / 5', '4:5']]), 'aspect-ratio'),
    ]);
  }

  function sectionLayout(key, s) {
    var kids = [];
    if (s.kids > 0) {
      var disp = effective(key, 'display') || s.computed.display;
      kids.push(row('Type', segField('display', [['block', 'Frame'], ['flex', 'Stack'], ['grid', 'Grid']]), 'display'));
      if (/flex/.test(disp)) {
        kids.push(row('Direction', segField('flex-direction', [['row', '→ Row', 'Horizontal'], ['column', '↓ Column', 'Vertical']]), 'flex-direction'));
        kids.push(row('Distribute', selectField('justify-content', [['', 'As designed'], ['flex-start', 'Start'], ['center', 'Center'], ['flex-end', 'End'], ['space-between', 'Space between'], ['space-around', 'Space around'], ['space-evenly', 'Space evenly']]), 'justify-content'));
        kids.push(row('Align', segField('align-items', [['flex-start', 'Start'], ['center', 'Center'], ['flex-end', 'End'], ['stretch', 'Stretch']]), 'align-items'));
        kids.push(row('Wrap', segField('flex-wrap', [['nowrap', 'No'], ['wrap', 'Yes']]), 'flex-wrap'));
      }
      if (/grid/.test(disp)) {
        var colsNow = effective(key, 'grid-template-columns');
        var m = colsNow.match(/^repeat\((\d+),/);
        kids.push(row('Columns', h('input', {
          type: 'text', class: 'wws-in', 'data-f': 'grid-cols',
          value: m ? m[1] : colsNow, placeholder: String((s.computed['grid-template-columns'] || '').split(' ').filter(Boolean).length || ''),
          oninput: function (e) {
            var v = e.target.value.trim();
            if (/^\d+$/.test(v) || !v) setStyle(key, 'grid-template-columns', v ? 'repeat(' + v + ', minmax(0, 1fr))' : '');
          },
          onchange: function (e) {
            var v = e.target.value.trim();
            setStyle(key, 'grid-template-columns', /^\d+$/.test(v) ? 'repeat(' + v + ', minmax(0, 1fr))' : v);
          },
        }), 'grid-template-columns'));
      }
      if (/flex|grid/.test(disp)) kids.push(row('Gap', numField('gap', 'px'), 'gap'));
    }
    if (/grid/.test(s.parentDisplay)) {
      var spanNow = (effective(key, 'grid-column').match(/span (\d+)/) || [])[1] || '';
      kids.push(row('Span', h('input', {
        type: 'text', class: 'wws-in', 'data-f': 'grid-span', value: spanNow, placeholder: '1',
        oninput: function (e) { var v = e.target.value.trim(); if (/^\d+$/.test(v) || !v) setStyle(key, 'grid-column', v && v !== '1' ? 'span ' + v : ''); },
        onchange: function (e) { var v = e.target.value.trim(); setStyle(key, 'grid-column', /^\d+$/.test(v) && v !== '1' ? 'span ' + v : ''); },
      }), 'grid-column'));
    }
    kids.push(h('div', { class: 'wws-sub', text: 'Padding' }));
    kids.push(sidesField('padding'));
    kids.push(h('div', { class: 'wws-sub', text: 'Margin' }));
    kids.push(sidesField('margin'));
    return section('Layout', kids);
  }

  function sectionStyles(key, s) {
    var hiddenHere = effective(key, 'display') === 'none';
    var opacity = effective(key, 'opacity');
    var opNow = opacity !== '' ? parseFloat(opacity) : parseFloat(s.computed.opacity || '1');
    var range = h('input', {
      type: 'range', min: 0, max: 100, value: Math.round(opNow * 100), class: 'wws-range', 'data-f': 'opacity-r',
      oninput: function (e) { setStyle(key, 'opacity', String(e.target.value / 100)); },
    });
    return section('Styles', [
      row('Visible', h('div', { class: 'wws-seg wws-seg-full' }, [
        h('button', { type: 'button', class: !hiddenHere ? 'is-on' : '', onclick: function () { if (hiddenHere) toggleHidden(key, true); } }, ['Yes']),
        h('button', { type: 'button', class: hiddenHere ? 'is-on is-set' : '', onclick: function () { if (!hiddenHere) toggleHidden(key, false); } }, ['No']),
      ]), 'display'),
      row('Opacity', range, 'opacity'),
      row('Fill', colorField('background-color'), 'background-color'),
      row('Radius', numField('border-radius', 'px', { placeholder: display(s.computed['border-top-left-radius']) }), 'border-radius'),
      h('div', { class: 'wws-pair' }, [
        row('Border', numField('border-width', 'px', { placeholder: display(s.computed['border-top-width']) }), 'border-width'),
        row('Style', selectField('border-style', [['', 'As designed'], ['none', 'None'], ['solid', 'Solid'], ['dashed', 'Dashed'], ['dotted', 'Dotted']]), 'border-style'),
      ]),
      row('Border col', colorField('border-color'), 'border-color'),
      row('Shadow', selectField('box-shadow', SHADOWS), 'box-shadow'),
      row('Overflow', segField('overflow', [['visible', 'Visible'], ['hidden', 'Hidden']]), 'overflow'),
    ]);
  }

  function stackOrder(items, keys, key) {
    if (items.length < 2 || items.map(function (x) { return x.key; }).join('|') === keys.join('|')) return;
    var outline = layerOutline();
    var group = outline.parentOf[key];
    if (!group || keys.indexOf(key) < 0) return;
    var from = items.findIndex(function (item) { return item.key === key; });
    var to = keys.indexOf(key);
    var targetZ = Number(items[to] && items[to].z) || 0;
    var z = targetZ + (to < from ? 1 : -1);
    snapshot();
    doc.layerOrder[group] = keys;
    var node = nodeFor(key, true);
    node.s = node.s || {};
    node.s[bp] = node.s[bp] || {};
    node.s[bp]['z-index'] = String(z);
    if (outline.byKey[key] && outline.byKey[key].position === 'static') node.s[bp].position = 'relative';
    changed(true);
  }

  function layerOutline() {
    if (!bridge || !bridge.outline) return { groups: [], sectionOf: {}, byKey: {}, parentOf: {}, peersOf: {} };
    var roots = bridge.outline();
    var page = roots.filter(function (x) { return x.key === 'm:' + route; })[0];
    var chrome = roots.filter(function (x) { return x !== page; });
    var head = chrome.filter(function (x) { return /navigation|header/i.test(x.name); });
    var tail = chrome.filter(function (x) { return head.indexOf(x) < 0; });
    // Sections are always shown in the same top-to-bottom flow as the page.
    // Only a deliberate drag can override that order.
    var groups = head.concat(page ? page.children : []).concat(tail);
    var sectionOf = {}, byKey = {};
    function index(node, group) {
      byKey[node.key] = node;
      sectionOf[node.key] = group;
      (node.children || []).forEach(function (kid) { index(kid, group); });
    }
    groups.forEach(function (group) { index(group, group.key); });
    var moved = {};
    Object.keys(doc.groups || {}).forEach(function (key) {
      var target = doc.groups[key];
      if (byKey[key] && byKey[target] && sectionOf[target] === target && sectionOf[key] !== target) {
        (moved[target] = moved[target] || []).push(byKey[key]);
      }
    });
    function copy(node, group) {
      var children = (node.children || []).filter(function (kid) {
        return !doc.groups[kid.key] || doc.groups[kid.key] === group || !byKey[doc.groups[kid.key]];
      });
      if (node.key === group && moved[group]) children = children.concat(moved[group]);
      var out = Object.assign({}, node);
      out.children = children.map(function (kid) { return copy(kid, group); });
      return out;
    }
    function ordered(nodes, group) {
      var saved = doc.layerOrder[group] || [];
      return nodes.map(function (item, index) { return { item: item, index: index }; })
        .sort(function (a, b) {
          var ai = saved.indexOf(a.item.key), bi = saved.indexOf(b.item.key);
          if (ai >= 0 && bi >= 0) return ai - bi;
          if (ai >= 0) return -1;
          if (bi >= 0) return 1;
          // Page sections follow the page. Within a section, front layers
          // come first; equal-depth layers follow their natural paint order.
          if (group === 'page') return a.index - b.index;
          return b.item.z - a.item.z || b.index - a.index;
        }).map(function (entry) { return entry.item; });
    }
    var result = { groups: [], sectionOf: sectionOf, byKey: byKey, parentOf: {}, peersOf: {} };
    result.groups = ordered(groups.map(function (group) {
      var copied = copy(group, group.key);
      var flat = [];
      function walk(children) {
        children.forEach(function (child) { flat.push(Object.assign({}, child, { children: [] })); walk(child.children); });
      }
      walk(copied.children);
      copied.children = ordered(flat, group.key);
      return copied;
    }), 'page');
    result.groups.forEach(function (group) {
      result.parentOf[group.key] = 'page';
      result.peersOf[group.key] = result.groups;
      group.children.forEach(function (item) {
        result.parentOf[item.key] = group.key;
        result.peersOf[item.key] = group.children;
      });
    });
    return result;
  }

  function assignLayerGroup(key, group, outline) {
    if (!outline.byKey[key] || !outline.byKey[group] || outline.sectionOf[group] !== group || key === group) return;
    if (outline.sectionOf[key] === key || !/^(m:|n:)/.test(key) || !/^m:/.test(group)) return;
    if (/^m:/.test(key) && key.split('>')[0] !== group.split('>')[0]) return;
    if ((doc.groups[key] || outline.sectionOf[key]) === group) return;
    snapshot();
    if (outline.sectionOf[key] === group) delete doc.groups[key];
    else doc.groups[key] = group;
    changed(true);
  }

  function renderLayerOutline() {
    var outline = layerOutline();
    var list = h('div', { class: 'wws-stack-tree', role: 'tree', 'aria-label': 'Page sections and layers' });
    function draw(node, depth, group) {
      var isGroup = node.key === group;
      var open = !!layerStackExpanded[node.key];
      var hasKids = !!node.children.length;
      var item = h('div', {
        class: 'wws-stack-tree-row' + (node.key === selected ? ' is-selected' : '') + (isGroup ? ' is-group' : ''),
        role: 'treeitem', 'aria-expanded': hasKids ? (open ? 'true' : 'false') : null,
        draggable: 'true', 'data-key': node.key,
        style: 'padding-left:' + (8 + depth * 12) + 'px',
        ondragstart: function (e) { layerStackDrag = node.key; e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', node.key); },
        ondragover: function (e) { if (layerStackDrag && layerStackDrag !== node.key) { e.preventDefault(); e.currentTarget.classList.add('is-drop'); } },
        ondragleave: function (e) { e.currentTarget.classList.remove('is-drop'); },
        ondrop: function (e) {
          e.preventDefault(); e.stopPropagation(); e.currentTarget.classList.remove('is-drop');
          var source = layerStackDrag;
          layerStackDrag = null;
          if (!source || source === node.key) return;
          var sourceNode = outline.byKey[source];
          if (!sourceNode) return;
          if (isGroup && outline.sectionOf[source] !== source) { assignLayerGroup(source, group, outline); return; }
          if (outline.parentOf[source] !== outline.parentOf[node.key]) return;
          var peers = outline.peersOf[source];
          var keys = peers.map(function (x) { return x.key; });
          if (keys.indexOf(source) < 0 || keys.indexOf(node.key) < 0) return;
          var from = keys.indexOf(source);
          var target = keys.indexOf(node.key);
          keys.splice(from, 1);
          keys.splice(keys.indexOf(node.key) + (from < target ? 1 : 0), 0, source);
          stackOrder(peers, keys, source);
        },
        ondragend: function (e) { layerStackDrag = null; e.currentTarget.classList.remove('is-drop'); },
        onclick: function () { if (node.key !== selected) select(node.key); },
      }, [
        hasKids ? h('button', { type: 'button', class: 'wws-stack-caret', title: open ? 'Collapse' : 'Expand',
          onclick: function (e) { e.stopPropagation(); layerStackExpanded[node.key] = !open; renderRight(true); } }, [open ? '⌄' : '›'])
          : h('span', { class: 'wws-stack-caret', 'aria-hidden': 'true' }),
        h('span', { class: 'wws-stack-icon' }, [icon(node.type)]),
        h('span', { class: 'wws-stack-name', text: node.name }),
        isGroup ? h('span', { class: 'wws-stack-count', text: String(node.children.length) }) : null,
      ]);
      list.appendChild(item);
      if (open) node.children.forEach(function (kid) { draw(kid, depth + 1, group); });
    }
    outline.groups.forEach(function (group) { draw(group, 0, group.key); });
    return list;
  }

  function sectionBasicAlignment(key) {
    var outline = layerOutline();
    var items = outline.peersOf[key] || [];
    var at = items.findIndex(function (x) { return x.key === key; });
    function shift(to) {
      if (at < 0 || to < 0 || to >= items.length || to === at) return;
      var keys = items.map(function (x) { return x.key; });
      keys.splice(to, 0, keys.splice(at, 1)[0]);
      stackOrder(items, keys, key);
    }
    var actions = h('div', { class: 'wws-stack-actions' }, [
      h('button', { type: 'button', disabled: at <= 0, onclick: function () { shift(at - 1); } }, ['Forward']),
      h('button', { type: 'button', disabled: at < 0 || at >= items.length - 1, onclick: function () { shift(at + 1); } }, ['Backward']),
      h('button', { type: 'button', disabled: at <= 0, onclick: function () { shift(0); } }, ['To front']),
      h('button', { type: 'button', disabled: at < 0 || at >= items.length - 1, onclick: function () { shift(items.length - 1); } }, ['To back']),
    ]);
    var kids = [actions];
    if (layerStackOpen) kids.push(renderLayerOutline());
    return section('Basic alignment', kids, h('button', {
      type: 'button', class: 'wws-stack-view', 'aria-expanded': layerStackOpen ? 'true' : 'false',
      onclick: function () {
        layerStackOpen = !layerStackOpen;
        if (layerStackOpen) {
          var parent = key;
          while (parent && parent !== 'page') { layerStackExpanded[parent] = true; parent = outline.parentOf[parent]; }
        }
        renderRight(true);
      },
    }, [layerStackOpen ? 'Close' : 'View all']));
  }

  function sectionGroup() {
    var items = multi.map(function (k) {
      var info = bridge ? bridge.info(k) : null;
      return { key: k, name: info ? info.name : k };
    });
    return section('Selection', [
      h('div', { class: 'wws-group-list' }, items.map(function (it) {
        return h('button', {
          type: 'button', class: 'wws-group-item' + (it.key === selected ? ' is-on' : ''), title: 'Edit this one in the panel below',
          onclick: function () { selectMany(multi, it.key); },
        }, [it.name]);
      })),
      h('div', { class: 'wws-btns' }, [
        h('button', { type: 'button', class: 'wws-btn', onclick: function () { hideMany(multi.slice()); } }, [icon('eyeOff'), ' Hide all']),
        h('button', { type: 'button', class: 'wws-btn', onclick: function () { resetGeometry(multi.slice()); } }, [icon('undo'), ' Reset position & size']),
      ]),
    ]);
  }

  function sectionArrange(key, s) {
    var isPage = /^m:[^>]*$/.test(key);
    return section('Arrange', [
      h('div', { class: 'wws-btns' }, [
        h('button', { type: 'button', class: 'wws-btn', disabled: isPage, title: 'Ctrl+D', onclick: function () { duplicate(key); } }, [icon('copy'), ' Duplicate']),
        s.inserted
          ? h('button', { type: 'button', class: 'wws-btn wws-btn-danger', title: 'Delete', onclick: function () { removeInserted(key.slice(2)); } }, [icon('trash'), ' Delete'])
          : h('button', { type: 'button', class: 'wws-btn', disabled: isPage, title: 'Delete key', onclick: function () { toggleHidden(key, false); } }, [icon('eyeOff'), ' Hide']),
      ]),
      !isPage ? h('button',{type:'button',class:'wws-btn wws-btn-block',onclick:function(){saveReusable(key,s.name);}},['Save to My sections']) : null,
      s.siblings.length > 1 ? h('div', { class: 'wws-btns' }, [
        h('button', { type: 'button', class: 'wws-btn', disabled: s.index <= 0, onclick: function () { move(key, -1); } }, [icon('up'), ' Up']),
        h('button', { type: 'button', class: 'wws-btn', disabled: s.index >= s.siblings.length - 1, onclick: function () { move(key, 1); } }, [icon('down'), ' Down']),
        h('span', { class: 'wws-hint wws-inline', text: (s.index + 1) + ' of ' + s.siblings.length }),
      ]) : null,
      s.parentKey && !isPage ? h('button', { type: 'button', class: 'wws-link', onclick: function () { select(s.parentKey); } }, ['Select parent ↑']) : null,
      hasGeometry(key) ? h('div', { class: 'wws-btns' }, [
        h('button', { type: 'button', class: 'wws-btn', title: 'Undo every drag and resize on ' + DEVICES[bp].label, onclick: function () { resetGeometry([key]); } }, [icon('undo'), ' Reset position & size']),
      ]) : null,
    ]);
  }

  function sectionPosition(key, s) {
    var pos = effective(key, 'position') || s.computed.position || 'static';
    var kids = [row('Type', selectField('position', [['', 'As designed'], ['static', 'In flow'], ['relative', 'Relative'], ['absolute', 'Absolute'], ['sticky', 'Sticky']]), 'position')];
    if (pos !== 'static') {
      kids.push(h('div', { class: 'wws-sides' }, ['top', 'right', 'bottom', 'left'].map(function (side) {
        var input = numField(side, 'px', { placeholder: '' });
        return h('label', { class: 'wws-side' }, [input, h('span', { text: side.charAt(0).toUpperCase() })]);
      })));
      kids.push(row('Layer', numField('z-index', '', { placeholder: s.computed['z-index'] || 'auto' }), 'z-index'));
    }
    return section('Position', kids);
  }

  /* ------------------------------------------------------------ keys */

  function shortcut(k) {
    // The media library is a modal of its own; its keys are its business.
    if (document.body.classList.contains('modal-open')) return;
    var key = k.key;
    if (k.meta && (key === 'z' || key === 'Z')) { if (k.shift) redo(); else undo(); return; }
    if (k.meta && key === 'y') { redo(); return; }
    if (k.meta && (key === 's' || key === 'S')) { saveNow(); return; }
    if (k.meta && (key === 'd' || key === 'D')) { if (selected) duplicate(selected); return; }
    if (k.meta) return;
    // Framer's zoom keys: Shift+1 fits the page, Shift+0 is 100%.
    if (k.shift && (key === '!' || key === '1')) { zoom = 'fit'; layoutCanvas(); renderTop(); stage.scrollLeft = 0; return; }
    if (k.shift && (key === ')' || key === '0')) { zoomAt(1); return; }
    if (key === 'v' || key === 'V') setMode('select');
    else if (key === 'p' || key === 'P') setMode(mode === 'preview' ? 'select' : 'preview');
    else if (key === 'Escape') { if (sel && sel.parentKey && sel.path.length > 1) select(sel.parentKey); else select(null); }
    else if ((key === 'Delete' || key === 'Backspace') && multi.length > 1) hideMany(multi.slice());
    else if ((key === 'Delete' || key === 'Backspace') && selected) {
      if (sel && sel.inserted) removeInserted(selected.slice(2));
      else if (!/^m:[^>]*$/.test(selected)) toggleHidden(selected, false);
    }
    else if (k.alt && key === 'ArrowUp' && selected) move(selected, -1);
    else if (k.alt && key === 'ArrowDown' && selected) move(selected, 1);
    else if (key === 'Enter' && selected && bridge) bridge.edit(selected);
    else if (!k.alt && /^Arrow(Up|Down|Left|Right)$/.test(key) && selected && bridge && bridge.nudge) {
      var step = k.shift ? 10 : 1;
      bridge.nudge(key === 'ArrowLeft' ? -step : key === 'ArrowRight' ? step : 0, key === 'ArrowUp' ? -step : key === 'ArrowDown' ? step : 0);
    }
  }

  document.addEventListener('keydown', function (e) {
    var t = e.target;
    var typing = t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable);
    var meta = e.metaKey || e.ctrlKey;
    if (typing && !(meta && (e.key === 's' || e.key === 'S'))) return;
    if (meta && /^[zZyYsSdD]$/.test(e.key)) e.preventDefault();
    if (!meta && /^(Delete|Backspace)$/.test(e.key)) e.preventDefault();
    if (!meta && !e.altKey && selected && /^Arrow(Up|Down|Left|Right)$/.test(e.key)) e.preventDefault();
    var onButton = t && /^(BUTTON|A|SUMMARY)$/.test(t.tagName);
    if (e.key === ' ' && !meta && !onButton) {
      // Space is the hand tool - except on a focused button, which Space presses.
      e.preventDefault();
      if (bridge && bridge.hand) bridge.hand(true);
      return;
    }
    shortcut({ key: e.key, meta: meta, shift: e.shiftKey, alt: e.altKey });
  });
  document.addEventListener('keyup', function (e) {
    if (e.key === ' ' && bridge && bridge.hand) bridge.hand(false);
  });
  window.addEventListener('blur', function () { if (bridge && bridge.hand) bridge.hand(false); });

  window.addEventListener('beforeunload', function (e) {
    if (!localDirty && !conflict) return;
    keepLocally();
    if (saveState === 'unsaved') saveNow();
    e.preventDefault();
    e.returnValue = '';
  });

  /* ------------------------------------------------------------ go */

  renderTop();
  renderLeft();
  renderRight();
  layoutCanvas();
  setTimeout(layoutCanvas, 50);
  offerKept();
})();
