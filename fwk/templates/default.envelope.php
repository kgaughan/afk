<?php $ctx->header('Content-Type: text/html; charset=utf-8') ?>
<!DOCTYPE html>

<html><head>

	<title><?php ee($page_title) ?></title>

</head><body>

<h1><?php ee($page_title) ?></h1>

<div id="body"><?php echo $generated_content ?></div>

</body></html>
