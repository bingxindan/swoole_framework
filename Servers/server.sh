#!/bin/sh
basepath=$(cd `dirname $0`; pwd)
cd $basepath
filePath="$basepath/Http/Server.php"
env=`cat $basepath/../.envkey`
projectName=`cat $basepath/../Conf/$env/conf.ini | grep "projectName=" | awk -F "=" '{print $2}'`
flag=''

case $env in
    local )
        phpBin='/usr/local/php7/bin/php'
        ;;
    dev )
        flag=`pwd|awk -F\/ '{print $3}'|awk -Fk '{print $2}'`
        phpBin='/home/service/php7/bin/php'
        ;;
    beta )
        flag=`pwd|awk -F\/ '{print $3}'|awk -Fk '{print $2}'`
        phpBin='/home/service/php7/bin/php'
        ;;
    gray )
        flag=`pwd|awk -F\/ '{print $3}'|awk -Fk '{print $2}'`
        phpBin='/home/service/php7/bin/php'
        ;;
    prod )
        flag=`pwd|awk -F\/ '{print $3}'|awk -Fk '{print $2}'`
        phpBin='/home/service/php7/bin/php'
        ;;
    * )
        phpBin='/home/service/php7/bin/php'
        ;;
esac

masterTitle="$projectName:Master $flag"
masterPid=`ps -ef | grep "$masterTitle" | grep -v "grep" | awk '{print $2}'`

start () {
	if [ -z "$masterPid" ]; then
            echo "Starting : begin"
            # ulimit -c unlimited
            $phpBin $filePath
            echo "Starting : finish"
	else
            echo "Starting : running, $masterPid"
	fi
}

stop () {
	if [ -z "$masterPid" ]; then
            echo "Stopping : no master"
	else
            echo "Stopping : begin"
            kill -TERM $masterPid
            echo "Stopping : finish"
            masterPid=''
	fi
}

reload () {
	echo "Reloading : begin"
	kill -USR1 $masterPid
        sleep 1
	kill -USR2 $masterPid
	echo "Reloading : finish"
}

monitor () {
	echo "Monitor : begin"
	if [ -z "$masterPid" ]; then
            start
	fi
	echo "Monitor : finish"
}

case "$1" in
  start)
	start
	;;
  stop)
	stop
	;;
  restart)
	stop
	    sleep 0.5
	start
	;;
  reload)
	reload
	;;
  monitor)
	;;
  *)
	echo $"Usage: $0 {start|stop|restart|reload|monitor}"
    ;;
esac
