// Recorre los sumarios del BOE entre dos fechas y guarda las resoluciones del Comisionado
// para el Mercado de Tabacos sobre precios de venta al público.
const fs = require('fs');
const [, , from, to, out] = process.argv;

function* days(a, b) {
  const d = new Date(a + 'T00:00:00Z'), end = new Date(b + 'T00:00:00Z');
  for (; d <= end; d.setUTCDate(d.getUTCDate() + 1)) yield d.toISOString().slice(0, 10).replace(/-/g, '');
}

function walk(node, found, date) {
  if (Array.isArray(node)) return node.forEach((n) => walk(n, found, date));
  if (node && typeof node === 'object') {
    if (node.identificador && typeof node.titulo === 'string'
      && /Mercado de Tabacos/i.test(node.titulo) && /precios de venta al p[úu]blico/i.test(node.titulo)) {
      found.push({ date, id: node.identificador, title: node.titulo });
    }
    Object.values(node).forEach((v) => walk(v, found, date));
  }
}

(async () => {
  const found = [], failed = [];
  const list = [...days(from, to)];
  for (let i = 0; i < list.length; i += 8) {
    await Promise.all(list.slice(i, i + 8).map(async (day) => {
      for (let attempt = 0; attempt < 3; attempt++) {
        try {
          const res = await fetch('https://www.boe.es/datosabiertos/api/boe/sumario/' + day, { headers: { Accept: 'application/json' } });
          if (res.status === 404) return; // sin BOE ese día (domingos)
          if (!res.ok) throw new Error(res.status);
          walk((await res.json()).data, found, day);
          return;
        } catch (e) { if (attempt === 2) failed.push(day + ' ' + e.message); }
      }
    }));
  }
  found.sort((a, b) => a.date.localeCompare(b.date));
  fs.writeFileSync(out, JSON.stringify({ found, failed }, null, 1));
  console.log('resoluciones', found.length, 'fallos', failed.length, failed.slice(0, 10));
})();
