#!/bin/sh
set -eu

root=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
for browser in chromium firefox; do
  output="$root/dist/$browser"
  mkdir -p "$output"
  cp "$root"/popup.html "$root"/popup.css "$root"/popup.js \
    "$root"/worker.js "$root"/capture-main.js "$root"/capture-bridge.js "$root"/capture-ui.js \
    "$root"/recorder.html "$root"/recorder.css "$root"/recorder.js "$output"/
  if [ "$browser" = firefox ]; then
    cp "$root/manifest.firefox.json" "$output/manifest.json"
  else
    cp "$root/manifest.json" "$output/manifest.json"
  fi
done
printf 'Packages created: %s/dist/chromium and %s/dist/firefox\n' "$root" "$root"
