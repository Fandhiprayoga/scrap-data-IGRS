"""
IGRS Game Data Scraper
Sumber: https://api.igrs.id/public/games/<id>

Cara pakai:
    python3 scraper_igrs.py --start 1 --end 2000
    python3 scraper_igrs.py --start 1 --end 2000 --output hasil.csv
    python3 scraper_igrs.py --start 1 --end 2000 --delay 0.5
    python3 scraper_igrs.py --start 1 --end 2000 --resume   # lanjut, tidak menimpa data lama
"""

import argparse
import csv
import json
import os
import time
import sys
from urllib.request import urlopen, Request
from urllib.error import HTTPError, URLError

BASE_URL = "https://api.igrs.id/public/games/{}"


def fetch_game(game_id: int) -> dict | None:
    url = BASE_URL.format(game_id)
    req = Request(url, headers={"User-Agent": "Mozilla/5.0", "Accept": "application/json"})
    try:
        with urlopen(req, timeout=10) as resp:
            if resp.status == 200:
                data = json.loads(resp.read().decode("utf-8"))
                # Jika nama kosong / data tidak ada, anggap skip
                if not data.get("name"):
                    return None
                return data
    except HTTPError as e:
        if e.code == 404:
            return None  # Data tidak ada, skip
        print(f"  [HTTP Error {e.code}] ID {game_id}", file=sys.stderr)
    except URLError as e:
        print(f"  [URL Error] ID {game_id}: {e.reason}", file=sys.stderr)
    except Exception as e:
        print(f"  [Error] ID {game_id}: {e}", file=sys.stderr)
    return None


def parse_game(data: dict) -> dict:
    ratings = data.get("ratings") or []
    rating_names = ", ".join(r.get("name", "") for r in ratings)

    descriptors = data.get("descriptors") or []
    descriptor_names = ", ".join(d.get("nameEn", "") for d in descriptors)

    platforms = data.get("platformsName") or []
    platform_names = ", ".join(platforms)

    return {
        "id": data.get("id", ""),
        "title": data.get("name", ""),
        "about": (data.get("description") or "").replace("\n", " ").strip(),
        "rating": rating_names,
        "descriptor": descriptor_names,
        "platform": platform_names,
        "release_year": data.get("releaseYear", ""),
        "publisher": data.get("publisherName", ""),
    }


FIELDNAMES = ["id", "title", "about", "rating", "descriptor", "platform", "release_year", "publisher"]


def main():
    parser = argparse.ArgumentParser(description="Scraper data game dari IGRS API")
    parser.add_argument("--start", type=int, required=True, help="ID awal (inklusif)")
    parser.add_argument("--end",   type=int, required=True, help="ID akhir (inklusif)")
    parser.add_argument("--output", default="igrs_games.csv", help="Nama file output CSV (default: igrs_games.csv)")
    parser.add_argument("--delay", type=float, default=0.3, help="Jeda antar request dalam detik (default: 0.3)")
    parser.add_argument("--resume", action="store_true", help="Lanjutkan dari titik terakhir, skip ID yang sudah ada di file")
    args = parser.parse_args()

    if args.start > args.end:
        print("Error: --start harus lebih kecil atau sama dengan --end")
        sys.exit(1)

    # Baca ID yang sudah ada di file jika mode resume
    existing_ids: set[int] = set()
    if args.resume and os.path.exists(args.output):
        with open(args.output, newline="", encoding="utf-8") as f:
            reader = csv.DictReader(f)
            for row in reader:
                try:
                    existing_ids.add(int(row["id"]))
                except (KeyError, ValueError):
                    pass
        print(f"Mode resume: {len(existing_ids)} ID sudah ada di '{args.output}', akan di-skip.")

    total = args.end - args.start + 1
    found = 0
    skipped = 0

    print(f"Scraping ID {args.start} s/d {args.end} → {args.output}")
    print("-" * 50)

    # Mode resume: append tanpa tulis header lagi; mode normal: overwrite
    file_mode = "a" if args.resume and os.path.exists(args.output) else "w"
    with open(args.output, file_mode, newline="", encoding="utf-8") as csvfile:
        writer = csv.DictWriter(csvfile, fieldnames=FIELDNAMES)
        if file_mode == "w":
            writer.writeheader()

        for i, game_id in enumerate(range(args.start, args.end + 1), start=1):
            print(f"[{i}/{total}] ID {game_id} ... ", end="", flush=True)

            if game_id in existing_ids:
                print("sudah ada (skip)")
                skipped += 1
                continue

            data = fetch_game(game_id)
            if data is None:
                print("skip")
                skipped += 1
            else:
                row = parse_game(data)
                writer.writerow(row)
                found += 1
                print(f"OK  → {row['title']}")

            if i < total:
                time.sleep(args.delay)

    print("-" * 50)
    print(f"Selesai. Ditemukan: {found} | Dilewati: {skipped} | Total dicek: {total}")
    print(f"File disimpan: {args.output}")


if __name__ == "__main__":
    main()
