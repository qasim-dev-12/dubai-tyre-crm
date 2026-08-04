# Job Location Map — Plan

## Problem
Lead → Job conversion page (`resources/js/pages/leads/convert.vue`) embeds Google Maps JS API
with a hardcoded browser key. It's broken (key invalid/restricted/unbilled — no way to verify
without Google Cloud console access). Technician-side viewing only has a plain
`<a :href="job.location_url">View on Map</a>` link (works, but no in-page map).

## Decision
Drop Google Maps JS API entirely. Switch to **Leaflet + OpenStreetMap tiles** (free, no API key,
no billing account, can't go down for key reasons) for both picking and viewing. Geocoding search
uses OSM Nominatim (free, no key).

`jobs.latitude` / `jobs.longitude` / `jobs.location_url` columns already exist — just weren't
populated with lat/lng (only `location_url`). Store all three going forward.

## Implementation
1. `npm install leaflet`
2. `resources/js/components/LocationPicker.vue` — reusable: search box (Nominatim) + click-to-pin
   Leaflet map. Emits `{ lat, lng }`. Used in `leads/convert.vue`, replaces the broken embed.
2. `resources/js/components/LocationViewer.vue` — reusable: read-only Leaflet map with a marker,
   given `lat`/`lng`. Used in `sales/jobs/show.vue` (technician + admin, same component per repo
   convention) under the existing "View on Map" link.
3. Backend: `LeadsController::convert` — validate + persist `latitude`/`longitude` alongside
   `location_url`.
4. Marker icons served from unpkg CDN (avoids webpack asset-loader config for Leaflet's default
   icon images).

## Out of scope
- Manual job creation (`jobs/create.vue`) has no location field today and isn't part of this ask.
- No change to the existing `location_url` Google-Maps-link format (still generated from lat/lng,
  still opens fine in the Google Maps app/site with zero API key needed for that).
