# This is for working on the RPM build
#
# Run this image:
#   docker build -t res-search-service .
#

FROM centos:7
MAINTAINER Elliot Smith <elliot.smith@bbc.co.uk>

EXPOSE 80

RUN yum update -y

RUN yum install -y httpd
