#
# Some notes on the design.
#
# Command functions are prefixed by 'do_', preexecution hooks by 'pre_',
# library functions by 'afk_', except for 'dispatch', which is special as
# it's invoked at the end of the script that includes this library.
#

SSH=`which ssh`

dispatch () {
	local cmd
	local possible_cmd

	if ! pre_dispatch; then
		exit 1
	fi

	cmd=$1
	shift

	case $cmd in
		production | staging | testing)
		pre_deploy $cmd
		afk_deploy $cmd
		exit $?
		;;

		*)
		if test "`type -t do_$cmd`" = "function"; then
			do_$cmd "$@"
			exit $?
		fi
	esac

	if test "x$cmd" != "x"; then
		echo "Unknown subcommand: '$cmd'"
	fi
	echo -n "Usage: $0 {production|staging|testing"
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
	local target=$1
	local dest ip deploy_user dir version site_config
	local cdir vdir

	read version <version

	if ! test -e "deployment/$target.target"; then
		echo "No can do: deployment target '$target' doesn't exist."
		return 1
	fi

	# Upload data.
	cat deployment/$target.target | while read ip deploy_user dir; do
		dest="$deploy_user@$ip:$dir/$version"
		echo -en "Syncing to \033[1m$dest\033[0m: "
		echo -n "codebase, "
		rsync -rlptzL --delete --exclude-from=deployment/exclude --rsh=$SSH . "$dest"
		site_config=default
		if test -f "deployment/configurations/$target/$ip.php"; then
			site_config="$target/$ip"
		fi
		echo -n "site configuration, "
		rsync -tLz --rsh=$SSH "deployment/configurations/$site_config.php" "$dest/site-config.php"
		echo -e "\033[1mdone\033[0m."
	done

	if ! pre_switch $target; then
		return 1
	fi

	# Switch deployed version.
	echo -n "Switching current on: "
	cat deployment/$target.target | while read ip deploy_user dir; do
		cdir="$dir/current"
		vdir="$dir/$version"
		echo -n "$ip, "
		echo "cd '$dir'; test -e current && rm current; ln -s $version current" | ssh "$deploy_user@$ip" /bin/sh
	done
	echo -e "\033[1mdone\033[0m."
	return 0
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
	svn status --xml | xmlstarlet sel -t -m '//entry' -i "wc-status[@item='modified' or @item='added']" -v '@path' -n | grep '.php$'
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

afk_list_help () {
	local cmd

	echo "Subcommands with help available:"
	# This regular expression is meant to be portable across implementations
	# of sed, or at least to those I know and care about such as FreeBSD's
	# sed, and GNU sed, hence the use of basic regex syntax.
	for cmd in `set | sed -n "s/^help_\\([a-x]\{1,\}\\) ().*$/\1/p"`; do
		echo "  $cmd"
	done
}

afk_help_banner () {
	echo -e "\033[1m$1\033[0m: $2\n\nUsage: $1 $3"
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

help_purgesvn () {
	afk_help_banner "purgesvn" "Removes any .svn directories in the project."
}

do_lint () {
	local method=afk_find_changed_php_files_svn
	local flag

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

help_lint () {
	afk_help_banner "lint" "Lints PHP files with 'php -l'." "[-a]"
	cat <<LEFIN

  The command currently only supports Subversion as a VCS.

Options:
  -a  Lint all files, regardless of whether the VCS thinks they've
      changed.
LEFIN
}

do_help () {
	if test "x$1" = "x"; then
		afk_list_help
	elif test "`type -t help_$1`" = "function"; then
		help_$1
	elif test "`type -t do_$1`" = "function"; then
		echo "Sorry, no help is currently available for '$1'."
		afk_list_help
	else
		echo "Unknown subcommand: '$1'"
		afk_list_help
	fi
}

do_ctags () {
	exec ctags-exuberant -f tags \
		-h ".php" -R \
		--exclude="\.svn" \
		--totals=yes \
		--tag-relative=yes \
		--PHP-kinds=+cf \
		--regex-PHP='/abstract class ([^ ]*)/\1/c/' \
		--regex-PHP='/interface ([^ ]*)/\1/c/' \
		--regex-PHP='/(public |static |abstract |protected |private )+function ([^ (]*)/\2/f/'
}

do_dev () {
	cd $(dirname $0)
	php -S localhost:8080 dispatch.php
}

help_help () {
	afk_help_banner "help" "Displays help on the given subcommand." "CMD"
}

help_production () {
	afk_help_banner "production" "Deploys the project to its production server(s)."
}

help_staging () {
	afk_help_banner "staging" "Deploys the project to its staging server(s)."
}

help_testing () {
	afk_help_banner "testing" "Deploys the project to its test server(s)."
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

pre_switch () {
	# This function is a dummy hook - replace it with your own.
	:
}
