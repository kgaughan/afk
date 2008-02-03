<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">

<html><head>

	<title><?php ee($page_title) ?></title>
	<?php load_helper('html'); stylesheets(array('screen' => 'afk/splash')) ?>

</head><body>

<h1><span><img src="<?php ee($ctx->application_root(), 'assets/afk/images/logo.png') ?>" width="205" height="53" alt="AFK"></span></h1>

<div id="body">

<h2>It&rsquo;s alive!</h2>

<p>You look to have successfully created a new project. Now, if there was
only some decent documentation to get you started...</p>

<p>In the meantime, you can replace this page with your own homepage by
creating a file called <code>default.php</code> in your project&rsquo;s
<code>templates</code> directory.</p>

</div>

</body></html>
