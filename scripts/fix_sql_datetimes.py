#!/usr/bin/env python3
"""full_database.sql içindeki ISO 8601 tarihlerini MySQL formatına çevir."""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SQL = ROOT / "database" / "sql" / "full_database.sql"

ISO_IN_QUOTES = re.compile(r"'(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})\.\d+Z'")


def main() -> int:
  if not SQL.exists():
    print(f"Dosya yok: {SQL}", file=sys.stderr)
    return 1
  text = SQL.read_text(encoding="utf-8")
  fixed, n = ISO_IN_QUOTES.subn(r"'\1 \2'", text)
  SQL.write_text(fixed, encoding="utf-8")
  remaining = len(re.findall(r"\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}", fixed))
  print(f"Duzeltildi: {n} tarih | kalan ISO: {remaining}")
  return 0 if remaining == 0 else 1


if __name__ == "__main__":
  raise SystemExit(main())
