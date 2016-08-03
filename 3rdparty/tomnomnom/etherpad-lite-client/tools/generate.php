<?php
// Generates an API client using the api-spec.json

$raw = file_get_contents(__DIR__.'/../api-spec.json');
$spec = json_decode($raw);

// Pre-process the methods to set httpMethod
$methods = array();
foreach ($spec->methods as $name => $args){

  // Slightly hacky, but should cover all bases
  $httpMethod = "post";
  if (hasPrefix($name, "get") ||
      hasPrefix($name, "list") ||
      hasPrefix($name, "pad") ||
      hasPrefix($name, "is")){
    $httpMethod = "get"; 
  }

  $methods[] = array(
    "name" => $name,
    "args" => $args,
    "httpMethod" => $httpMethod,
  );
}

echo "<?php\n";

// Render the template
render(__DIR__.'/templates/client.php', array(
  "version" => $spec->version,
  "methods" => $methods,
));

function render($template, $data){
  extract($data);
  include($template);
}

function hasPrefix($candidate, $prefix){
  if (strpos($candidate, $prefix) === 0){
    return true;
  }
  return false;
}
