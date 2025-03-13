#! /usr/bin/env bash

# Adapted from comments at gist.github.com/stephenhardy/5470814

if ! [ -d "${1}" ] || [ -z "${1}" ] ; then
  echo "\"${1}\" is not a valid directory name"
  exit 0
fi

read -r -p "Really make \"${1}\" the new head with no history? [y/n] " confirm

case ${confirm} in
  [yY] )
    echo "${1}" ;;
  [yY][eE][sS] )
    echo "${1}" ;;
  * )
    echo "Not confirmed, not making changes . . ." && exit
esac

cd "$1" || exit
# New orphan branch
git checkout --orphan newmaster
# Add everything
git add -A
# Delete master
git branch -D master
# Move to master
git branch -m master
# Commit
git commit -m "Initial release"
# Force push
git push -f --set-upstream origin master
# Prune
git gc --aggressive --prune=all
