#!/usr/bin/env python3
"""
This script fetches a list of circulars from the CBN website
and saves it into a JSON file.
"""

import os
import json
import requests

# Make sure the script has enough time and memory
# (Not needed in Python unless doing super large operations)

# Some important values we will use
API_URL = 'https://www.cbn.gov.ng/api/GetAllCirculars'
JSON_FILE = 'cbn_circulars.json'
PDF_DIR = 'pdf_downloads/'
BASE_URL = 'https://www.cbn.gov.ng'

# This function talks to the CBN website and gets the circulars
def fetch_circulars():
    headers = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }

    response = requests.get(API_URL, headers=headers, verify=False)
    
    if response.status_code != 200:
        exit(f"Could not get data from the website (Error code {response.status_code})")

    try:
        data = response.json()
    except json.JSONDecodeError as e:
        exit(f"The data from the website is not good JSON: {str(e)}")

    circulars = []

    for item in data:
        pdf_link = item.get('link', '')
        if not pdf_link:
            continue  # Skip if no link

        # Make full link if needed
        if pdf_link.startswith('http'):
            pdf_url = pdf_link
        else:
            pdf_url = BASE_URL + '/' + pdf_link.lstrip('/')

        # Clean up the filename
        file_name = os.path.basename(pdf_url).replace(' ', '_')

        circulars.append({
            'title': item.get('title', '').strip(),
            'date': item.get('documentDate', '').strip(),
            'ref_no': item.get('refNo', '').strip(),
            'pdf_url': pdf_url,
            'file_name': file_name,
            'local_path': os.path.join(PDF_DIR, file_name),
        })

    return circulars

# This function saves the list into a JSON file
def save_json(data):
    with open(JSON_FILE, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=4, ensure_ascii=False)

# This makes sure there is a folder for PDFs
def ensure_download_folder():
    if not os.path.isdir(PDF_DIR):
        os.makedirs(PDF_DIR, exist_ok=True)

# --- Start here ---
if __name__ == '__main__':
    print("Getting the circulars list...")

    circulars = fetch_circulars()
    if not circulars:
        exit("No circulars found!")

    ensure_download_folder()
    save_json(circulars)

    print(f"Saved {len(circulars)} circulars to {JSON_FILE}")