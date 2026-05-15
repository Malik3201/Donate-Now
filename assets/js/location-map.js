(function () {
  'use strict';

  function initMap(el) {
    if (!el || el.dataset.dnMapInit === '1') {
      return;
    }
    if (typeof L === 'undefined') {
      return;
    }

    el.dataset.dnMapInit = '1';

    var lat = parseFloat(el.dataset.lat || '31.5204');
    var lng = parseFloat(el.dataset.lng || '74.3587');
    var readonly = el.dataset.readonly === '1';
    var hasCoords = el.dataset.hasCoords === '1';

    var map = L.map(el, {
      scrollWheelZoom: !readonly,
      dragging: !readonly,
      doubleClickZoom: !readonly,
      touchZoom: !readonly,
    }).setView([lat, lng], hasCoords ? 14 : 11);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    var marker = null;

    function setCoords(newLat, newLng, zoom) {
      lat = newLat;
      lng = newLng;
      if (marker) {
        marker.setLatLng([lat, lng]);
      } else {
        marker = L.marker([lat, lng], { draggable: !readonly }).addTo(map);
        if (!readonly) {
          marker.on('dragend', function () {
            var p = marker.getLatLng();
            updateHidden(p.lat, p.lng);
          });
        }
      }
      map.setView([lat, lng], zoom || map.getZoom());
      updateHidden(lat, lng);
    }

    function updateHidden(newLat, newLng) {
      var picker = el.closest('.dn-location-map-picker');
      if (!picker) {
        return;
      }
      var latInput = picker.querySelector('input[type="hidden"][name$="latitude"]');
      var lngInput = picker.querySelector('input[type="hidden"][name$="longitude"]');
      var coordsEl = picker.querySelector('.dn-location-map-coords');
      if (latInput) {
        latInput.value = newLat.toFixed(6);
      }
      if (lngInput) {
        lngInput.value = newLng.toFixed(6);
      }
      if (coordsEl) {
        coordsEl.textContent = 'Pin: ' + newLat.toFixed(5) + ', ' + newLng.toFixed(5);
      }
      el.dataset.hasCoords = '1';
    }

    if (readonly) {
      L.marker([lat, lng]).addTo(map);
    } else {
      if (hasCoords) {
        setCoords(lat, lng, 14);
      }
      map.on('click', function (e) {
        setCoords(e.latlng.lat, e.latlng.lng, 15);
      });

      var picker = el.closest('.dn-location-map-picker');
      if (picker) {
        var searchInput = picker.querySelector('.dn-location-map-search__input');
        var searchBtn = picker.querySelector('.dn-location-map-search__btn');

        function geocode(query) {
          if (!query) {
            return;
          }
          var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(query);
          fetch(url, { headers: { Accept: 'application/json' } })
            .then(function (r) {
              return r.json();
            })
            .then(function (data) {
              if (data && data[0]) {
                var la = parseFloat(data[0].lat);
                var ln = parseFloat(data[0].lon);
                setCoords(la, ln, 15);
              }
            })
            .catch(function () {});
        }

        if (searchBtn && searchInput) {
          searchBtn.addEventListener('click', function () {
            geocode(searchInput.value.trim());
          });
          searchInput.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter') {
              ev.preventDefault();
              geocode(searchInput.value.trim());
            }
          });
        }
      }
    }

    setTimeout(function () {
      map.invalidateSize();
    }, 200);
  }

  function boot() {
    document.querySelectorAll('.dn-location-map').forEach(initMap);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
