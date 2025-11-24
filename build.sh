#!/bin/bash

# Define the output file name
OUTPUT_FILE="panelalpha-for-whmcs.zip"

# Remove existing zip file if it exists
if [ -f "$OUTPUT_FILE" ]; then
    rm "$OUTPUT_FILE"
fi

# Check if zip command is available
if ! command -v zip &> /dev/null; then
    echo "zip command could not be found"
    exit 1
fi

# Create the zip file containing only the modules directory
echo "Creating package..."
zip -r "$OUTPUT_FILE" modules

if [ -f "$OUTPUT_FILE" ]; then
    echo "Package created successfully: $OUTPUT_FILE"
else
    echo "Failed to create package."
    exit 1
fi
