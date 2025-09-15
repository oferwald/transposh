<?php

// Directory containing the 4x3 flag SVGs
$flagsDir = 'node_modules/flag-icons/flags/4x3';

// Output CSS file
$outputFile = 'css/flags.css';

// Mapping of SVG files to flag values (including non-existing flags)
$flagMapping = [
    'fr' => ['fr', 'fr-20r'],               // Corsican uses French flag
    'la' => ['la', 'hmong'],                // Hmong, Hmong Daw use Laos flag
    'nl' => ['nl', 'nl-fr'],                   // Frisian uses Netherlands flag
    'ng' => ['ng', 'hausa', 'yorubaland'],  // Hausa, Yoruba use Nigeria flag
    'id' => ['id', 'id-jt', 'id-jb'],       // Javanese, Sundanese use Indonesia flag
    'mx' => ['mx', 'otomi'],                // Otomi uses Mexico flag
    'pk' => ['pk', 'pk-sd', 'ur'],          // Sindhi, Urdu use Pakistan flag
    'in' => ['in', 'in-gj', 'in-ka', 'in-tn', 'in-tg', 'language-mr','malayali'], // Gujarati, Kannada, Tamil, Telugu, Marathi, Malayalam use India flag
    'ru' => ['ru', 'ru-ba', 'ru-ta', 'ru-ud'], // Bashkir, Tatar, Udmurt use Russian flag
    'iq' => ['iq', 'iq-kr'],                // Kurdish (Kurmanji) uses Iraq flag (primary region)
    'us' => ['us', 'us-hi'],                // Hawaiian uses US flag (Hawaii is a US state)
    'eu' => ['eu', 'language-yi'],                // Hawaiian uses US flag (Hawaii is a US state)
    'un' => ['language-eo','un'],                // Esperanto uses EU flag (common association for Esperanto)
];

// Initialize CSS content
$cssContent = "/* Flags CSS generated from SVG icons (minimal URL encoding) */\n\n";

// Add the main .trf class with shared styles
$cssContent .= ".trf {\n";
$cssContent .= "  display: inline-block;\n";
$cssContent .= "  width: 16px;\n"; // Default width, adjust as needed
$cssContent .= "  height: 11px;\n"; // Default height for 4x3 aspect ratio
$cssContent .= "  background-size: cover;\n";
$cssContent .= "  background-position: center;\n";
$cssContent .= "}\n\n";

// Ensure the directory exists
if (!is_dir($flagsDir)) {
    die("Error: Directory '$flagsDir' does not exist.\n");
}

// Get all SVG files in the 4x3 directory
$files = glob($flagsDir . '/*.svg');

if (empty($files)) {
    die("Error: No SVG files found in '$flagsDir'.\n");
}

// Function for minimal URL encoding (only #, <, > as specified)
function minimalEncode($str) {
    $replacements = [
        '<' => '%3C',
        '>' => '%3E',
        '#' => '%23',
        '"' => '\''
    ];
    return strtr($str, $replacements);
}

// Process each SVG file
foreach ($files as $file) {
    // Get the filename without extension (e.g., 'us' from 'us.svg')
    $filename = basename($file, '.svg');

    // Get the flag values for this SVG file from the mapping, or use the filename without .svg as default
    $flagValues = $flagMapping[$filename] ?? [$filename];

    // Read the SVG content
    $svgContent = file_get_contents($file);
    if ($svgContent === false) {
        echo "Warning: Could not read file '$file'. Skipping.\n";
        continue;
    }

    // Pre-process SVG to use single quotes (as in example) for common attributes
    $svgContent = str_replace('xmlns="', "xmlns='", $svgContent);
    $svgContent = str_replace('fill="#', "fill='#", $svgContent);
    // Add more str_replace if needed, e.g., for viewBox: str_replace('viewBox="', "viewBox='", $svgContent);

    // Minify SVG content only: Remove unneeded spaces and newlines from SVG
    $svgContent = preg_replace('/>\s+</', '><', $svgContent); // Remove whitespace between tags
    $svgContent = trim(str_replace(["\n", "\r", "\t"], '', $svgContent)); // Remove newlines, tabs, etc.

    // Minimal encode the SVG content
    $encodedSvg = minimalEncode($svgContent);
    $dataUrl = "url(\"data:image/svg+xml,$encodedSvg\")";

    // Create the flag-specific sub-class with multiple selectors (e.g., .trf-fr, .trf-fr-20r)
    $sanitizedFlags = array_map(function($flag) {
        return '.trf-' . str_replace(['-', ':'], '-', $flag);
    }, $flagValues);
    $cssClass = implode(', ', $sanitizedFlags) . " {\n";
    $cssClass .= "  background-image: $dataUrl;\n";
    $cssClass .= "}\n\n";

    // Append to CSS content
    $cssContent .= $cssClass;
}

// Write the CSS content to the output file
$result = file_put_contents($outputFile, $cssContent);

if ($result === false) {
    die("Error: Could not write to '$outputFile'.\n");
}

echo "Success: CSS file '$outputFile' generated with " . count($files) . " flag icons (minimal URL encoding, SVG minified, using .trf and .trf-xx classes).\n";

?>