#! /usr/bin/env bash

str="$1"
valstr=(second minute hour day week)

case "$str" in
  ''|*[!0-9]* ) echo "seconds must be at least 0, not \"$str\"" && exit ;;
  * ) ;;
esac

vals=($((str%60)))  # secs
str=$((str/60))
vals+=($((str%60))) # mins
str=$((str/60))
vals+=($((str%24))) # hours
str=$((str/24))
vals+=($((str%7)))  # days
vals+=($((str/7)))  # weeks
str=""
for i in $(seq $((${#vals[@]}-1)) -1 0) ; do
  { [ "$i" -ne 0 ] || [ -n "$str" ]; } && [ "${vals[$i]}" -eq 0 ] && continue
  [ -n "$str" ] && str="${str}, "
  str="${str}${vals[$i]} ${valstr[$i]}"
  [ "${vals[$i]}" -ne 1 ] && str="${str}s"
done
echo "$str"

