<?php

// Note: This mime-type is not official and will prevent JSON content from
//       being viewable in most browsers. It is, however, a defacto standard
//       and YUI expects JSON requests to use this mime-type. Other
//       alternatice mime-types are text/javascript, or text/plain.
header('Content-Type: application/json; charset=utf-8');

echo $this->content;

?>
