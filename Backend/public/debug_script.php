<?php
/**
 * OCR Debug Script for Egyptian ID Cards
 * 
 * This script provides detailed debugging for Tesseract OCR with Arabic language support
 * and implements multiple extraction and verification strategies.
 */

// Ensure errors are displayed
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to convert Arabic digits to English
function convertArabicDigitsToEnglish($text) {
    $arabic = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $english = ['0','1','2','3','4','5','6','7','8','9'];
    return str_replace($arabic, $english, $text);
}

// Function to preprocess image for better OCR results
function preprocessImage($imagePath) {
    $info = pathinfo($imagePath);
    $processedPath = $info['dirname'] . '/' . $info['filename'] . '_processed.' . $info['extension'];
    
    // Check if ImageMagick is available
    exec("which convert", $output, $returnVar);
    if ($returnVar === 0) {
        // Use ImageMagick to enhance the image
        $cmd = "convert '$imagePath' -colorspace Gray -normalize -sharpen 0x1 '$processedPath'";
        exec($cmd, $output, $returnVar);
        
        if ($returnVar === 0 && file_exists($processedPath)) {
            return [
                'path' => $processedPath,
                'preprocessed' => true,
                'method' => 'ImageMagick'
            ];
        }
    }
    
    // If ImageMagick failed or isn't available, return original
    return [
        'path' => $imagePath,
        'preprocessed' => false,
        'method' => 'None'
    ];
}

// Function to extract National ID using multiple methods
function extractNationalId($ocrText) {
    $results = [];
    
    // Method 1: Direct 14-digit pattern match
    if (preg_match('/\b\d{14}\b/', $ocrText, $matches)) {
        $results['direct_match'] = [
            'method' => 'Direct 14-digit match',
            'id' => $matches[0]
        ];
    }
    
    // Method 2: Extract all digits and find 14-digit sequence
    $textDigitsOnly = preg_replace('/[^0-9]/', '', $ocrText);
    if (preg_match('/\d{14}/', $textDigitsOnly, $matches)) {
        $results['digits_extraction'] = [
            'method' => 'All digits extraction',
            'id' => $matches[0]
        ];
    }
    
    // Method 3: Look for ID after specific labels in Arabic and English
    $patterns = [
        '/(?:الرقم القومي|رقم قومي|الرقم|رقم|ID|id|Id)[:\s]*(\d{14})/u',
        '/(?:National ID|National Number)[:\s]*(\d{14})/i',
        '/(?:بطاقة|هوية)[:\s]*(\d{14})/u'
    ];
    
    foreach ($patterns as $index => $pattern) {
        if (preg_match($pattern, $ocrText, $matches)) {
            $results['label_match_' . $index] = [
                'method' => 'Label-based extraction (Pattern ' . $index . ')',
                'id' => $matches[1]
            ];
        }
    }
    
    // If no 14-digit number found, try to find any sequence of digits that might be part of an ID
    if (empty($results) && preg_match_all('/\d+/', $ocrText, $matches)) {
        $potentialIds = [];
        foreach ($matches[0] as $match) {
            if (strlen($match) >= 5) { // Only consider sequences of 5+ digits
                $potentialIds[] = $match;
            }
        }
        
        if (!empty($potentialIds)) {
            $results['partial_digits'] = [
                'method' => 'Partial digit sequences',
                'id' => implode(', ', $potentialIds)
            ];
        }
    }
    
    return $results;
}

// Function to verify ID against input using multiple methods
function verifyNationalId($extractedResults, $inputId) {
    $verificationResults = [];
    $finalResult = false;
    
    // Clean input ID (digits only)
    $inputDigits = preg_replace('/[^0-9]/', '', $inputId);
    
    foreach ($extractedResults as $key => $result) {
        if (empty($result['id'])) continue;
        
        $extractedId = $result['id'];
        $extractedDigits = preg_replace('/[^0-9]/', '', $extractedId);
        
        // Method 1: Direct comparison
        $directMatch = ($extractedId === $inputId);
        
        // Method 2: Digits-only comparison
        $digitsMatch = ($extractedDigits === $inputDigits);
        
        // Method 3: Contains check
        $containsMatch = (strpos($extractedDigits, $inputDigits) !== false);
        
        // Method 4: Input contains extracted
        $inputContainsExtracted = (strpos($inputDigits, $extractedDigits) !== false);
        
        // Method 5: Levenshtein distance for partial matches
        $levenshteinMatch = false;
        $levenshteinDistance = -1;
        if (strlen($extractedDigits) >= 10 && strlen($inputDigits) >= 10) {
            $levenshteinDistance = levenshtein($extractedDigits, $inputDigits);
            $levenshteinMatch = ($levenshteinDistance <= 2); // Allow up to 2 character differences
        }
        
        $methodResult = $directMatch || $digitsMatch || $containsMatch || $inputContainsExtracted || $levenshteinMatch;
        $finalResult = $finalResult || $methodResult;
        
        $verificationResults[$key] = [
            'extracted_id' => $extractedId,
            'extracted_digits' => $extractedDigits,
            'direct_match' => $directMatch,
            'digits_match' => $digitsMatch,
            'contains_match' => $containsMatch,
            'input_contains_extracted' => $inputContainsExtracted,
            'levenshtein_distance' => $levenshteinDistance,
            'levenshtein_match' => $levenshteinMatch,
            'method_result' => $methodResult
        ];
    }
    
    return [
        'details' => $verificationResults,
        'final_result' => $finalResult
    ];
}

