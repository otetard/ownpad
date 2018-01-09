FROM ubuntu

RUN apt-get update
RUN apt-get install -y gzip git curl python libssl-dev pkg-config build-essential
RUN apt-get install -y nodejs npm
RUN ln -s /usr/bin/nodejs /usr/bin/node
RUN git clone git://github.com/ether/etherpad-lite.git

COPY APIKEY.txt /etherpad-lite/

EXPOSE 9001

ENTRYPOINT ["/etherpad-lite/bin/run.sh", "--root"]
