Hack of a job to write a TXT record with the timestamp it was created, then publish zone. This is done by the PHP script.
The Python scripts start a loop with a 1/2 second timer and wait for that TXT record to change. When it does, it measures the delay.
The results are pushed to graphite using statsd.

python-statsd and php-statsd are required for these to work.

I used this crontab to manage things
m h  dom mon dow   command
*/1 * * * * /usr/bin/php /home/pi/dnsmon/dnstest.php >>/home/pi/dnsmon/logs/dnsapi.log
@reboot /usr/bin/python /home/pi/dnsmon/dnsedge-ns1.py >>/home/pi/dnsmon/logs/dnsedge-ns1.log
@reboot /usr/bin/python /home/pi/dnsmon/dnsedge-ns2.py >>/home/pi/dnsmon/logs/dnsedge-ns2.log
@reboot /usr/bin/python /home/pi/dnsmon/dnsedge-ns3.py >>/home/pi/dnsmon/logs/dnsedge-ns3.log
@reboot /usr/bin/python /home/pi/dnsmon/dnsedge-ns4.py >>/home/pi/dnsmon/logs/dnsedge-ns4.log
