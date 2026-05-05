#!/bin/bash
set -e

MOODLE_DIR="/var/www/html/public"
PLUGINS_DIR="/tmp/plugins"
REMUI_DIR="$PLUGINS_DIR/remui_bundle"

echo "=============================================="
echo "  Moodle Plugin Installation Script"
echo "  Target: $MOODLE_DIR"
echo "=============================================="
echo ""

# ========================================================
# STEP 1: Install theme_remui FIRST (mandatory per docs)
# ========================================================
echo "[1/14] Installing theme_remui..."
cd "$MOODLE_DIR/theme"
unzip -o "$REMUI_DIR/theme_remui.zip"
echo "  -> Installed to $MOODLE_DIR/theme/"
echo ""

# ========================================================
# STEP 2: Install RemUI Block - Rating/Review
# ========================================================
echo "[2/14] Installing block_edwiserratingreview..."
cd "$MOODLE_DIR/blocks"
unzip -o "$REMUI_DIR/Plugins/block_edwiserratingreview.zip"
echo "  -> Done"
echo ""

# ========================================================
# STEP 3: Install RemUI Course Format
# ========================================================
echo "[3/14] Installing format_remuiformat..."
cd "$MOODLE_DIR/course/format"
unzip -o "$REMUI_DIR/Plugins/format_remuiformat.zip"
echo "  -> Done"
echo ""

# ========================================================
# STEP 4: Install local_edwisersiteimporter
# ========================================================
echo "[4/14] Installing local_edwisersiteimporter..."
cd "$MOODLE_DIR/local"
unzip -o "$REMUI_DIR/Plugins/local_edwisersiteimporter.zip"
echo "  -> Done"
echo ""

# ========================================================
# STEP 5: Install block_edwiseradvancedblock (Page Builder)
# ========================================================
echo "[5/14] Installing block_edwiseradvancedblock..."
cd "$MOODLE_DIR/blocks"
unzip -o "$REMUI_DIR/Plugins/Page Builder Plugins/block_edwiseradvancedblock.zip"
echo "  -> Done"
echo ""

# ========================================================
# STEP 6: Install filter_edwiserpbf (Page Builder Filter)
# ========================================================
echo "[6/14] Installing filter_edwiserpbf..."
cd "$MOODLE_DIR/filter"
unzip -o "$REMUI_DIR/Plugins/Page Builder Plugins/filter_edwiserpbf.zip"
echo "  -> Done"
echo ""

# ========================================================
# STEP 7: Install local_edwiserpagebuilder
# ========================================================
echo "[7/14] Installing local_edwiserpagebuilder..."
cd "$MOODLE_DIR/local"
unzip -o "$REMUI_DIR/Plugins/Page Builder Plugins/local_edwiserpagebuilder.zip"
echo "  -> Done"
echo ""

# ========================================================
# STEP 8: Install local_sitesync (experimental)
# ========================================================
echo "[8/14] Installing local_sitesync..."
cd "$MOODLE_DIR/local"
unzip -o "$REMUI_DIR/Plugins/Site Sync Plugin (Experimental)/local_sitesync.zip"
echo "  -> Done"
echo ""

# ========================================================
# STEP 9: Install block_site_monitor (standalone)
# ========================================================
echo "[9/14] Installing block_site_monitor..."
cd "$MOODLE_DIR/blocks"
unzip -o "$PLUGINS_DIR/block_site_monitor.zip"
echo "  -> Done"
echo ""

# ========================================================
# STEP 10: Install edwiser_grader (standalone)
# ========================================================
echo "[10/14] Installing edwiser_grader..."
cd "$MOODLE_DIR/local"
unzip -o "$PLUGINS_DIR/edwiser_grader.zip"
echo "  -> Done"
echo ""

