<?php
$content = file_get_contents(__DIR__ . '/../register.php');
$tokens = token_get_all($content);
foreach ($tokens as $idx => $token) {
    if (is_array($token) && $token[0] === T_STRING && $token[1] === 'execute') {
        echo "Found execute at token index $idx\n";
        // Print 10 tokens around it
        for ($k = $idx - 5; $k < $idx + 10; $k++) {
            if (isset($tokens[$k])) {
                $t = $tokens[$k];
                echo "Token $k: " . (is_array($t) ? token_name($t[0]) . ' (' . $t[1] . ')' : $t) . "\n";
            }
        }
    }
}
