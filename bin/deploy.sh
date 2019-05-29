#!/usr/bin/env bash

#https://deliciousbrains.com/deploying-wordpress-plugins-travis/
#https://kuzmi.ch/articles/wordpress-plugin-and-theme-development-using-git-only/
#https://github.com/ocean90/svn2git-tools/tree/master/automated-wordpress-plugin-deployment

if [[ -z "$TRAVIS" ]]; then
	echo "Script is only to be run by Travis CI" 1>&2
	exit 1
fi

if [[ -z "$SVNPASS" ]]; then
	echo "WordPress.org password not set" 1>&2
	exit 1
fi

if [[ -z "$TRAVIS_BRANCH" || "$TRAVIS_BRANCH" != "master" ]]; then
	echo "Build branch is required and must be 'master'" 1>&2
	exit 0
fi
# main config
PLUGINSLUG="cheetaho-image-optimizer"
CURRENTDIR=$(pwd)
MAINFILE="cheetaho.php"
DEFAULT_EDITOR="/usr/bin/vim"

# git config
GITPATH="$CURRENTDIR/" # this file should be in the base of your git repository

# svn config
SVNPATH="/tmp/$PLUGINSLUG" # Path to a temp SVN repo. No trailing slash required.
SVNURL="http://plugins.svn.wordpress.org/$PLUGINSLUG/" # Remote SVN repo on wordpress.org
SVNUSER="$SVNUSER" # Your SVN username

# Let's begin...
echo
echo "Deploy WordPress plugin"
echo "======================="
echo

# Check version in readme.txt is the same as plugin file after translating both to unix
# line breaks to work around grep's failure to identify mac line breaks
NEWVERSION1=$(grep "Stable tag: " "$GITPATH/readme.txt" | awk -F' ' '{print $NF}')
echo "readme.txt version: $NEWVERSION1"
NEWVERSION2=$(grep "Version: " "$GITPATH/$MAINFILE" | awk -F' ' '{print $NF}')
echo "$MAINFILE version: $NEWVERSION2"

if [ "$NEWVERSION1" != "$NEWVERSION2" ]
	then echo "Version in readme.txt & $MAINFILE don't match. Exiting."
	exit 1
fi

echo "Versions match in readme.txt and $MAINFILE. Let's proceed..."

if git show-ref --quiet --tags --verify -- "refs/tags/$NEWVERSION1"
	then
		echo "Version $NEWVERSION1 already exists as git tag. Exiting."
		exit 1
	else
		echo "Git version does not exist. Let's proceed..."
		echo
fi

cd $GITPATH

echo -n "Delete specific files script..."
rm -rf bin/
rm -rf tests/
rm -rf vendor/
rm -rf .phpcs.xml.dist
rm -rf .travis.yml
rm -rf phpunit.xml.dist
rm -rf .gitignore
rm -rf phpcs.ruleset.xml
rm -rf composer.json
rm -rf composer.lock
echo "Done."

echo -n "Saving previous Git tag version..."
PREVTAG=$(git describe --tags \`git rev-list --tags --max-count=1\`)
echo "Done."

echo -n "Committing new Git tag version..."
git commit -a -m "Committing version $NEWVERSION1"
echo "Done."

echo -n "Tagging new Git version..."
git tag -a "$NEWVERSION1" -m "Tagging version $NEWVERSION1"
echo "Done."

echo -n "Pushing new Git tag..."
git push --quiet --tags https://${GH_TOKEN}@github.com/CheetahO/cheetaho-wp-image-optimizer.git > /dev/null 2>&1
echo "Done."

echo -n "Creating local copy of SVN repo..."
svn checkout --quiet $SVNURL/trunk $SVNPATH/trunk
echo "Done."

echo -n "Exporting the HEAD of master from Git to the trunk of SVN..."
git checkout-index --quiet --all --force --prefix=$SVNPATH/trunk/
echo "Done."

echo -n "Preparing commit message..."
git log --pretty=oneline --abbrev-commit $PREVTAG..$NEWVERSION1 > /tmp/wppdcommitmsg.tmp
echo "Done."

echo -n "Preparing assets-wp-repo..."
if [ -d $SVNPATH/trunk/assets-wp-repo ]
	then
		svn checkout --quiet $SVNURL/assets $SVNPATH/assets > /dev/null 2>&1
		mkdir $SVNPATH/assets/ > /dev/null 2>&1 # Create assets directory if it doesn't exists
		mv $SVNPATH/trunk/assets-wp-repo/* $SVNPATH/assets/ # Move new assets
		rm -rf $SVNPATH/trunk/assets-wp-repo # Clean up
		cd $SVNPATH/assets/ # Switch to assets directory
		svn stat | grep "^?\|^M" > /dev/null 2>&1 # Check if new or updated assets exists
		if [ $? -eq 0 ]
			then
				svn stat | grep "^?" | awk '{print $2}' | xargs svn add --quiet # Add new assets
				echo -en "Committing new assets..."
				svn commit --no-auth-cache --no-auth-cache --quiet --username=$SVNUSER --password=$SVNPASS -m "Updated assets"
				echo "Done."
			else
				echo "Unchanged."
		fi
	else
		echo "No assets exists."
fi


cd $SVNPATH/trunk/

echo -n "Delete GitHub specific files and deployment script..."
rm -rf bin/
rm -rf tests/
rm -rf vendor/
rm -rf .phpcs.xml.dist
rm -rf .travis.yml
rm -rf phpunit.xml.dist
rm -rf readme.md
rm -rf .gitignore
rm -rf .git/
rm -rf phpcs.ruleset.xml
rm -rf composer.json
rm -rf composer.lock
echo "Done."

echo -n "Adding new files..."
SVNFILESTOADD=$(svn stat | grep "^?" | awk '{print $2}' | xargs)

if [ "$SVNFILESTOADD" != "" ]; then
    echo -n "Add files ${SVNFILESTOADD}"
    svn stat | grep "^?" | awk '{print $2}' | xargs svn add --quiet
fi
echo "Done."

echo -n "Removing old files..."
SVNFILESTOREMOVE=$(svn stat | grep "^\!" | awk '{print $2}' | xargs)

if [ "$SVNFILESTOREMOVE" != "" ]; then
    echo -n "Removing files ${SVNFILESTOREMOVE}"
    svn stat | grep "^\!" | awk '{print $2}' | xargs svn remove --quiet
fi
echo "Done."

echo -n "Enter a commit message for this new SVN version..."
#$DEFAULT_EDITOR /tmp/wppdcommitmsg.tmp
COMMITMSG=$(cat /tmp/wppdcommitmsg.tmp)
rm /tmp/wppdcommitmsg.tmp
echo "Done."

echo -n "Committing new SVN version..."
svn commit --quiet --no-auth-cache --no-auth-cache --username=$SVNUSER --password=$SVNPASS -m "$COMMITMSG"
echo "Done."

echo -n "Tagging and committing new SVN tag..."
svn copy $SVNURL/trunk $SVNURL/tags/$NEWVERSION1 --no-auth-cache --no-auth-cache --quiet --username=$SVNUSER --password=$SVNPASS -m "Tagging version $NEWVERSION1"
echo "Done."

echo -n "Removing temporary directory $SVNPATH..."
rm -rf "${SVNPATH:?}/"
echo "Done."

echo
echo "The plugin version $NEWVERSION1 is successfully deployed."
echo