<?php
/**
 * Simple security audit script to find dangerous functions and hardcoded passwords
 */

$directories = ['includes', 'api', 'tests', '.'];
$extensions = ['php', 'js'];
$dangerousFunctions = ['eval', 'system', 'exec', 'shell_exec', 'passthru', 'proc_open', 'popen', 'pcntl_exec'];
$passwordPattern = '/password.*=.*[\'"].*[\'"]/i';

$issuesFound = 0;

function scanDirectory($dir, $extensions, $dangerousFunctions, $passwordPattern, &$issuesFound) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'vendor' || $file === '.git') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            scanDirectory($path, $extensions, $dangerousFunctions, $passwordPattern, $issuesFound);
        } else {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if (in_array($ext, $extensions)) {
                $content = file_get_contents($path);
                $lines = explode("\n", $content);
                
                foreach ($lines as $index => $line) {
                    // Check for dangerous functions
                    foreach ($dangerousFunctions as $func) {
                        if (preg_match('/\b' . preg_quote($func) . '\s*\(/', $line)) {
                            echo "POTENTIAL ISSUE: Dangerous function '$func' found in $path on line " . ($index + 1) . "\n";
                            echo "  > $line\n";
                            $issuesFound++;
                        }
                    }
                    
                    // Check for hardcoded passwords
                    if (preg_match($passwordPattern, $line)) {
                        // Filter out common false positives
                        if (
                            strpos($line, '$_POST') !== false || 
                            strpos($line, '$_GET') !== false || 
                            strpos($line, '$_REQUEST') !== false ||
                            strpos($line, 'type="password"') !== false ||
                            strpos($line, 'id="password"') !== false ||
                            strpos($line, 'name="password"') !== false ||
                            strpos($line, 'document.getElementById') !== false // JS DOM access
                        ) {
                            continue;
                        }

                        echo "POTENTIAL ISSUE: Possible hardcoded password found in $path on line " . ($index + 1) . "\n";
                        echo "  > " . trim($line) . "\n";
                        $issuesFound++;
                    }
                }
            }
        }
    }
}

echo "Starting security audit...\n";
foreach (['includes', 'api', 'tests'] as $dir) {
    if (is_dir($dir)) {
        scanDirectory($dir, $extensions, $dangerousFunctions, $passwordPattern, $issuesFound);
    }
}

// Also check root files but avoid recursing into already checked dirs
$rootFiles = scandir('.');
foreach ($rootFiles as $file) {
    if (is_file($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (in_array($ext, $extensions)) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            foreach ($lines as $index => $line) {
                foreach ($dangerousFunctions as $func) {
                    if (preg_match('/\b' . preg_quote($func) . '\s*\(/', $line)) {
                        echo "POTENTIAL ISSUE: Dangerous function '$func' found in $file on line " . ($index + 1) . "\n";
                        $issuesFound++;
                    }
                }
                if (preg_match($passwordPattern, $line)) {
                    // Filter out common false positives
                    if (
                        strpos($line, '$_POST') !== false || 
                        strpos($line, '$_GET') !== false || 
                        strpos($line, '$_REQUEST') !== false ||
                        strpos($line, 'type="password"') !== false ||
                        strpos($line, 'id="password"') !== false ||
                        strpos($line, 'name="password"') !== false ||
                        strpos($line, 'document.getElementById') !== false // JS DOM access
                    ) {
                        continue;
                    }

                    echo "POTENTIAL ISSUE: Possible hardcoded password found in $file on line " . ($index + 1) . "\n";
                    echo "  > " . trim($line) . "\n";
                    $issuesFound++;
                }
            }
        }
    }
}

if ($issuesFound === 0) {
    echo "No obvious security issues found.\n";
    exit(0);
} else {
    echo "\nFound $issuesFound potential security issues.\n";
    // We don't want to fail the CI for these warnings as they might be false positives
    // but they are printed for the developer to see.
    exit(0);
}
