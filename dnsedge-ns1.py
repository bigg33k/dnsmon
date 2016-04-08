#!/usr/bin/python
from datetime import datetime
import time
import subprocess
import shlex
import socket
import os
import statsd

os.nice(20)

CARBON_SERVER = 'graphite.bigg33k.net'
CARBON_PORT = 2003

mydnsrecord="test.geo.bigg33k.net"
myrecordtype="txt"
mytarget="ns1.p29.dynect.net"
mystatsd="NS1"

oldrec=0
newrec=0
intrec=0
noop=0


stats = statsd.Connection.set_defaults(host='graphite.bigg33k.net')
	
while (1):
	proptimer = statsd.Timer('dnsprop')
	proptimer.start()
	
	while (intrec == oldrec):
		time.sleep(0.05)
		newrec = subprocess.Popen(['dig', myrecordtype, mydnsrecord, '+short', mytarget], stdout=subprocess.PIPE).communicate()[0]

                try:
                	intrec=long(newrec.strip('"' '\n'));
			#print record
                except ValueError:
                        noop
                        #print record

			
	proptimer.stop(mystatsd)
	
	oldrec = intrec;	
	print oldrec
	
	now = time.time();
	delay = float(now)-float(intrec);
	print "REC Changed in " + str(delay)
	message = 'dnsapi.prop.%s %.2f  %d\n' % (mystatsd.lower(),delay, time.time()) 
	print (message)
	
	#send to graphite
	sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
	sock.connect((CARBON_SERVER,CARBON_PORT))
	sock.send(message)
	sock.close()
	
