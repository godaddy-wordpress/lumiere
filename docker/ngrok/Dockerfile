FROM alpine

RUN apk add --no-cache bash unzip

WORKDIR /tmp/ngrok

RUN wget https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-386.zip
RUN unzip ngrok-*.zip
RUN rm ngrok-*.zip
RUN mv ngrok /usr/local/bin/

WORKDIR /usr/local/bin

ADD docker-entrypoint.sh /usr/local/bin/

RUN ["chmod", "+x", "/usr/local/bin/docker-entrypoint.sh"]

ENTRYPOINT ["docker-entrypoint.sh"]

CMD [ "start" ]