// Main test function with multiple OCR approaches
function testOcr($imagePath, $expectedId = null) {
    echo "<h2>OCR Debug Results</h2>";
    
    // Check if image exists
    if (!file_exists($imagePath)) {
        echo "<p style='color:red'>Error: Image file not found at $imagePath</p>";
        return;
    }
    
    echo "<p>Testing image: $imagePath</p>";
    
    // Check if tesseract is installed
    exec("which tesseract", $output, $returnVar);
    if ($returnVar !== 0) {
        echo "<p style='color:red'>Error: Tesseract is not installed or not in PATH</p>";
        return;
    }
    
    // Check tesseract version
    exec("tesseract --version", $versionOutput);
    echo "<p>Tesseract version: " . $versionOutput[0] . "</p>";
    
    // Check available languages
    exec("tesseract --list-langs", $langOutput, $langReturnVar);
    echo "<p>Available languages: " . implode(", ", array_slice($langOutput, 1)) . "</p>";
    
    $hasArabic = in_array('ara', $langOutput);
    if (!$hasArabic) {
        echo "<p style='color:orange'>Warning: Arabic language data not found. OCR may not work correctly for Arabic text.</p>";
    }
    
    // Preprocess image
    $preprocessResult = preprocessImage($imagePath);
    echo "<p>Image preprocessing: " . ($preprocessResult['preprocessed'] ? 'Applied (' . $preprocessResult['method'] . ')' : 'Not applied') . "</p>";
    $processedImagePath = $preprocessResult['path'];
    
    // Test different OCR configurations
    $ocrConfigurations = [
        'ara' => 'Arabic only',
        'ara+eng' => 'Arabic + English',
        'eng+ara' => 'English + Arabic',
    ];
    
    $bestResult = null;
    $bestScore = -1;
    
    foreach ($ocrConfigurations as $lang => $description) {
        echo "<h3>Testing OCR with $description</h3>";
        
        // Try different PSM modes
        $psmModes = [3, 6, 4, 11];
        foreach ($psmModes as $psm) {
            echo "<h4>Page Segmentation Mode: $psm</h4>";
            
            $outputBase = tempnam(sys_get_temp_dir(), 'ocr');
            $cmd = "tesseract '$processedImagePath' '$outputBase' -l $lang --psm $psm";
            
            echo "<p>Executing: $cmd</p>";
            
            exec($cmd, $cmdOutput, $cmdReturnVar);
            if ($cmdReturnVar !== 0) {
                echo "<p style='color:red'>Error: OCR process failed with code $cmdReturnVar</p>";
                continue;
            }
            
            // Read OCR output
            $ocrText = file_get_contents($outputBase . '.txt');
            
            // Display raw OCR output
            echo "<h5>Raw OCR Output:</h5>";
            echo "<pre>" . htmlspecialchars($ocrText) . "</pre>";
            
            // Normalize text (convert Arabic digits)
            $normalizedText = convertArabicDigitsToEnglish($ocrText);
            echo "<h5>Normalized Text (Arabic digits converted):</h5>";
            echo "<pre>" . htmlspecialchars($normalizedText) . "</pre>";
            
            // Extract National ID using multiple methods
            $extractionResults = extractNationalId($normalizedText);
            echo "<h5>National ID Extraction Results:</h5>";
            
            if (empty($extractionResults)) {
                echo "<p>No potential IDs found</p>";
            } else {
                echo "<ul>";
                foreach ($extractionResults as $key => $result) {
                    echo "<li><strong>" . $result['method'] . ":</strong> " . $result['id'] . "</li>";
                }
                echo "</ul>";
            }
            
            // Verify against expected ID if provided
            if ($expectedId && !empty($extractionResults)) {
                $verificationResult = verifyNationalId($extractionResults, $expectedId);
                echo "<h5>Verification against expected ID ($expectedId):</h5>";
                
                echo "<ul>";
                foreach ($verificationResult['details'] as $key => $detail) {
                    echo "<li><strong>" . $extractionResults[$key]['method'] . ":</strong>";
                    echo "<ul>";
                    echo "<li>Extracted ID: " . $detail['extracted_id'] . "</li>";
                    echo "<li>Direct match: " . ($detail['direct_match'] ? 'Yes' : 'No') . "</li>";
                    echo "<li>Digits-only match: " . ($detail['digits_match'] ? 'Yes' : 'No') . "</li>";
                    echo "<li>Contains match: " . ($detail['contains_match'] ? 'Yes' : 'No') . "</li>";
                    echo "<li>Input contains extracted: " . ($detail['input_contains_extracted'] ? 'Yes' : 'No') . "</li>";
                    if ($detail['levenshtein_distance'] >= 0) {
                        echo "<li>Levenshtein distance: " . $detail['levenshtein_distance'] . "</li>";
                        echo "<li>Levenshtein match: " . ($detail['levenshtein_match'] ? 'Yes' : 'No') . "</li>";
                    }
                    echo "<li>Result: " . ($detail['method_result'] ? '<span style="color:green">MATCH</span>' : '<span style="color:red">NO MATCH</span>') . "</li>";
                    echo "</ul>";
                    echo "</li>";
                }
                echo "</ul>";
                
                echo "<p>Final verification result: " . ($verificationResult['final_result'] ? 
                    '<span style="color:green; font-weight:bold">VERIFIED</span>' : 
                    '<span style="color:red; font-weight:bold">NOT VERIFIED</span>') . "</p>";
                
                // Calculate a score for this configuration
                $score = 0;
                foreach ($verificationResult['details'] as $detail) {
                    if ($detail['method_result']) $score++;
                }
                
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestResult = [
                        'lang' => $lang,
                        'psm' => $psm,
                        'verified' => $verificationResult['final_result'],
                        'score' => $score
                    ];
                }
            }
            
            // Clean up temp file
            @unlink($outputBase . '.txt');
        }
    }
    
    // Show best configuration
    if ($bestResult) {
        echo "<h3>Best OCR Configuration</h3>";
        echo "<p>Language: " . $ocrConfigurations[$bestResult['lang']] . "</p>";
        echo "<p>PSM Mode: " . $bestResult['psm'] . "</p>";
        echo "<p>Verification: " . ($bestResult['verified'] ? 
            '<span style="color:green; font-weight:bold">VERIFIED</span>' : 
            '<span style="color:red; font-weight:bold">NOT VERIFIED</span>') . "</p>";
        echo "<p>Score: " . $bestResult['score'] . "</p>";
        
        echo "<h3>Recommended Tesseract Command</h3>";
        echo "<pre>tesseract input.jpg output -l " . $bestResult['lang'] . " --psm " . $bestResult['psm'] . "</pre>";
    }
    
    // Clean up processed image if it was created
    if ($preprocessResult['preprocessed']) {
        @unlink($processedImagePath);
    }
}

