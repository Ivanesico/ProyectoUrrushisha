// Saca de las resoluciones del BOE las líneas de picaduras de pipa que empiezan por un prefijo, con su precio.
const fs = require('fs');
const [, , prefix, out, ...inputs] = process.argv;
const found = inputs.flatMap((f) => JSON.parse(fs.readFileSync(f, 'utf8')).found);
const decode = (s) => s.replace(/<[^>]+>/g, '').replace(/&amp;/g, '&').replace(/&#39;|&apos;/g, "'")
  .replace(/&quot;/g, '"').replace(/&nbsp;/g, ' ').replace(/&#(\d+);/g, (_, n) => String.fromCharCode(n)).replace(/\s+/g, ' ').trim();
const re = new RegExp('^' + prefix, 'i');
(async () => {
  const rows = [], failed = [];
  for (let i = 0; i < found.length; i += 6) {
    await Promise.all(found.slice(i, i + 6).map(async (r) => {
      for (let attempt = 0; attempt < 3; attempt++) {
        try {
          const res = await fetch('https://www.boe.es/diario_boe/txt.php?id=' + r.id);
          if (!res.ok) throw new Error(res.status);
          const html = await res.text();
          // Cada resolución tiene una parte para Península e Illes Balears y otra para Ceuta y Melilla,
          // con precios distintos. La zona de cada tabla la marca el último párrafo "Los precios de venta
          // al público ... en Expendedurías ... de <zona>" que aparece antes de ella.
          let area = 'desconocida';
          for (const chunk of html.split(/<table/i)) {
            const [head, ...rest] = chunk.split(/<\/table>/i);
            if (rest.length && /PICADURAS DE PIPA/i.test(head.split(/<\/caption>/i)[0])) {
              for (const m of head.matchAll(/<td class="cuerpo_tabla_izq">([\s\S]*?)<\/td>\s*<td[^>]*>([\s\S]*?)<\/td>/g)) {
                const text = decode(m[1]);
                if (re.test(text)) rows.push({ date: r.date, id: r.id, area, text, price: decode(m[2]) });
              }
            }
            for (const p of rest.join(' ').matchAll(/<p[^>]*>([\s\S]*?)<\/p>/gi)) {
              const t = decode(p[1]);
              if (/precios de venta al p[úu]blico/i.test(t)) {
                if (/Ceuta y Melilla/i.test(t)) area = 'Ceuta y Melilla';
                else if (/Pen[íi]nsula/i.test(t)) area = 'Península y Baleares';
              }
            }
            // El texto antes de la primera tabla también puede fijar la zona
            if (!rest.length) {
              for (const p of head.matchAll(/<p[^>]*>([\s\S]*?)<\/p>/gi)) {
                const t = decode(p[1]);
                if (/precios de venta al p[úu]blico/i.test(t)) {
                  if (/Ceuta y Melilla/i.test(t)) area = 'Ceuta y Melilla';
                  else if (/Pen[íi]nsula/i.test(t)) area = 'Península y Baleares';
                }
              }
            }
          }
          return;
        } catch (e) { if (attempt === 2) failed.push(r.id + ' ' + e.message); }
      }
    }));
  }
  rows.sort((a, b) => a.date.localeCompare(b.date));
  fs.writeFileSync(out, JSON.stringify({ rows, failed }, null, 1));
  console.log('filas', rows.length, 'fallos', failed);
})();
