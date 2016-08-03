<?php
$arglist = implode(", ", array_map(function($arg){
  // Optional arg
  if ($arg[0] == '['){
    $arg = trim($arg, "[]");
    $arg = "{$arg} = null";
  }
  return '$'.$arg;
}, $args));


echo "  // {$name}\n";
echo "  public function {$name}({$arglist}){\n";
echo "    \$params = array();\n\n";

// Optional arg
foreach ($args as $arg){
  if ($arg[0] == '['){
    $arg = trim($arg, "[]");
    echo "    if (isset(\${$arg})){\n";
    echo "      \$params['{$arg}'] = \${$arg};\n";
    echo "    }\n";
  } else {
    echo "    \$params['{$arg}'] = \${$arg};\n";
  }
}

echo "\n";
echo "    return \$this->{$httpMethod}(\"{$name}\", \$params);\n";
echo "  }\n\n";
?>
