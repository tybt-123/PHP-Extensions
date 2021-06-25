#!/bin/bash

#Installing needed dependencies
sudo yum update -y
sudo yum install jq -y
sudo yum install mysql -y

#Getting region that EC2 instance is residing in
region=`curl -s http://169.254.169.254/latest/dynamic/instance-identity/document | jq .region -r`

#Checking for completion status
status=`aws cloudformation describe-stacks --region "$region" --stack-name add-RDS --query "Stacks[0].StackStatus" --output text`
while [ "$status" != "CREATE_COMPLETE" ]
do
    status=`aws cloudformation describe-stacks --region "$region" --stack-name add-RDS --query "Stacks[0].StackStatus" --output text`
    sleep 180
done

#Getting the instance endpoint
endpoint=`aws cloudformation describe-stacks --region "$region" --stack-name add-RDS --query "Stacks[0].Outputs[?OutputKey=='DBEndpoint'].OutputValue" --output text`

sudo su
service httpd stop
mysqldump -u "root" -p"wordpress-pass" wordpress > backup.sql
mysql -u "admin" -p"wordpress-pass" -h "$endpoint" -D wordpress < backup.sql

cd /var/www/html/
configFile="wp-config.php"
originalString="localhost"
sed -i "s/$originalString/$endpoint/" $configFile
service httpd start
systemctl enable httpd
