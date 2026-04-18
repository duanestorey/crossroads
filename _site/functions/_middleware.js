// Markdown content negotiation. When an agent sends `Accept: text/markdown`,
// serve the .md companion crossroads writes next to every HTML page.
// Primary mechanism is Cloudflare Transform Rules; this is a fallback.

export async function onRequest({ request, next }) {
  if (!wantsMarkdown(request.headers.get('Accept'))) {
    return next();
  }

  const url = new URL(request.url);
  const mdUrl = new URL(resolveMdPath(url.pathname), url);

  let mdResp = await next(new Request(mdUrl, request));

  if (!mdResp.ok) {
    mdResp = await next(new Request(new URL('/llms.txt', url), request));
  }

  if (!mdResp.ok) {
    return next();
  }

  const text = await mdResp.text();

  return new Response(text, {
    status: 200,
    headers: {
      'Content-Type': 'text/markdown; charset=utf-8',
      'Vary': 'Accept',
      'x-markdown-tokens': String(Math.ceil(text.length / 4)),
      'Cache-Control': 'public, max-age=300, must-revalidate',
    },
  });
}

function wantsMarkdown(accept) {
  return typeof accept === 'string' && /\btext\/markdown\b/i.test(accept);
}

function resolveMdPath(pathname) {
  if (pathname.endsWith('/')) return pathname + 'index.md';
  if (pathname.endsWith('.html')) return pathname.replace(/\.html$/, '.md');
  if (/\.[a-z0-9]+$/i.test(pathname)) return pathname;
  return pathname + '.md';
}
