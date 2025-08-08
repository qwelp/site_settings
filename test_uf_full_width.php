<?php
/**
 * Test script to verify UF_FULL_WIDTH functionality
 * This script simulates the radio card rendering with different UF_FULL_WIDTH values
 */

// Mock the required classes and functions for testing
if (!function_exists('htmlspecialcharsbx')) {
    function htmlspecialcharsbx($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

// Test data simulating a section with UF_FULL_WIDTH = 1
$sectionWithFullWidth = [
    'id' => 'header-section',
    'title' => 'Header Section',
    'UF_FULL_WIDTH' => 1,
    'SUBSECTIONS' => [
        [
            'ID' => 2277,
            'id' => 'header-1',
            'title' => 'Шапка №1',
            'PICTURE' => 1388,
            'DEPTH' => 4
        ],
        [
            'ID' => 2278,
            'id' => 'header-2', 
            'title' => 'Шапка №2',
            'PICTURE' => 1389,
            'DEPTH' => 4
        ]
    ]
];

// Test data simulating a section with UF_FULL_WIDTH = 0
$sectionWithoutFullWidth = [
    'id' => 'header-section-2',
    'title' => 'Header Section 2',
    'UF_FULL_WIDTH' => 0,
    'SUBSECTIONS' => [
        [
            'ID' => 2279,
            'id' => 'header-3',
            'title' => 'Шапка №3',
            'PICTURE' => 1390,
            'DEPTH' => 4
        ]
    ]
];

// Function to simulate the UF_FULL_WIDTH logic
function testFullWidthLogic($section) {
    $isRadioCardGroup = !empty($section['SUBSECTIONS']) && (int)reset($section['SUBSECTIONS'])['DEPTH'] === 4;
    
    if ($isRadioCardGroup) {
        $groupCode = $section['id'];
        $isFullWidth = !empty($section['UF_FULL_WIDTH']) && (int)$section['UF_FULL_WIDTH'] === 1;
        
        echo "Section: " . $section['title'] . "\n";
        echo "Group Code: " . $groupCode . "\n";
        echo "UF_FULL_WIDTH: " . ($section['UF_FULL_WIDTH'] ?? 0) . "\n";
        echo "Is Full Width: " . ($isFullWidth ? 'YES' : 'NO') . "\n";
        
        foreach ($section['SUBSECTIONS'] as $subSection) {
            echo "  Radio Card: " . $subSection['title'] . "\n";
            
            // Simulate CSS class generation
            $radioCardClasses = ['radio-card'];
            if ($isFullWidth) {
                $radioCardClasses[] = 'radio-card--full-width';
            }
            
            echo "  CSS Classes: " . implode(' ', $radioCardClasses) . "\n";
        }
        echo "\n";
    }
}

echo "Testing UF_FULL_WIDTH functionality:\n\n";

echo "=== Test 1: Section with UF_FULL_WIDTH = 1 ===\n";
testFullWidthLogic($sectionWithFullWidth);

echo "=== Test 2: Section with UF_FULL_WIDTH = 0 ===\n";
testFullWidthLogic($sectionWithoutFullWidth);

echo "Test completed. Expected results:\n";
echo "- Test 1: Radio cards should have 'radio-card radio-card--full-width' classes\n";
echo "- Test 2: Radio cards should have only 'radio-card' class\n";