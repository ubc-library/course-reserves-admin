Library Online Course Reserves - Web
-------------------------------------

![Build Status](https://proxy-01.library.ubc.ca/build-status/locr--cr-staff)

*/buildfile can be safely ignored, it is used to defined files needed by jenkins to build the project for multiple environments*

Production Deployment
----------------------

- Trigger build in Jenkins
- SFTP the post-build prod folder to the prod server
- Swap folders


Development in Docker - Quick Start to App
--------------------------------------------

## Build The Image
- cd to the root folder of the application: `cd CODE_ROOT`
- build the docker image, lets name it 'cr-staff' and tag it 'v1.5.0' `sudo docker build -t cr-staff:v1.5.0 .`
- this image is now available on the server, `sudo docker images`

## Run a Named Instance
- for this step, you need to know which instance you want to run (e.g. dev-cr-staff, or dev-rm-cr-staff)
- reference the file resources/docker-host/nginx/nginx.conf for a listing of expected instances, and the port it is bound to, for this example, we are using `dev-cr-staff`, which is bound to `8090`
- run your container `sudo docker run -d --name dev-cr-staff -p 127.0.0.1:8090:80 -it cr-staff:v1.5.0`
- the --name, set with the flag `--name dev-cr-staff` can be used to reference this container in all subsequent commands, as seen below
- shell into the container to access applications `sudo docker exec -it dev-cr-staff /bin/bash`

## Start cr-staff
- shell into the container to access applications `sudo docker exec -it dev-cr-staff /bin/bash`
- start php `service php5-fpm restart`
- change to code folder `cd /usr/local/cr-staff`
- install php dependencies via composer `composer update`
- change your server name to the correct site (temporary fix) `vim /etc/nginx/sites-available/cr-staff`
- start the web server, `service nginx restart`

## Maintenance - make your life easier
- on the host, `sudo docker inspect dev-cr-staff`
- look for the `Mounts` entry, these are volumes in the container that are accessible on the host machine
- we typically define two mounts in the Dockerfile, the application logs (`Destination: /var/log/cr-staff`), and the nginx logs (`Destination: /var/log/nginx`)
- each entry states where on the host machine the container folder can be accessed, e.g. `cd /var/lib/docker/volumes/3779b352c1da6b0c09f0752f3b/_data` to access the container volume `/var/logs/nginx`
- create a folder, if not exists, in the host `/usr/local/docker-instances`, for this named instance, `mkdir /usr/local/docker-instances/dev-cr-staff`
- for each mount, create a symlink in this folder, e.g. `ln -s /var/lib/docker/volumes/3779b352c1da6b0c09f0752f3b/_data /usr/local/docker-instances/dev-cr-staff/nginx-logs`
- you can now access these logs in a more memorable format, e.g., `tail -f -n 600 /usr/local/docker-instances/dev-cr-staff/nginx-logs/error.log`, rather than the unmemorable docker path

## Cleanup
- get list of containers `sudo docker ps -a`
- stop the container that you just started `sudo docker stop CONTAINER_ID`
- using our example in startup, stop by `sudo docker stop dev-cr-staff`
- remove all stopped containers `sudo docker rm $(sudo docker ps -a -q)`
- list docker images `sudo docker images`
- remove any unused images `sudo docker rmi IMAGE_ID` OR `sudo docker rmi IMAGE_NAME:TAG` (if no tag, defaults to 'latest' tag, so IMAGE_NAME:latest)
