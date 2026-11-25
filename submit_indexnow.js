const https = require('https');
const fs = require('fs');
const path = require('path');

const key = '8e9f2a7b1c3d4e5f6a0b9c8d7e6f5a4b';
const host = 'jouleassetvaluers.com';
const keyLocation = `https://${host}/${key}.txt`;

const sitemapPath = path.join(__dirname, 'sitemap.xml');
const xmlContent = fs.readFileSync(sitemapPath, 'utf8');
const urlMatches = Array.from(xmlContent.matchAll(/<loc>([^<]+)<\/loc>/g)).map(m => m[1]);

const payload = JSON.stringify({
  host: host,
  key: key,
  keyLocation: keyLocation,
  urlList: urlMatches
});

console.log('IndexNow Payload preparing for', urlMatches.length, 'URLs...');

const req = https.request('https://api.indexnow.org/indexnow', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json; charset=utf-8',
    'Content-Length': Buffer.byteLength(payload)
  }
}, (res) => {
  console.log('IndexNow Response Status:', res.statusCode, res.statusMessage);
  res.on('data', d => process.stdout.write(d));
});

req.on('error', (e) => {
  console.log('IndexNow request note (if offline or pending upload):', e.message);
});

req.write(payload);
req.end();
