# ALREADY APPLIED (2026-10-07) - kept as a record only. DO NOT RE-RUN.
# Adds active-tab memory (sessionStorage) to the tab switcher in
# views/layouts/sidebar.php so the current tab survives page reloads.
# Re-running it is harmless (the target text no longer matches) but unnecessary.
# Location: coh-pms/python/update_sidebar_js.py
from pathlib import Path
import sys

PROJECT_ROOT = Path(__file__).resolve().parents[1]
TARGET_FILE = PROJECT_ROOT / "views" / "layouts" / "sidebar.php"

with open(TARGET_FILE, "r", encoding="utf-8") as f:
    content = f.read()

old_js = """    function switchTab(hash) {
        if (!hash) return;

        const targetId = hash.replace('#', '');"""

new_js = """    function switchTab(hash) {
        if (!hash) return;
        
        const currentPath = window.location.search.split('#')[0] || 'default';
        sessionStorage.setItem('activeTab_' + currentPath, hash);

        const targetId = hash.replace('#', '');"""

content = content.replace(old_js, new_js)

old_init = """    if (document.querySelector('.tab-section')) {
        switchTab(window.location.hash || '#list');"""

new_init = """    if (document.querySelector('.tab-section')) {
        const currentPath = window.location.search.split('#')[0] || 'default';
        const savedTab = sessionStorage.getItem('activeTab_' + currentPath);
        switchTab(window.location.hash || savedTab || '#list');"""

content = content.replace(old_init, new_init)

with open(TARGET_FILE, "w", encoding="utf-8") as f:
    f.write(content)
print("Done")

