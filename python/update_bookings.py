# ALREADY APPLIED (2026-10-06) - kept as a record only. DO NOT RE-RUN.
# Wraps views/customer/bookings.php into two tab sections:
# #request (booking form) and #history (booking history table).
# Re-running it would add extra wrapper <div>s.
# Location: coh-pms/python/update_bookings.py
from pathlib import Path
import sys

PROJECT_ROOT = Path(__file__).resolve().parents[1]
TARGET_FILE = PROJECT_ROOT / "views" / "customer" / "bookings.php"

with open(TARGET_FILE, "r", encoding="utf-8") as f:
    content = f.read()

# Replace <section class="dashboard-main"> with <section class="dashboard-main">\n<div id="request" class="tab-section active">
# Then find <div class="card">\n                  <h2>My booking history</h2> and replace with </div>\n<div id="history" class="tab-section">\n<div class="card">...

new_content = content.replace(
    '<section class="dashboard-main">\n            <div style="margin-bottom: 32px;">',
    '<section class="dashboard-main">\n            <div id="request" class="tab-section active">\n            <div style="margin-bottom: 32px;">'
)

new_content = new_content.replace(
    '</aside>\n            </div>\n\n            <div class="card">\n                <h2>My booking history</h2>',
    '</aside>\n            </div>\n            </div>\n\n            <div id="history" class="tab-section">\n            <div class="card">\n                <h2>My booking history</h2>'
)

new_content = new_content.replace(
    '</table>\n            </div>\n        </section>',
    '</table>\n            </div>\n            </div>\n        </section>'
)

with open(TARGET_FILE, "w", encoding="utf-8") as f:
    f.write(new_content)
print("Done")

