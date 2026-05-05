<?php
/**
 * Rebuild quiz HTML using Moodle-safe elements only.
 * 
 * Moodle's Fragment API puts content through format_text() which strips:
 * - <style> tags
 * - <input> elements  
 * - <script> tags
 * - Custom data attributes may be altered
 * 
 * Solution: Use only standard HTML that survives Moodle sanitization:
 * - <details>/<summary> for reveal-on-click mechanics
 * - Standard divs with inline styles for layout
 * - No form inputs, no CSS tricks, no JavaScript
 */
define('CLI_SCRIPT', true);
require('/var/www/html/public/config.php');
global $DB;

echo "=== Rebuilding Quiz HTML (Moodle-Safe) ===\n\n";

// Get the quiz JSON to rebuild with proper data
$quiz_json_path = '/var/www/html/public/pluginfile.php'; // Not available here

// Instead, we'll parse the existing content to get the questions/options
$items = $DB->get_records('interactivevideo_items', ['type' => 'richtext'], 'timestamp ASC');
echo "Found " . count($items) . " quiz annotations\n\n";

foreach ($items as $item) {
    // Extract question number from title
    preg_match('/Q(\d+)/', $item->title, $qm);
    $qn = $qm[1] ?? '?';
    
    $content = $item->content;
    
    // Extract question text - look between <p> tags with font-weight
    if (preg_match('/<p[^>]*>(.+?)<\/p>/s', $content, $pm)) {
        $question = strip_tags($pm[1]);
    } else {
        echo "  ⚠️ Q{$qn}: Could not extract question text, skipping\n";
        continue;
    }
    
    // Extract all options - look for <label> or <div> with letter prefixes
    // The current format uses: <strong>A)</strong> A) text
    // or original format: <strong>A)</strong> text
    preg_match_all('/<(?:label|div)[^>]*><strong>([A-D])\)<\/strong>\s*(.*?)<\/(?:label|div)>/s', $content, $opt_matches);
    
    if (empty($opt_matches[2]) || count($opt_matches[2]) < 2) {
        // Try alternate format
        preg_match_all('/<strong>([A-D])\)<\/strong>\s*(.*?)(?:<\/div>|<\/label>)/s', $content, $opt_matches);
    }
    
    $options = [];
    foreach ($opt_matches[2] as $opt_text) {
        $clean = trim(strip_tags($opt_text));
        // Remove duplicate letter prefix (e.g., "A) Patterns are..." -> "Patterns are...")
        $clean = preg_replace('/^[A-D]\)\s*/', '', $clean);
        // Remove trailing emoji/check marks
        $clean = rtrim($clean, " ✅❌");
        $options[] = $clean;
    }
    
    // Determine correct answer index
    $correct_idx = 0;
    if (preg_match('/data-result="correct".*?<strong>([A-D])\)/', $content, $cm)) {
        $correct_idx = ord($cm[1]) - ord('A');
    } elseif (preg_match('/background:#e8f5e9.*?<strong>([A-D])\)/', $content, $cm)) {
        $correct_idx = ord($cm[1]) - ord('A');
    }
    
    // Extract explanation
    $explanation = 'Think about what you learned!';
    if (preg_match('/Explanation:<\/strong>\s*(.*?)(?:<\/div>)/s', $content, $em)) {
        $explanation = trim(strip_tags($em[1]));
    } elseif (preg_match('/💡.*?<\/strong>\s*(.*?)(?:<\/div>)/s', $content, $em)) {
        $explanation = trim(strip_tags($em[1]));
    }
    
    echo "  Q{$qn}: {$question}\n";
    echo "  Options (" . count($options) . "): ";
    foreach ($options as $j => $o) {
        echo chr(65+$j) . ") " . substr($o, 0, 30) . ($j === $correct_idx ? " [CORRECT]" : "") . "  ";
    }
    echo "\n";
    
    if (count($options) < 2) {
        echo "  ⚠️ Too few options, skipping\n\n";
        continue;
    }
    
    // Build Moodle-safe interactive HTML using <details>/<summary>
    $correct_letter = chr(65 + $correct_idx);
    $correct_text = htmlspecialchars($options[$correct_idx], ENT_QUOTES, 'UTF-8');
    $question_safe = htmlspecialchars($question, ENT_QUOTES, 'UTF-8');
    $explain_safe = htmlspecialchars($explanation, ENT_QUOTES, 'UTF-8');
    
    // Each option is a <details> element - clicking reveals correct/incorrect feedback
    $opts_html = '';
    foreach ($options as $j => $opt) {
        $letter = chr(65 + $j);
        $opt_safe = htmlspecialchars($opt, ENT_QUOTES, 'UTF-8');
        
        if ($j === $correct_idx) {
            // Correct answer
            $opts_html .= "<details style=\"margin:8px 0;\">"
                       . "<summary style=\"padding:12px 16px;border-radius:10px;background:#f5f5f5;border:2px solid #e0e0e0;"
                       . "cursor:pointer;font-size:1.05em;list-style:none;\">"
                       . "<strong>{$letter})</strong> {$opt_safe}</summary>"
                       . "<div style=\"padding:10px 16px;margin:4px 0;border-radius:0 0 10px 10px;background:#e8f5e9;border:2px solid #4caf50;border-top:none;\">"
                       . "✅ <strong>Correct!</strong> Great job!</div></details>";
        } else {
            // Wrong answer
            $opts_html .= "<details style=\"margin:8px 0;\">"
                       . "<summary style=\"padding:12px 16px;border-radius:10px;background:#f5f5f5;border:2px solid #e0e0e0;"
                       . "cursor:pointer;font-size:1.05em;list-style:none;\">"
                       . "<strong>{$letter})</strong> {$opt_safe}</summary>"
                       . "<div style=\"padding:10px 16px;margin:4px 0;border-radius:0 0 10px 10px;background:#ffebee;border:2px solid #ef9a9a;border-top:none;\">"
                       . "❌ Not quite. The correct answer is <strong>{$correct_letter}) {$correct_text}</strong></div></details>";
        }
    }
    
    // Explanation in a details block at the bottom
    $html = "<div style=\"max-width:560px;margin:0 auto;font-family:sans-serif;\">"
          . "<h3 style=\"color:#1565c0;margin-bottom:4px;\">📝 Check Your Understanding (Q{$qn})</h3>"
          . "<p style=\"font-size:1.1em;font-weight:600;margin-bottom:12px;\">{$question_safe}</p>"
          . "<p style=\"color:#666;font-size:0.9em;margin-bottom:8px;\">👆 Click an answer to check if you're right!</p>"
          . $opts_html
          . "<details style=\"margin-top:16px;\">"
          . "<summary style=\"padding:12px 16px;background:#e3f2fd;border-left:4px solid #1565c0;border-radius:6px;"
          . "cursor:pointer;font-weight:600;\">💡 Show Explanation</summary>"
          . "<div style=\"padding:12px 16px;background:#e3f2fd;border-left:4px solid #1565c0;border-radius:0 0 6px 6px;border-top:1px solid #bbdefb;\">"
          . "{$explain_safe}</div></details>"
          . "</div>";
    
    // Update the database
    $update = new stdClass();
    $update->id = $item->id;
    $update->content = $html;
    $update->timemodified = time();
    $DB->update_record('interactivevideo_items', $update);
    
    echo "  ✅ Updated with interactive <details> HTML\n\n";
}

purge_all_caches();
echo "✅ All caches purged\n";
echo "\n🎯 Hard-refresh (Ctrl+Shift+R) and seek to a quiz timestamp!\n";
echo "   Students click an option to reveal if it's correct or wrong.\n";
echo "   The explanation is hidden behind a 'Show Explanation' toggle.\n";
