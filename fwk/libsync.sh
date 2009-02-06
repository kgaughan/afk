#
# Some notes on the design.
#
# Command functions are prefixed by 'do_', preexecution hooks by 'pre_',
# library functions by 'afk_', except for 'dispatch', which is special as
# it's invoked at the end of the script that includes this library.
# 

SSH=`which ssh`
: ${DEPLOY_USER:=root}

dispatch () {
	local cmd
	local possible_cmd

	if ! pre_dispatch; then
		exit 1
	fi

	cmd=$1
	shift

	case $cmd in
		production | staging | test)
		pre_deploy $cmd
		afk_deploy $cmd
		;;

		*)
		if test "`type -t do_$cmd`" = "function"; then
			do_$cmd "$@"
			exit $?
		fi
	esac

	echo "Unknown command: '$cmd'"
	echo -n "Usage: $0 {production|staging|test"
	# This regular expression is meant to be portable across implementations
	# of sed, or at least to those I know and care about such as FreeBSD's
	# sed, and GNU sed, hence the use of basic regex syntax.
	for cmd in `set | sed -n "s/^do_\\([a-x]\{1,\}\\) ().*$/\1/p"`; do 
		echo -n "|$cmd"
	done
	echo "}"
	exit 1
}

afk_deploy () {
	local dest
	local target=$1
	local site_config

	read version <version

	if test -e "deployment/$target.target"; then
		# Upload data.
		cat deployment/$target.target | while read ip dir; do
			dest="$DEPLOY_USER@$ip:$dir/$version"
			echo -en "Syncing to \033[1m$dest\033[0m: "
			echo -n "codebase, "
			rsync -rlptz --delete --exclude-from=deployment/exclude --rsh=$SSH . "$dest"
			site_config=default
			if test -f "deployment/configurations/$target/$ip.php"; then
				site_config="$target/$ip"
			fi
			echo -n "site configuration, "
			rsync -tLz --rsh=$SSH "deployment/configurations/$site_config.php" "$dest/site-config.php"
			echo -e "\033[1mdone\033[0m."
		done

		# Switch deployed version.
		echo -n "Switching current on: "
		cat deployment/$target.target | while read ip dir; do
			cdir="$dir/current"
			vdir="$dir/$version"
			echo -n "$ip, "
			ssh "$DEPLOY_USER@$ip" /bin/sh <<EOT
if test ! -e $cdir -o '\$(realpath $cdir)' != '\$(realpath $vdir)'; then
	test -e $cdir && rm $cdir
	ln -s $vdir $cdir
fi
EOT
		done
		echo -e "\033[1mdone\033[0m."
	else
		echo "No can do: deployment target '$target' doesn't exist."
	fi
}

afk_php_lint () {
	local method=$1

	$method | while read; do
		if ! php -l "$REPLY"; then
			echo $REPLY has errors.
			return 1
		fi
	done

	return 0
}

afk_find_changed_php_files_svn () {
	svn status | grep '^M .*\.php' | cut -c 8-
}

afk_find_all_php_files () {
	local exclusion=''
	local i

	# exclude directories and symlinks in ./lib - they come from elsewhere so
	# we don't bother linting them.
	for i in `ls ./lib`; do
		if test -d "./lib/$i" -o -L "./lib/$i"; then
			exclusion="$exclusion -path ./lib/$i -prune -o"
		fi
	done
	find . $exclusion -name \*.php | cut -c 3-
}

afk_make_directory_structure () {
	mkdir -p assets/images classes templates tests lib/classes
	mkdir -p deployment/configurations/{staging,production} documentation
	cp -r $1/basis/project-template/* .
	cp $1/basis/htaccess .htaccess
	ln -s $1/fwk lib/afk
	ln -s $1/assets assets/afk
}

# == Commands =================================================================

do_create () {
	# Fallback for when ./afk create is ran in a directory where a project's
	# already been created.
	echo A project already exists here.
	exit 1
}

do_purgesvn () {
	find . -name .svn -type d -exec rm -rf {} +
}

do_lint () {
	local method=afk_find_changed_php_files_svn

	while getopts "a" flag; do
		case $flag in
			a)
			method=afk_find_all_php_files
			;;
		esac
	done

	if afk_php_lint $method; then
		echo All clean!
	fi
}

# == Hooks ====================================================================

pre_deploy () {
	# This function is a dummy hook - replace it with your own.
	: 
}

pre_dispatch () {
	# This function is a dummy hook - replace it with your own.
	:
}
