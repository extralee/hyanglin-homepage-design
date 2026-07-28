#!/bin/bash

# 발급할 도메인 목록
domains="www.hyanglin.org hyanglin.org"
email="admin@hyanglin.org" # 실제 알림을 받을 이메일 주소

echo "### 1. Nginx 구동을 위한 임시 더미(Dummy) 인증서 생성"
docker compose run --rm --entrypoint "\
  sh -c 'mkdir -p /etc/letsencrypt/live/www.hyanglin.org && \
  openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
    -keyout /etc/letsencrypt/live/www.hyanglin.org/privkey.pem \
    -out /etc/letsencrypt/live/www.hyanglin.org/fullchain.pem -subj \"/CN=localhost\"'" certbot

echo "### 2. Nginx 컨테이너 실행"
docker compose up -d nginx

echo "### 3. Nginx 헬스체크 및 대기 (3초)"
sleep 3

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
