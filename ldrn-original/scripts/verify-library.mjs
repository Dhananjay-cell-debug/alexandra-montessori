import { readFileSync, writeFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import assert from 'node:assert/strict';
const scope = {window:{}};
runInNewContext(readFileSync(new URL('../wordpress/plugins/ldrn-workspace/assets/studio-library.js', import.meta.url), 'utf8'), scope);
const library = scope.window.ldrnStudioLibrary;
assert.ok(library.templates.length >= 30);
assert.equal(new Set(library.templates.map(t=>t.id)).size, library.templates.length);
const fixtures = library.templates.map(template=>({id:template.id,...library.instantiate(template.build())}));
const ids = new Set();
function walk(node, fixture) {
  assert.ok(!ids.has(node.id), 'Each inserted element needs its own identity'); ids.add(node.id);
  assert.ok(fixture.nodes['n:'+node.id], 'Each element must have inspector styles');
  if(node.tag==='a') {
    assert.equal(node.text,undefined,'A button container must not own its label');
    assert.ok(node.kids?.some(kid=>kid.tag==='span' && typeof kid.text==='string'),'A button needs an independently editable label child');
  }
  (node.kids || []).forEach(kid=>walk(kid,fixture));
}
fixtures.forEach(fixture=>walk(fixture.tree,fixture));
const reusable=library.instantiate({tag:'a',href:'/contact',newTab:true,s:{display:'inline-flex'},edits:{newTab:false,href:'/book-a-visit'},kids:[{id:'oldlabel',tag:'span',text:'Edited label',s:{color:'#123456'},edits:{text:'Saved label'}}]});
assert.equal(reusable.tree.newTab,true,'Rendered target must survive copying');
assert.equal(reusable.nodes['n:'+reusable.tree.id].newTab,false,'Saved explicit target override must survive insertion');
assert.equal(reusable.nodes['n:'+reusable.tree.id].href,'/book-a-visit','Saved destination override must survive insertion');
assert.equal(reusable.nodes['n:'+reusable.tree.kids[0].id].text,'Saved label','Saved independent child edits must survive insertion');
const svgSource={tag:'svg',attrs:{viewBox:'0 0 24 24',stroke:'currentColor'},kids:[{tag:'path',attrs:{d:'M 3 12 L 21 12'}}]};
const svgCopy=library.instantiate(svgSource);
assert.equal(svgCopy.tree.kids[0].attrs.d,'M 3 12 L 21 12','Icon geometry must survive reusable insertion');
svgCopy.tree.attrs.stroke='#123456';
assert.equal(svgSource.attrs.stroke,'currentColor','Copied icon attributes must remain independent');
writeFileSync(new URL('./library-fixtures.json', import.meta.url), JSON.stringify(fixtures));
console.log(`${fixtures.length} nursery templates, ${library.elements.length} elements, ${ids.size} independently editable layers verified.`);
