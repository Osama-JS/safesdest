// public/js/mapbox-helper.js

let mapInstance = null;
let mapReadyCallbackQueue = [];
export const mapsConfig = {
  token: 'pk.eyJ1Ijoib3NhbWExOTk4IiwiYSI6ImNtYXc0Y2gwNTBiaXoyaXNkZmd3b2V6YzcifQ.bumnNtPfvx8ZXHpKbeJkPA',
  style: 'osama1998/cma8lcv6p00ha01s58rdb73zw',
  center: [46.6753, 24.7136]
};

export function initializeMap(containerId, center = [39.85791, 21.3891], zoom = 10, onReady) {
  if (mapInstance) {
    if (onReady) onReady(mapInstance);
    return mapInstance;
  }

  try {
    mapboxgl.accessToken = mapsConfig.token;

    mapInstance = new mapboxgl.Map({
      container: containerId,
      style: 'mapbox://styles/' + mapsConfig.style,
      center: mapsConfig.center,
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
