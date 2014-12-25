#!/bin/bash -u
# Copyright (C) 2006-2013 Zmanda Incorporated.
# All Rights Reserved.
#
# The software you have just accessed, its contents, output and underlying
# programming code are the proprietary and confidential information of Zmanda
# Incorporated.  Only specially authorized employees, agents or licensees of
# Zmanda may access and use this software.  If you have not been given
# specific written permission by Zmanda, any attempt to access, use, decode,
# modify or otherwise tamper with this software will subject you to civil
# liability and\/or criminal prosecution to the fullest extent of the law.
#
# First step, in a long road to robust unit tests:

test_dumptypes=1
disklist=1
amanda=1
verbose=
verbose='-S'

###################

cat <<-EOD
Test Yasumi\'s ability to read and write zmc_dumptype, zmc_user_dumptype,
and a torture test disklist.conf file".  These files are read, parsed,
put into a YAML "parse tree" of sorts, then used to generate "new"
configuration files.  Ideally, the resulting files would not differ
from the original files.
EOD

cd `pwd`/${0%%/*}
mkdir -p /etc/amanda/zmc_test_quick
chown -R amandabackup:disk /etc/amanda/zmc_test_quick .
rm -f *.results/*
errors=0
echo > vimdiff.txt

if [ $test_dumptypes ]; then
	mkdir -p dumptypes.results dumptypes.expected
	echo '==>Testing dumptypes<=='
	infile=/etc/zmanda/zmc_aee/zmc_dumptypes
	outfile=/etc/amanda/zmc_test_quick/result.conf
	resultfile=dumptypes.results/zmc_dumptypes
	expectfile=dumptypes.expected/zmc_dumptypes
	wgetout=dumptypes.results/zmc_dumptypes.wget
	cp -p $infile /etc/amanda/zmc_test_quick/zmc_dumptypes.conf
	wget $verbose --user=rest --password=fc35838261057664efff5307e88c2d99 --no-check-certificate -O $wgetout 'https://localhost/Yasumi/index.php/conf/readwrite?amanda_configuration_name=zmc_test_quick&what=zmc_dumptypes.conf&where=result.conf&debug=8&user_name=admin&user_id=1&human=1&testing=1'
	if [ ! -e $outfile ]; then
		echo "FAILED: read/write test for '$infile' - no output to: $outfile (see wget output: $wgetout)"
		let "errors++"
	else
		mv $outfile $resultfile
		echo "vimdiff $resultfile $expectfile" >> vimdiff.txt
	fi
	infile=/etc/zmanda/zmc_aee/zmc_user_dumptypes
	wgetout=dumptypes.results/zmc_user_dumptypes.wget
	resultfile=dumptypes.results/zmc_user_dumptypes
	expectfile=dumptypes.expected/zmc_user_dumptypes
	cp -p $infile /etc/amanda/zmc_test_quick/zmc_user_dumptypes.conf
	wget $verbose --user=rest --password=fc35838261057664efff5307e88c2d99 --no-check-certificate -O $wgetout 'https://localhost/Yasumi/index.php/conf/readwrite?amanda_configuration_name=zmc_test_quick&what=zmc_user_dumptypes.conf&where=result.conf&debug=8&user_name=admin&user_id=1&human=1&testing=1'
	if [ ! -e $outfile ]; then
		echo "FAILED: read/write test for '$infile' - no output to: $outfile (see wget output: $wgetout)"
		let "errors++"
	else
		mv $outfile $resultfile
		echo "vimdiff $resultfile $expectfile" >> vimdiff.txt
	fi
fi

rm -f results/* > /dev/null
for dir in *.conf
do
	name=${dir%%.conf}
	[ ${!name} ] || continue
	mkdir -p $name.results $name.expected
	cd $dir
	for test in *.conf
	do
		echo "==>$dir/$test<=="
		cp -p "$test" /etc/amanda/zmc_test_quick/$dir
		wget $verbose --user=rest --password=fc35838261057664efff5307e88c2d99 --no-check-certificate -O ../$name.results/$test.wget 'https://localhost/Yasumi/index.php/conf/readwrite?amanda_configuration_name=zmc_test_quick&what='$dir'&debug=8&user_name=admin&user_id=1&human=1&where=test.conf&testing=1'
		outfile=/etc/amanda/zmc_test_quick/test.conf
		if [ ! -e $outfile ]; then
			echo "FAILED: $dir/$test - no output to: $outfile (see wget output: $name.results/$test.wget)"
			let "errors++"
			continue
		fi
		mv $outfile "../$name.results/$test"
		echo vimdiff "$name.results/$test" "${dir%%.conf}.expected/$test" >> ../vimdiff.txt
	done
	cd ..
done

echo "Number of tests which failed to yield output: $errors"
cat vimdiff.txt
