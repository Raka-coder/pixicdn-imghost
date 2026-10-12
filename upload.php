<?php
/**
 * ============================================================
 * File: upload.php
 * Deskripsi: UI upload drag-drop native + preview + progress + Toastify
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/config.example.php'; // dev-fill saja, guarded defined()
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login(); // Wajib akun — tamu diarahkan ke login
start_secure_session();
$token = csrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload — PixiCDN</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="/assets/app.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen" style="font-family:'Work Sans',system-ui,sans-serif">
<header class="bg-white border-b border-[#CBD2D6] h-16">
<nav class="max-w-[1120px] mx-auto px-6 h-16 flex items-center justify-between">
<a href="/" class="text-xl font-bold text-[#003087]">PixiCDN</a>
<div class="flex items-center gap-3 text-[15px] font-medium">
<a href="/gallery.php" class="hover:text-[#003087] hover:underline hover:underline-offset-4">Gallery</a>
<a href="/dashboard/" class="hover:text-[#003087] hover:underline hover:underline-offset-4">Dashboard</a>
</div>
</nav>
</header>
<main class="max-w-3xl mx-auto px-6 py-10">
<h1 class="font-bold" style="font-size:32px">Upload Gambar</h1>
<p class="text-[#687173] mb-6">JPG, PNG, GIF, WebP · maks 5 MB/file · maks 5 file</p>
<div id="drop" class="border-2 border-dashed border-[#CBD2D6] bg-white rounded-2xl p-10 text-center cursor-pointer hover:border-[#003087]">
<p class="mb-2 font-medium">Tarik &amp; letakkan gambar di sini, atau klik untuk pilih</p>
<input id="files" type="file" accept=".jpg,.jpeg,.png,.gif,.webp" multiple class="hidden">
<div id="preview" class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4"></div>
</div>
<div class="h-2 bg-white border border-[#CBD2D6] rounded mt-4 overflow-hidden"><div id="bar" class="h-full w-0 bg-[#003087]"></div></div>
<button id="go" class="btn btn-primary mt-4 w-full">Upload</button>
<label class="block text-sm text-[#687173] mt-3">Hapus otomatis
<select id="exp" class="input ml-2" style="width:auto;display:inline-block">
<option value="never">Tidak pernah</option>
<option value="1h">1 jam</option>
<option value="1d">1 hari</option>
<option value="7d">7 hari</option>
<option value="30d">30 hari</option>
</select></label>
<div id="result" class="mt-6 space-y-3"></div>
<p class="mt-6"><a href="/gallery.php" class="text-[#003087] font-medium underline">Lihat gallery</a></p>
</main>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
const drop = document.getElementById('drop'), input = document.getElementById('files'),
      prev = document.getElementById('preview'), bar = document.getElementById('bar'),
      go = document.getElementById('go'), result = document.getElementById('result');
const CSRF = <?= json_encode($token) ?>;
let files = [];
const toast = (t, ok) => Toastify({ text: t, duration: 3000, gravity: 'top', position: 'right',
  style: { background: ok ? 'linear-gradient(to right,#00b09b,#96c93d)' : 'linear-gradient(to right,#ff5f6d,#ffc371)' } }).showToast();
drop.onclick = () => input.click();
['dragover','dragenter'].forEach(e => drop.addEventListener(e, x => { x.preventDefault(); drop.classList.add('border-[#003087]'); }));
['dragleave','drop'].forEach(e => drop.addEventListener(e, x => { x.preventDefault(); drop.classList.remove('border-[#003087]'); }));
drop.addEventListener('drop', x => { add([...x.dataTransfer.files]); });
input.onchange = () => add([...input.files]);
function add(list) {
  files = [...files, ...list].slice(0, 5);
  prev.innerHTML = '';
  files.forEach(f => {
    if (!f.type.startsWith('image/')) { toast(f.name + ' bukan gambar', false); return; }
    if (f.size > 5242880) { toast(f.name + ' melebihi 5 MB', false); return; }
    const img = document.createElement('img');
    img.src = URL.createObjectURL(f); img.loading = 'lazy';
    img.className = 'w-full h-24 object-cover rounded-lg border border-[#CBD2D6]';
    prev.appendChild(img);
  });
}
go.onclick = () => {
  if (!files.length) { toast('Pilih file dulu', false); return; }
  const fd = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('expires_in', document.getElementById('exp').value);
  files.forEach(f => fd.append('images[]', f));
  const xhr = new XMLHttpRequest();
  xhr.open('POST', '/upload_handler.php');
  xhr.upload.onprogress = e => { if (e.lengthComputable) bar.style.width = (e.loaded / e.total * 100) + '%'; };
  xhr.onload = () => {
    let r; try { r = JSON.parse(xhr.responseText); } catch { toast('Response tidak valid', false); return; }
    if (!r.success) { toast(r.error || 'Upload gagal', false); return; }
    toast('Upload berhasil', true); result.innerHTML = '';
    r.data.forEach(d => {
      const div = document.createElement('div');
      div.className = 'bg-white border border-[#CBD2D6] rounded-xl p-4';
      div.innerHTML = '<img src="' + d.url + '" loading="lazy" class="w-full max-h-48 object-contain rounded-lg mb-3 border border-[#CBD2D6]">'
        + row('Direct', d.url) + row('BBCode', d.bbcode) + row('HTML', d.html);
      result.appendChild(div);
    });
    files = []; prev.innerHTML = ''; bar.style.width = '0';
  };
  xhr.onerror = () => toast('Jaringan gagal', false);
  xhr.send(fd);
};
function row(label, val) {
  const id = 'c' + Math.random().toString(36).slice(2);
  return '<label class="block text-sm text-[#687173] mb-2">' + label
    + '<span class="flex gap-2 mt-1"><input id="' + id + '" readonly value="' + val.replace(/"/g, '&quot;')
    + '" class="mono flex-1 h-12 px-3 bg-white border border-[#CBD2D6] rounded-lg text-xs">'
    + '<button onclick="navigator.clipboard.writeText(document.getElementById(\'' + id + '\').value);this.textContent=\'Copied!\';toast(\'Disalin\',true)" class="btn btn-primary text-xs">Copy</button></span></label>';
}
</script>
</body>
</html>
