<?php

// Directories containing the flag SVGs
$flagDirs = [
    'circle' => 'node_modules/circle-flags/flags',
    'square' => 'node_modules/square-flags/flags'
];

// Output CSS files
$outputFiles = [
    'circle' => 'css/circle-flags.css',
    'square' => 'css/square-flags.css'
];

// Function for minimal URL encoding (only #, <, > as specified; includes double quote replacement)
function minimalEncode($str) {
    // Replace all double quotes with single quotes
    $str = str_replace('"', "'", $str);

    $replacements = [
        '<' => '%3C',
        '>' => '%3E',
        '#' => '%23'
    ];
    return strtr($str, $replacements);
}

// Process each package (circle-flags and square-flags)
foreach ($flagDirs as $type => $flagsDir) {
    // Initialize CSS content
    $cssContent = "/* $type-flags CSS generated from SVG icons (minimal URL encoding) */\n\n";

    // Add the main .trf class with shared styles
    $cssContent .= ".trf {\n";
    $cssContent .= "  display: inline-block;\n";
    $cssContent .= "  width: 16px;\n"; // Default width, adjust as needed
    $cssContent .= "  height: 16px;\n"; // 32px for circle (1:1), 24px for square (4:3)
    $cssContent .= "  background-size: cover;\n";
    $cssContent .= "  background-position: center;\n";
    $cssContent .= "}\n\n";

    // Ensure the directory exists
    if (!is_dir($flagsDir)) {
        die("Error: Directory '$flagsDir' does not exist.\n");
    }

    // Get all SVG files recursively (including subdirectories)
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($flagsDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'svg') {
            $files[] = $file->getPathname();
        }
    }

    if (empty($files)) {
        die("Error: No SVG files found in '$flagsDir' or its subdirectories.\n");
    }

    // Deduplication: First pass - collect ALL classes per unique hash
    $hashToClasses = [];
    $processedHashes = []; // Track hashes that have been minified/encoded (to avoid re-processing)

    foreach ($files as $file) {
        // Get the relative path (e.g., 'il.svg' or 'language/he.svg')
        $relativePath = str_replace($flagsDir . '/', '', $file);
        // Create class name from relative path without .svg, replacing / with -
        $relativePathWithoutExt = str_replace('.svg', '', $relativePath);
        $className = str_replace('/', '-', $relativePathWithoutExt);

        // Read the SVG content
        $svgContent = file_get_contents($file);
        if ($svgContent === false) {
            echo "Warning: Could not read file '$file'. Skipping.\n";
            continue;
        }

        // Compute MD5 hash of raw SVG content
        $hash = hash('md5', $svgContent, true);
        $hashHex = bin2hex($hash);

        // Always add the class to the group for this hash
        if (!isset($hashToClasses[$hashHex])) {
            $hashToClasses[$hashHex] = [];
        }
        $hashToClasses[$hashHex][] = $className;

        // If this is the first time seeing this hash, mark it for processing (minify/encode later)
        if (!in_array($hashHex, $processedHashes)) {
            $processedHashes[] = $hashHex;
            echo "Added new class '$className' to group for hash: $hashHex.\n";
        } else {
            echo "Added duplicate class '$className' to existing group for hash: $hashHex.\n";
        }
    }

    // Second pass: Generate combined CSS rules for each unique hash
    foreach ($hashToClasses as $hashHex => $classes) {
        // Sort classes alphabetically for consistent output
        sort($classes);

        // Get the raw content for one file in this group (use first file as representative)
        // To find sample: iterate files, compute hash, match against hex2bin($hashHex)
        $sampleFile = null;
        foreach ($files as $file) {
            $testContent = file_get_contents($file);
            if (hash('md5', $testContent, true) === hex2bin($hashHex)) {
                $sampleFile = $file;
                break;
            }
        }
        if (!$sampleFile) {
            echo "Warning: Could not find sample file for hash $hashHex. Skipping.\n";
            continue;
        }

        $svgContent = file_get_contents($sampleFile);

        // Minify SVG content: Remove unneeded spaces and newlines
        $svgContent = preg_replace('/>\s+</', '><', $svgContent); // Remove whitespace between tags
        $svgContent = trim(str_replace(["\n", "\r", "\t"], '', $svgContent)); // Remove newlines, tabs, etc.

        // Minimal encode the SVG content
        $encodedSvg = minimalEncode($svgContent);
        $dataUrl = "url(\"data:image/svg+xml,$encodedSvg\")";

        // Create combined class rule (e.g., .trf-il, .trf-language-he)
        $combinedClasses = implode(', ', array_map(function($cls) { return ".trf-$cls"; }, $classes));
        echo "Generated combined rule for hash $hashHex: " . implode(', ', $classes) . "\n";

        $cssClass = "$combinedClasses {\n";
        $cssClass .= "  background-image: $dataUrl;\n";
        $cssClass .= "}\n\n";

        // Append to CSS content
        $cssContent .= $cssClass;
    }

    // Write the CSS content to the output file
    $result = file_put_contents($outputFiles[$type], $cssContent);

    if ($result === false) {
        die("Error: Could not write to '{$outputFiles[$type]}'.\n");
    }

    echo "Success: CSS file '{$outputFiles[$type]}' generated with " . count($files) . " flag files (" . count($hashToClasses) . " unique groups, minimal URL encoding, SVG minified, using .trf and .trf-xx classes).\n";
}

?>