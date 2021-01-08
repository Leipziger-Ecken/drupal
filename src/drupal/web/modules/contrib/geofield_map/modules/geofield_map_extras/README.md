### **Geofield Map Extras**

This module provides additional features not enabled by default when you install
the main **Geofield Map** module.

#### **Geofield Map Static Formatter**

A "Geofield Google Map (static)" formatter is available, that renders a
Google Map, accordingly to the 
[Google Maps Static API](https://developers.google.com/maps/documentation/maps-static/dev-guide)
(only Points supported, and not Geometries such as Polylines, Polygons, etc.).
The use of the static maps API is significantly cheaper than the dynamic map.
The drawback is that the map is displayed as a static image (i.e. without any
controls, zoom, pan, etc).
For more information on pricing you can consult the
[Google Maps Static Pricing documentation](https://developers.google.com/maps/billing/understanding-cost-of-use#static-maps).

#### **Geofield Map Embed Formatter**

A "Geofield Google Map (embed)" formatter is available, that renders a
Google Map, accordingly to the 
[Google Maps Embed API](https://developers.google.com/maps/documentation/embed/guide)
(only Points supported, and not Geometries such as Polylines, Polygons, etc.).
The use of the static maps API is significantly cheaper than the dynamic map,
even free to use for the most basic use cases (map in the "places" mode).
For more information on pricing you can consult the
[Google Maps Embed Pricing documentation](https://developers.google.com/maps/billing/understanding-cost-of-use#embed).

This formatter renders the map inside an `<iframe>` where the map gets rendered.
No Javascript whatsoever is required for this option to work.
