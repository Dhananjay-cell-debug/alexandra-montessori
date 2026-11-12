import { createServer } from 'vite';
import { readFileSync, writeFileSync } from 'node:fs';
import { homedir } from 'node:os';
import { resolve } from 'node:path';
import assert from 'node:assert/strict';
import React from 'react';
import { renderToString } from 'react-dom/server';
import { MemoryRouter, Routes, Route } from 'react-router-dom';

const evidence = resolve(homedir(), '.codex/private-deployments/20261111-alexandra-site-update');
globalThis.window = { amData: JSON.parse(readFileSync(resolve(evidence, 'amData.json'))), amHomeDesign: {} };
const server = await createServer({ server: { middlewareMode: true, open: false }, appType: 'custom' });
const render = (Component, path, pattern) => renderToString(React.createElement(MemoryRouter, {initialEntries:[path]}, React.createElement(Routes, null, React.createElement(Route, {path:pattern, element:React.createElement(Component)}))));
try {
  const Nursery = (await server.ssrLoadModule('/src/pages/NurseryDetail.jsx')).default;
  for (const slug of ['hounslow', 'heston', 'hammersmith']) {
    const html = render(Nursery, `/nurseries/${slug}`, '/nurseries/:slug');
    assert.match(html, /id="contact"/);
    assert.match(html, new RegExp(`name="branch" value="${slug}"`));
    assert.match(html, /type="submit"/);
    assert.doesNotMatch(html, /type="file"|Check fees/);
    assert.equal(html.includes('Freshly prepared vegetarian meals'), slug !== 'hammersmith');
    writeFileSync(resolve(evidence, `render-${slug}.html`), html);
  }
  const Fees = (await server.ssrLoadModule('/src/pages/Fees.jsx')).default;
  const fees = render(Fees, '/fees', '/fees');
  assert.match(fees, /id="calculator"/);
  assert.match(fees, /Days per week/);
  assert.match(fees, /Chargeable hours/);
  assert.match(fees, /15 hours/);
  assert.doesNotMatch(fees, /href="\/fee-calculator"/);
  const Contact = (await server.ssrLoadModule('/src/pages/Contact.jsx')).default;
  const contact = render(Contact, '/contact?event=Open%20Morning', '/contact');
  for(const slug of ['hounslow','heston','hammersmith']) assert.match(contact, new RegExp(`/nurseries/${slug}\\?event=Open%20Morning#contact`));
  assert.doesNotMatch(contact, /<form/);
  writeFileSync(resolve(evidence, 'render-fees.html'), fees);
  console.log('PASS: all three nursery forms, branch bindings, no uploads/fee links, vegetarian visibility, inline calculator and event-context links.');
} finally { await server.close(); }
