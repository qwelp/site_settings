<?php
/**
 * Test script to verify individual UF_FULL_WIDTH functionality
 * This script simulates the corrected radio card rendering logic
 */

// Mock the required functions for testing
if (!function_exists('htmlspecialcharsbx')) {
    function htmlspecialcharsbx($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

// Test data matching the issue description
$sectionData = [
    'ID' => 2276,
    'PARENT_ID' => 2273,
    'id' => 'header-section',
    'title' => 'Шапки раздел',
    'UF_FULL_WIDTH' => 0,  // Parent has UF_FULL_WIDTH = 0
    'SUBSECTIONS' => [
        [
            'ID' => 2277,
            'PARENT_ID' => 2276,
            'id' => 'header-1',
            'title' => 'Шапка №1',
            'PICTURE' => 1388,
            'DEPTH' => 4,
            'UF_FULL_WIDTH' => 1  // Subsection has UF_FULL_WIDTH = 1
        ],
        [
            'ID' => 2278,
            'PARENT_ID' => 2276,
            'id' => 'header-2',
            'title' => 'Шапка №2',
            'PICTURE' => 1389,
            'DEPTH' => 4,
            'UF_FULL_WIDTH' => 1  // Subsection has UF_FULL_WIDTH = 1
        ]
    ]
];

// Function to simulate the CORRECTED logic
function testCorrectedLogic($section) {
    $isRadioCardGroup = !empty($section['SUBSECTIONS']) && (int)reset($section['SUBSECTIONS'])['DEPTH'] === 4;
    
    if ($isRadioCardGroup) {
        $groupCode = $section['id'];
        
        echo "Section: " . $section['title'] . "\n";
        echo "Parent UF_FULL_WIDTH: " . ($section['UF_FULL_WIDTH'] ?? 0) . "\n";
        echo "Group Code: " . $groupCode . "\n\n";
        
        foreach ($section['SUBSECTIONS'] as $subSection) {
            // NEW CORRECTED LOGIC: Check individual subsection's UF_FULL_WIDTH
            $isFullWidth = !empty($subSection['UF_FULL_WIDTH']) && (int)$subSection['UF_FULL_WIDTH'] === 1;
            
            echo "  Radio Card: " . $subSection['title'] . "\n";
            echo "  Individual UF_FULL_WIDTH: " . ($subSection['UF_FULL_WIDTH'] ?? 0) . "\n";
            echo "  Is Full Width: " . ($isFullWidth ? 'YES' : 'NO') . "\n";
            
            // Simulate CSS class generation
            $radioCardClasses = ['radio-card'];
            if ($isFullWidth) {
                $radioCardClasses[] = 'radio-card--full-width';
            }
            
            echo "  CSS Classes: " . implode(' ', $radioCardClasses) . "\n";
            echo "  Expected HTML: <div class=\"" . implode(' ', $radioCardClasses) . "\">\n\n";
        }
    }
}

// Function to simulate the OLD (incorrect) logic for comparison
function testOldLogic($section) {
    $isRadioCardGroup = !empty($section['SUBSECTIONS']) && (int)reset($section['SUBSECTIONS'])['DEPTH'] === 4;
    
    if ($isRadioCardGroup) {
        $groupCode = $section['id'];
        // OLD LOGIC: Check parent section's UF_FULL_WIDTH
        $isFullWidth = !empty($section['UF_FULL_WIDTH']) && (int)$section['UF_FULL_WIDTH'] === 1;
        
        echo "Section: " . $section['title'] . " (OLD LOGIC)\n";
        echo "Parent UF_FULL_WIDTH: " . ($section['UF_FULL_WIDTH'] ?? 0) . "\n";
        echo "Applied to all subsections: " . ($isFullWidth ? 'YES' : 'NO') . "\n\n";
        
        foreach ($section['SUBSECTIONS'] as $subSection) {
            echo "  Radio Card: " . $subSection['title'] . "\n";
            echo "  Individual UF_FULL_WIDTH: " . ($subSection['UF_FULL_WIDTH'] ?? 0) . " (ignored)\n";
            
            // Simulate CSS class generation with old logic
            $radioCardClasses = ['radio-card'];
            if ($isFullWidth) {
                $radioCardClasses[] = 'radio-card--full-width';
            }
            
            echo "  CSS Classes: " . implode(' ', $radioCardClasses) . "\n";
            echo "  HTML: <div class=\"" . implode(' ', $radioCardClasses) . "\">\n\n";
        }
    }
}

echo "Testing individual UF_FULL_WIDTH functionality:\n";
echo "==============================================\n\n";

echo "=== NEW CORRECTED LOGIC ===\n";
testCorrectedLogic($sectionData);

echo "=== OLD INCORRECT LOGIC (for comparison) ===\n";
testOldLogic($sectionData);

echo "Summary:\n";
echo "- NEW LOGIC: Each radio card gets full-width class based on its own UF_FULL_WIDTH value\n";
echo "- OLD LOGIC: All radio cards inherit the parent's UF_FULL_WIDTH value (incorrect)\n";
echo "\nWith the fix, header-1 and header-2 will now correctly receive the 'radio-card--full-width' class!\n";