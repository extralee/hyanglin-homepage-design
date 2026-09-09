#!/usr/bin/env bash
# Browser-sync termination script

echo "Stopping any running browser-sync processes..."
pkill -f "browser-sync" && echo "Browser-sync stopped successfully." || echo "No active browser-sync process found."