// HTML form for testing
?>
<!DOCTYPE html>
<html>
<head>
    <title>Egyptian ID OCR Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 900px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="file"] { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; font-size: 16px; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; border: 1px solid #ddd; }
        .results { margin-top: 20px; border-top: 1px solid #ddd; padding-top: 20px; }
        h1, h2, h3, h4, h5 { color: #333; }
        ul { margin-top: 5px; }
        .note { background: #fffde7; padding: 10px; border-left: 4px solid #ffd600; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Egyptian ID OCR Debug Tool</h1>
        <p>This advanced tool tests multiple OCR configurations for extracting data from Egyptian ID cards.</p>
        
        <div class="note">
            <p><strong>Note:</strong> This tool will test multiple OCR configurations and extraction methods to find the best approach for your ID cards. The process may take 1-2 minutes to complete.</p>
        </div>
        
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="id_image">ID Card Image:</label>
                <input type="file" name="id_image" id="id_image" required>
            </div>
            <div class="form-group">
                <label for="expected_id">Expected National ID (for verification):</label>
                <input type="text" name="expected_id" id="expected_id" placeholder="e.g., 30303132601728">
            </div>
            <button type="submit">Run OCR Debug Test</button>
        </form>
        
        <div class="results">
            <?php
            // Process form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['id_image'])) {
                $uploadDir = sys_get_temp_dir() . '/';
                $uploadFile = $uploadDir . basename($_FILES['id_image']['name']);
                
                if (move_uploaded_file($_FILES['id_image']['tmp_name'], $uploadFile)) {
                    $expectedId = isset($_POST['expected_id']) ? $_POST['expected_id'] : null;
                    testOcr($uploadFile, $expectedId);
                } else {
                    echo "<p style='color:red'>Error uploading file.</p>";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>
