#!/usr/bin/env python3
"""
This script downloads the PDFs listed in cbn_circulars.json
"""

import os
import json
import requests
import time

# Important names we use
JSON_FILE = 'cbn_circulars.json'
PDF_DIR = 'pdf_downloads/'

# Load the list of circulars
def load_circulars():
    if not os.path.exists(JSON_FILE):
        exit("The circulars file is missing. Please run extractor.py first.")

    with open(JSON_FILE, 'r', encoding='utf-8') as f:
        try:
            data = json.load(f)
        except json.JSONDecodeError as e:
            exit(f"The circulars file is broken: {str(e)}")

    return data

# Download a PDF file from the web
def download_pdf(url, save_path):
    headers = {
        'Accept': 'application/pdf',
        'Referer': 'https://www.cbn.gov.ng/Documents/circulars.html'
    }

    try:
        response = requests.get(url, headers=headers, stream=True, verify=False, timeout=300)
    except requests.RequestException as e:
        print(f"Request failed: {str(e)}")
        return False

    if response.status_code != 200 or not response.content:
        return False  # Did not download properly

    with open(save_path, 'wb') as f:
        f.write(response.content)

    # Check if file was saved properly
    return os.path.exists(save_path) and os.path.getsize(save_path) > 1000

# Save updated info back into JSON file
def update_json(data):
    with open(JSON_FILE, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=4, ensure_ascii=False)

# --- Start here ---
if __name__ == '__main__':
    print("Starting to download PDFs...")

    circulars = load_circulars()
    total = len(circulars)
    success = 0
    fail = 0

    for index, circular in enumerate(circulars):
        path = circular.get('local_path', '')
        if not path:
            continue

        print(f"[{index+1}/{total}] Downloading: {circular['file_name']}")

        # Skip if already downloaded
        if os.path.exists(path) and os.path.getsize(path) > 1000:
            print("Already exists, skipping")
            circular['downloaded'] = True
            continue

        if download_pdf(circular['pdf_url'], path):
            circular['downloaded'] = True
            success += 1
        else:
            print(f"Failed to download: {circular['pdf_url']}")
            circular['downloaded'] = False
            fail += 1

        # Sleep half a second to be nice to the server
        time.sleep(0.5)

    # Save updated info
    update_json(circulars)

    print("\nSummary:")
    print(f"Downloaded: {success} files")
    print(f"Failed: {fail} files")