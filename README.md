# IGRS Game Data Scraper

A command-line tool to scrape game data from the [Indonesia Game Rating System (IGRS)](https://igrs.id) public API.

## Data Collected

| Field | Description |
|---|---|
| `id` | Game ID |
| `title` | Game title |
| `about` | Game description (About This Game) |
| `rating` | Age rating (e.g. 3+, 13+, 18+) |
| `descriptor` | Content descriptors (e.g. Violence, Online Interactions) |
| `platform` | Available platforms (e.g. Android, iOS, PC) |
| `release_year` | Year the game was released |
| `publisher` | Publisher name |

## Requirements

- Python 3.10 or higher
- No external libraries required (uses Python standard library only)

## Usage

```bash
python3 scraper_igrs.py --start <from_id> --end <to_id> [options]
```

### Arguments

| Argument | Required | Default | Description |
|---|---|---|---|
| `--start` | Yes | — | Starting game ID (inclusive) |
| `--end` | Yes | — | Ending game ID (inclusive) |
| `--output` | No | `igrs_games.csv` | Output CSV filename |
| `--delay` | No | `0.3` | Delay between requests in seconds |
| `--resume` | No | — | Resume mode: skip IDs already in the output file |

### Examples

```bash
# Scrape game IDs from 1 to 2000, save to default igrs_games.csv
python3 scraper_igrs.py --start 1 --end 2000

# Save to a custom file
python3 scraper_igrs.py --start 1 --end 2000 --output my_games.csv

# Slower scraping to reduce server load
python3 scraper_igrs.py --start 1 --end 2000 --delay 1.0

# Resume after an interruption without overwriting existing data
python3 scraper_igrs.py --start 1 --end 2000 --resume
```

## Output

The script produces a UTF-8 encoded CSV file. Example:

```
id,title,about,rating,descriptor,platform,release_year,publisher
1748,Mobile Legends: Bang Bang,"Mobile Legends: Bang Bang is one of the most popular...",18+,"Online Interactions, Violence, Drugs","Android, iOS",2016,Moonton Games
```

## Behavior

- **IDs with no data** (HTTP 404 or empty name) are automatically skipped.
- **Default mode** (`--no resume`): overwrites the output file on every run.
- **Resume mode** (`--resume`): reads existing IDs from the output file and appends only new records — safe to use after an interruption.

## Data Source

- API endpoint: `https://api.igrs.id/public/games/<id>`
- Game detail page: `https://igrs.id/game-detail/<id>`
