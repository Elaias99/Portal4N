self.addEventListener('install', function(e) {
  console.log('Service Worker instalado');
});

self.addEventListener('fetch', function(event) {
  // Por ahora solo deja pasar todo
  return;
});
