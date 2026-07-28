#!/bin/bash

# 발급할 도메인 목록
domains="www.hyanglin.org hyanglin.org"
email="your-email@example.com" # 실제 사용하는 이메일로 변경 필수

echo "### 1. Nginx 시작을 위해 임시 더미(Dummy) 인증서 생성 및 설정 다운로드"
docker compose run --rm --entrypoint "\
  sh -c 'mkdir -p /etc/letsencrypt/live/www.hyanglin.org && \
  openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
    -keyout /etc/letsencrypt/live/www.hyanglin.org/privkey.pem \
    -out /etc/letsencrypt/live/www.hyanglin.org/fullchain.pem -subj \"/CN=localhost\" && \
  wget -O /etc/letsencrypt/options-ssl-nginx.conf https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf && \
  wget -O /etc/letsencrypt/ssl-dhparams.pem https://raw.githubusercontent.com/certbot/certbot/master/certbot/certbot/ssl-dhparams.pem'" certbot

echo "### 2. Nginx 컨테이너 백그라운드 실행"
docker compose up --force-recreate -d nginx

echo "### 3. 기존 더미 인증서 삭제"
docker compose run --rm --entrypoint "\
  rm -Rf /etc/letsencrypt/live/www.hyanglin.org && \
  rm -Rf /etc/letsencrypt/archive/www.hyanglin.org && \
  rm -Rf /etc/letsencrypt/renewal/www.hyanglin.org.conf" certbot

echo "### 4. 실제 Let's Encrypt 인증서 발급 시도"
domain_args=""
for domain in $domains; do
  domain_args="$domain_args -d $domain"
done

# ACME 챌린지를 통과하기 위해 --webroot 방식을 사용합니다.
docker compose run --rm --entrypoint "\
  certbot certonly --webroot -w /var/www/certbot \
    $domain_args \
    --email $email \
    --rsa-key-size 4096 \
    --agree-tos \
    --force-renewal \
    --non-interactive" certbot

echo "### 5. 인증서 적용을 위해 Nginx 리로드"
docker compose exec nginx nginx -s reload

echo "모든 과정이 완료되었습니다. https://www.hyanglin.org 로 접속을 확인하세요."
