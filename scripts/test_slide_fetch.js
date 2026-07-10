const https = require('https');
function get(url) {
  return new Promise((resolve, reject) => {
    https.get(url, (r) => {
      let d = '';
      r.on('data', (c) => (d += c));
      r.on('end', () => resolve({ status: r.statusCode, body: d, type: r.headers['content-type'] }));
    }).on('error', reject);
  });
}
(async () => {
  const home = await get('https://dmchurch.kr/');
  const imgs = [...home.body.matchAll(/class="church-slide-img[^"]*" src="([^"]+)"/g)].map((m) => m[1]);
  console.log('slide_imgs', imgs.length, imgs);
  console.log('has_init_script', home.body.includes('churchMainSlide'));
  for (const src of imgs.slice(0, 2)) {
    const r = await get('https://dmchurch.kr' + src.split('?')[0]);
    console.log('img', src, r.status, r.type);
  }
})();
