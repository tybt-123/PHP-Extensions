#!/bin/bash

#Installing needed dependencies
sudo yum update -y
sudo yum install jq -y
sudo yum install mysql -y

#Getting region that EC2 instance is residing in
region=`curl -s http://169.254.169.254/latest/dynamic/instance-identity/document | jq .region -r`

sleep 1800

#Checking for completion status, 
status=`aws cloudformation describe-stacks --region "$region" --stack-name add-RDS --query "Stacks[0].StackStatus" --output text`
let num=0
while true
do
    status=`aws cloudformation describe-stacks --region "$region" --stack-name add-RDS --query "Stacks[0].StackStatus" --output text`
    if [ "$status" == "CREATE_COMPLETE" ]; then
        break
    fi
    sleep 180
    let num=num+1
    if [ $num -ge 15 ]
    then
        exit 1
    fi
done

#Getting the instance endpoint
endpoint="`aws cloudformation describe-stacks --region "$region" --stack-name add-RDS --query "Stacks[0].Outputs[?OutputKey=='DBEndpoint'].OutputValue" --output text`"

sudo su
service httpd stop
service crond stop
mysqldump -u "root" -p"wordpress-pass" wordpress > backup.sql
mysql -u "admin" -p"wordpress-pass" -h "$endpoint" -D wordpress < backup.sql

cd /var/www/html/
sed -i "s/localhost/$endpoint/" wp-config.php
service httpd start
service crond start
