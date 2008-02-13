#!/bin/sh

if [ -z "$1" ]; then
	echo Syntax: `basename $0` "<webserver-application-root>"
	exit 1
fi

ROOT=$1

mkdir -p assets/images classes handlers templates tests lib/classes 2>/dev/null

cat >.htaccess <<LEFIN
RewriteEngine On
RewriteBase $ROOT
RewriteRule ^assets/.*         -                [L]
RewriteRule ^favicon\.ico      -                [L]
RewriteRule ^robots\.txt       -                [L]
RewriteRule ^dispatch\.php/.*  -                [L]
RewriteRule ^(.*)$             dispatch.php/\$1  [QSA,L]
LEFIN

cat >sync <<LEFIN
#!/bin/sh

do_sync () {
	local OLD_LOC=\`pwd\`
	echo Syncing from ./\$1 to \$2/\$1...
	cd \$1
	rsync -rlptvz --rsh=\`which ssh\` --del \\
		-C --exclude="sync" --exclude="tests" --exclude=".htaccess" --exclude=".*.sw?" --exclude="*.xcf" \\
		./ "\$2/\$1"
	cd \$OLD_LOC
}

do_sync_all () {
	echo "Affirmative, Dave, I read you."
	for AREA in .; do
		do_sync \$AREA "\$1"
	done
}

do_lint () {
	local i=''
	local exclusion=''

	# Exclude directories and symlinks in ./lib - they come from elsewhere so
	# we don't bother linting them.
	for i in \`ls ./lib\`; do
		if [ -d "./lib/\$i" -o -L "./lib/\$i" ]; then
			exclusion="\$exclusion -wholename ./lib/\$i -prune -o"
		fi
	done

	for i in \`find . \$exclusion -name \*.php -o -name \*.inc\`; do
		if ! php -l "\$i"; then
			echo \$i has errors.
			return 1
		fi
	done

	return 0
}

# Make sure that we're in the right directory.
cd \`dirname \$0\`

case "\$1" in
	lint)
	if do_lint; then
		echo All clean!
	fi
	;;

	purge-svn)
	echo Purging .svn directories...
	find . -name .svn -type d -exec rm -rf {} +
	echo Done!
	;;

	production)
	# Remove these three lines, and possibly edit the do_sync_all function.
	echo "You'll need to edit me before you even think about running me."
	exit 255
	do_sync_all "%%PROD_USER%%@%%PROD_SERVER%%:%%PROD_APPLICATION_PATH%%"
	;;

	test)
	# Remove these three lines, and possibly edit the do_sync_all function.
	echo "You'll need to edit me before you even think about running me."
	exit 255
	do_sync_all "%%TEST_USER%%@%%TEST_SERVER%%:%%TEST_APPLICATION_PATH%%"
	;;

	*)
	echo "I'm sorry Dave, I'm afraid I can't do that."
	exit 1
esac
LEFIN
chmod +x sync

cat >assets/all.css <<LEFIN
body {
	margin: 0;
	padding: 0;
	line-height: 1.5;
}
body, h1, h2, h3, h4, h5, h6 {
	font-family: "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", Lucida, sans-serif;
}
#body {
	font-size: 80%;
}
form {
	margin: 0 auto;
	padding: 0;
}
#errors {
	border: 1px solid red;
	padding: 1ex;
	margin: 1ex 0;
	width: 40em;
}
#errors ul {
	margin: 0;
	padding: 0;
}
#errors li {
	margin: 0 0 0 1em;
	padding: 0;
}
ol.pagination {
	margin-left: 0;
	margin-right: 0;
	padding: 0;
	text-align: center;
	list-style: none;
}
ol.pagination li {
	display: inline;
	padding: 0 0.5ex;
}

/*- Tabular Data -*/
table.data {
	border-collapse: collapse;
	margin: 1em auto;
	text-align: center;
}
table.data th, table.data td {
	border-bottom: 1px solid #666;
	padding: 0.25ex 0.5ex;
}
table.data th {
	background: #666;
	color: white;
}
th, td {
	text-align: left;
	vertical-align: top;
}
caption {
	text-align: center;
	margin: 1ex auto;
	caption-side: bottom;
}
th {
	white-space: nowrap;
}
.numeric, .currency {
	text-align: right;
}

#ctl {
	float: right;
}
LEFIN

cat >assets/screen.css <<LEFIN
h1 {
	margin: 0;
	background: #666;
	padding: 0 80px 0 0.5ex;
	color: white;
	font-weight: normal;
	font-size: 200%;
}
#body {
	padding: 1em;
}
LEFIN

cat >assets/print.css <<LEFIN
h1 {
	border-bottom: 1px solid gray;
	font-weight: normal;
}
table.data thead, table.data tbody {
	border-top: 2px solid gray;
	border-bottom: 2px solid gray;
}
table.data tbody tr {
	border-top: 1px solid silver;
}
a:link, a:visited {
	color: inherit;
	text-decoration: none;
}
form {
	display: none;
}
LEFIN

cat >bootstrap.php <<LEFIN
<?php
define('APP_ROOT', dirname(__FILE__));
define('APP_TEMPLATE_ROOT', APP_ROOT . '/templates');
require(APP_ROOT . '/lib/afk/afk.php');
\$filters = AFK::bootstrap();
LEFIN

cat >config.php <<LEFIN
<?php
define('DB_HOST', 'localhost');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');

define('APP_VERSION', '0.0.0');

function routes() {
	\$r = new AFK_Routes();
	\$r->route('/', array('_handler' => 'Root'));
	\$r->fallback(array('_handler' => 'AFK_Default'));
	return \$r;
}

function init() {
	global \$db;

	error_reporting(E_ALL);
	date_default_timezone_set('UTC');
	AFK::load_helper('core');

	if (defined('DB_NAME') && DB_NAME != '') {
		\$db = new DB_MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

		// If you need sessions, you might need this:
		// new AFK_Session_DB(\$db, 'sessions');

		// If you need output caching, you'll need this:
		// AFK::load_helper('cache');
		// cache_install(new AFK_Cache_DB(\$db, 'cache'));
	}

	session_start();

	return array();
}
LEFIN

cat >dispatch.php <<LEFIN
<?php
require(dirname(__FILE__) . '/bootstrap.php');
AFK::process_request(AFK_Registry::routes(), \$filters);
LEFIN

cat >handlers/RootHandler.php <<LEFIN
<?php
class RootHandler extends AFK_HandlerBase {

	public function on_get(AFK_Context \$ctx) {
	}
}
LEFIN
