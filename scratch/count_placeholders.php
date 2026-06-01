<?php
$content = file_get_contents(__DIR__ . '/../register.php');
$tokens = token_get_all($content);

$in_insert = false;
$insert_sql = '';
$bracket_level = 0;

$execute_stmt_found = false;
$execute_array_items = 0;

for ($i = 0; $i < count($tokens); $i++) {
    $token = $tokens[$i];
    $text = is_array($token) ? $token[1] : $token;
    
    // Detect INSERT INTO prepare statement
    if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING && stripos($text, 'INSERT INTO properties') !== false) {
        $insert_sql = eval("return $text;");
    }
    
    // Detect execute call
    if (is_array($token) && $token[0] === T_VARIABLE && $text === '$stmt') {
        if (isset($tokens[$i+1]) && $tokens[$i+1] === '->' && 
            isset($tokens[$i+2]) && is_array($tokens[$i+2]) && $tokens[$i+2][1] === 'execute') {
            
            $j = $i + 3;
            // Seek to [
            while ($j < count($tokens) && $tokens[$j] !== '[') {
                $j++;
            }
            if ($tokens[$j] === '[') {
                $execute_stmt_found = true;
                $depth = 1;
                $j++;
                $item_count = 0;
                $has_items = false;
                while ($j < count($tokens) && $depth > 0) {
                    $t = $tokens[$j];
                    $txt = is_array($t) ? $t[1] : $t;
                    
                    if ($txt === '[') {
                        $depth++;
                        $has_items = true;
                    } elseif ($txt === ']') {
                        $depth--;
                        if ($depth === 0) {
                            break;
                        }
                    } elseif ($txt === ',' && $depth === 1) {
                        $item_count++;
                    } else {
                        if (!is_array($t) || ($t[0] !== T_WHITESPACE && $t[0] !== T_COMMENT && $t[0] !== T_DOC_COMMENT)) {
                            $has_items = true;
                        }
                    }
                    $j++;
                }
                if ($has_items) {
                    $item_count++; // Last item after last comma
                }
                $execute_array_items = $item_count;
                break;
            }
        }
    }
}

if ($insert_sql) {
    if (preg_match('/INSERT INTO properties \((.*?)\)\s*VALUES\s*\((.*?)\)/si', $insert_sql, $m)) {
        $cols = array_map('trim', explode(',', $m[1]));
        $placeholders = array_map('trim', explode(',', $m[2]));
        
        echo "Columns count: " . count($cols) . "\n";
        echo "Placeholders count: " . count($placeholders) . "\n";
        echo "Execute parameters count: " . $execute_array_items . "\n";
        
        if (count($cols) === count($placeholders) && count($placeholders) === $execute_array_items) {
            echo "SUCCESS: Everything matches perfectly!\n";
        } else {
            echo "ERROR: Mismatch detected!\n";
        }
    } else {
        echo "Failed to parse INSERT INTO match inside SQL string:\n$insert_sql\n";
    }
} else {
    echo "Could not find the INSERT INTO properties statement in register.php\n";
}
