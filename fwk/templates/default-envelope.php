<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">

<html><head>

	<title><?php if (isset($page_title)) ee($page_title) ?></title>

</head><body>

<?php if (isset($page_title)) { ?>
<h1><?php ee($page_title) ?></h1>
<?php } ?>

<div id="body"><?php echo $generated_content ?></div>

</body></html>
