#!/bin/bash

# Script which runs outside Docker (on the host machine)
# Called by wp-env's afterStart lifecycle hook

# Print the script name.
echo $(basename "$0")

# This presumes the current working directory is the project root and the directory name matches the plugin slug.
PLUGIN_SLUG=$(basename $PWD)
echo "Building $PLUGIN_SLUG"

# Detect the operating system
OS_TYPE=$(uname)
echo "Detected OS: $OS_TYPE"

# Function to build the plugin for Unix-based systems (Linux and macOS)
build_plugin_unix() {
  # Run the internal scripts which configure the environments:
  echo "run npx wp-env run cli ../setup/initialize-internal.sh $PLUGIN_SLUG;"
  npx wp-env run cli ../setup/initialize-internal.sh $PLUGIN_SLUG;
  echo "run npx wp-env run tests-cli ../setup/initialize-internal.sh $PLUGIN_SLUG;"
  npx wp-env run tests-cli ../setup/initialize-internal.sh $PLUGIN_SLUG;
}

# Function to build the plugin for Windows
build_plugin_windows() {
  echo "run npx wp-env run cli setup/initialize-internal.sh $PLUGIN_SLUG;"
  npx wp-env run cli setup/initialize-internal.sh $PLUGIN_SLUG;
  echo "run npx wp-env run tests-cli setup/initialize-internal.sh $PLUGIN_SLUG;"
  npx wp-env run tests-cli setup/initialize-internal.sh $PLUGIN_SLUG;
}

# OS-specific actions
if [[ "$OS_TYPE" == "Linux" ]]; then
  echo "Running on Linux"
  build_plugin_unix
elif [[ "$OS_TYPE" == "Darwin" ]]; then
  echo "Running on macOS"
  build_plugin_unix
elif [[ "$OS_TYPE" == "MINGW"* || "$OS_TYPE" == "CYGWIN"* ]]; then
  echo "Running on Windows (Git Bash or Cygwin)"
  build_plugin_windows
else
  echo "Unsupported OS: $OS_TYPE"
  exit 1
fi