# ========================================================
# STEP 11: Install edwiservideoactivity (standalone - mod plugin)
# ========================================================
echo "[11/14] Installing edwiservideoactivity..."
cd "$MOODLE_DIR/mod"
unzip -o "$PLUGINS_DIR/edwiservideoactivity.zip"
echo "  -> Done"
echo ""

# ========================================================
# STEP 12: Install edwiservideoformat (standalone - course format)
# ========================================================
echo "[12/14] Installing edwiservideoformat..."
cd "$MOODLE_DIR/course/format"
unzip -o "$PLUGINS_DIR/edwiservideoformat.zip"
echo "  -> Done"
echo ""

# ========================================================
# STEP 13: Install Edwiser Reports (standalone - local)
# ========================================================
echo "[13/14] Installing edwiserreports..."
cd "$MOODLE_DIR/local"
unzip -o "$PLUGINS_DIR/moodle-local-edwiserreports-1.zip"
# Rename to expected directory name
if [ -d "moodle-local-edwiserreports" ] && [ ! -d "edwiserreports" ]; then
    mv moodle-local-edwiserreports edwiserreports
    echo "  -> Renamed moodle-local-edwiserreports -> edwiserreports"
fi
echo "  -> Done"
echo ""

# ========================================================
# STEP 14: Install Edwiser Forms Pro (contains 3 sub-zips)
# ========================================================
echo "[14/14] Installing Edwiser Forms Pro (3 sub-plugins)..."
cd "$PLUGINS_DIR"
unzip -o Edwiser-Forms-Pro.zip -d forms_pro

echo "  [14a] Installing local_edwiserform..."
cd "$MOODLE_DIR/local"
unzip -o "$PLUGINS_DIR/forms_pro/Edwiser-Forms-Pro/local_edwiserform.zip"
echo "  -> Done"

echo "  [14b] Installing mod_edwiserform..."
cd "$MOODLE_DIR/mod"
unzip -o "$PLUGINS_DIR/forms_pro/Edwiser-Forms-Pro/mod_edwiserform.zip"
echo "  -> Done"

echo "  [14c] Installing filter_edwiserformlink..."
cd "$MOODLE_DIR/filter"
unzip -o "$PLUGINS_DIR/forms_pro/Edwiser-Forms-Pro/filter_edwiserformlink.zip"
echo "  -> Done"
echo ""

# ========================================================
# Fix ownership
# ========================================================
echo "Fixing file ownership..."
chown -R www-data:www-data /var/www/html
echo "  -> Done"
echo ""

echo "=============================================="
echo "  All 14 plugins installed successfully!"
echo "=============================================="
echo ""
echo "=== Verification ==="
echo "--- Themes ---"
ls -d $MOODLE_DIR/theme/remui 2>/dev/null && echo "  OK" || echo "  MISSING"
echo "--- Blocks ---"
for d in edwiserratingreview edwiseradvancedblock sitemonitor; do
    ls -d $MOODLE_DIR/blocks/$d 2>/dev/null && echo "  OK: $d" || echo "  MISSING: $d"
done
echo "--- Local ---"
for d in edwisersiteimporter edwiserpagebuilder sitesync edwiser_grader edwiserreports edwiserform; do
    ls -d $MOODLE_DIR/local/$d 2>/dev/null && echo "  OK: $d" || echo "  MISSING: $d"
done
echo "--- Course Format ---"
for d in remuiformat edwiservideoformat; do
    ls -d $MOODLE_DIR/course/format/$d 2>/dev/null && echo "  OK: $d" || echo "  MISSING: $d"
done
echo "--- Filters ---"
for d in edwiserpbf edwiserformlink; do
    ls -d $MOODLE_DIR/filter/$d 2>/dev/null && echo "  OK: $d" || echo "  MISSING: $d"
done
echo "--- Mod (Activities) ---"
for d in edwiservideoactivity edwiserform; do
    ls -d $MOODLE_DIR/mod/$d 2>/dev/null && echo "  OK: $d" || echo "  MISSING: $d"
done
