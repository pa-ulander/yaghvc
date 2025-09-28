<?php
$regex = '/^data:image\/(png|jpeg|gif|svg\+xml);base64,([A-Za-z0-9+\/]+={0,2})$/';
$testString = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAAOCAYAAAAfSC3RAAACmElEQVQokUWSa0iTcRTGn//26u4b6ZQ0U8lKMqykwPpgZVBEHyLp8jEoIZJADCQ0iCiStIwuZmHRioIuroQss2VkrkIrdeFckiZqdhctTXPOve8Tr7M6X8/zO+fwPEfIwy7IwQA0GgExGYQwyhCmMLRX1z2hJCJSN+xZgqAZnPgCaAUQ0EHICjSYLlKBCDdNQb7HLmeRoy3zQFnzYk/1WTckGUIXCVD+Kw+BpAxtuBXCpkN7bdXt/JL3W3J3xuHg3iTsL/NkNFWVPoWkQOj/wxooCrRhFgiTjI4n9ZVHHQObjxVEY8UGIi1zEhVFCahwdq5qvn+hHkKC0EcBigxwvAnkW3ge7L6TMi+VztOLOOKOY8ulKL68GM2emnjeLF3AZSlz2FCZ6yaHwLGv6pkv8MyxsUoHLcsLwBuHwE0rtdy2UuLWNTpmpkkszQEfnAPDAd47tbaB7NaJR+eXujfmtGTUXgFWp5uwPd8Oi1GBJEmwWYlP34L4PSFw7chPeD+MYnkWUVmy0CeNfe5N8ANIjNWpNmHzqklYrDIGRwRm2gXsM/xofRMOf1AgcbYOAfgxMvgxCmS9+dbh5A6VarxuIMdBDoJ0g+vSreytNpAEux7qqWrK82I+kC2xYOAzyFbz5QNJPrXhdRo4XK/n3WILkxPsbKqwsr8xBB3PjukhGyJJv+qqB+QvkN0mR2Fim5pU1hobzxTYOPbcyJoTNpoAlu6wdZKvIslR0O9VXe0Clc5p2Ge4WDh36ux3ThM/1RqnNhXvilU32cjvINtAf4cKdkzlSHpBTqgNY11JfLtFA+o14NU8Wx/piggNfg2yGVR8EF9/dP37PyCIoDQLs8z9hmv71nsC4wFz9klX2tD4/AEG+gBoQ7KghD8MZ2xdnt7s7wAAAABJRU5ErkJggg==';

echo "Testing regex match...\n";
$result = preg_match($regex, $testString, $matches);
echo "Result: " . ($result ? 'MATCH' : 'NO MATCH') . "\n";

if ($result) {
    print_r($matches);
} else {
    echo "Checking base64 part...\n";
    $prefix = 'data:image/png;base64,';
    if (strpos($testString, $prefix) === 0) {
        $base64Part = substr($testString, strlen($prefix));
        echo "Base64 part length: " . strlen($base64Part) . "\n";
        
        $base64Regex = '/^[A-Za-z0-9+\/]+={0,2}$/';
        $base64Match = preg_match($base64Regex, $base64Part);
        echo "Base64 regex match: " . ($base64Match ? 'YES' : 'NO') . "\n";
        
        if ("
\$regex = '/^data:image\/(png|jpeg|gif|svg\+xml);base64,([A-Za-z0-9+\/]+={0,2})$/';
\$testString = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAAOCAYAAAAfSC3RAAACmElEQVQokUWSa0iTcRTGn//26u4b6ZQ0U8lKMqykwPpgZVBEHyLp8jEoIZJADCQ0iCiStIwuZmHRioIuroQss2VkrkIrdeFckiZqdhctTXPOve8Tr7M6X8/zO+fwPEfIwy7IwQA0GgExGYQwyhCmMLRX1z2hJCJSN+xZgqAZnPgCaAUQ0EHICjSYLlKBCDdNQb7HLmeRoy3zQFnzYk/1WTckGUIXCVD+Kw+BpAxtuBXCpkN7bdXt/JL3W3J3xuHg3iTsL/NkNFWVPoWkQOj/wxooCrRhFgiTjI4n9ZVHHQObjxVEY8UGIi1zEhVFCahwdq5qvn+hHkKC0EcBigxwvAnkW3ge7L6TMi+VztOLOOKOY8ulKL68GM2emnjeLF3AZSlz2FCZ6yaHwLGv6pkv8MyxsUoHLcsLwBuHwE0rtdy2UuLWNTpmpkkszQEfnAPDAd47tbaB7NaJR+eXujfmtGTUXgFWp5uwPd8Oi1GBJEmwWYlP34L4PSFw7chPeD+MYnkWUVmy0CeNfe5N8ANIjNWpNmHzqklYrDIGRwRm2gXsM/xofRMOf1AgcbYOAfgxMvgxCmS9+dbh5A6VarxuIMdBDoJ0g+vSreytNpAEux7qqWrK82I+kC2xYOAzyFbz5QNJPrXhdRo4XK/n3WILkxPsbKqwsr8xBB3PjukhGyJJv+qqB+QvkN0mR2Fim5pU1hobzxTYOPbcyJoTNpoAlu6wdZKvIslR0O9VXe0Clc5p2Ge4WDh36ux3ThM/1RqnNhXvilU32cjvINtAf4cKdkzlSHpBTqgNY11JfLtFA+o14NU8Wx/piggNfg2yGVR8EF9/dP37PyCIoDQLs8z9hmv71nsC4wFz9klX2tD4/AEG+gBoQ7KghD8MZ2xdnt7s7wAAAABJRU5ErkJggg==';

echo 'Testing regex match...' . PHP_EOL;
\$result = preg_match(\$regex, \$testString, \$matches);
echo 'Result: ' . (\$result ? 'MATCH' : 'NO MATCH') . PHP_EOL;

if (\$result) {
    print_r(\$matches);
} else {
    echo 'Checking base64 part...' . PHP_EOL;
    \$prefix = 'data:image/png;base64,';
    if (strpos(\$testString, \$prefix) === 0) {
        \$base64Part = substr(\$testString, strlen(\$prefix));
        echo 'Base64 part length: ' . strlen(\$base64Part) . PHP_EOL;
        
        \$base64Regex = '/^[A-Za-z0-9+\/]+={0,2}$/';
        \$base64Match = preg_match(\$base64Regex, \$base64Part);
        echo 'Base64 regex match: ' . (\$base64Match ? 'YES' : 'NO') . PHP_EOL;
    }
}
" == $base64Match) {
            // Find problematic characters
            for ($i = 0; $i < strlen($base64Part); $i++) {
                $char = $base64Part[$i];
                if (!preg_match('/[A-Za-z0-9+\/=]/', $char)) {
                    echo "Problematic character at position $i: '$char' (ASCII: " . ord($char) . ")\n";
                    break;
                }
            }
        }
    } else {
        echo "Prefix does not match\n";
    }
}
