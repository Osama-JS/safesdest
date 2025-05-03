// public/js/mapbox-helper.js

let mapInstance = null;
let mapReadyCallbackQueue = [];

export async function initializeMap(containerId, center = [39.85791, 21.3891], zoom = 10, onReady) {
  if (mapInstance) {
    if (onReady) onReady(mapInstance);
    return mapInstance;
  }

  try {
    const res = await fetch(baseUrl + 'mapbox-token');
    const data = await res.json();

    if (!data.token) throw new Error('فشل في جلب التوكن');

    mapboxgl.accessToken = data.token;

    mapInstance = new mapboxgl.Map({
      container: containerId,
      style: 'mapbox://styles/mapbox/streets-v11',
      center: center,
      zoom: zoom
    });

    mapInstance.on('load', () => {
      mapReadyCallbackQueue.forEach(cb => cb(mapInstance));
      mapReadyCallbackQueue = [];
      if (onReady) onReady(mapInstance);
    });
  } catch (error) {
    console.error('فشل تهيئة الخريطة:', error);
  }

  return mapInstance;
}

export function onMapReady(callback) {
  if (mapInstance) {
    callback(mapInstance);
  } else {
    mapReadyCallbackQueue.push(callback);
  }
}
