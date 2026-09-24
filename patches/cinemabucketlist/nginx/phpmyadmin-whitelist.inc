# phpMyAdmin 접근 허용 IP 화이트리스트
# 이 파일만 수정하면 모든 사이트의 phpMyAdmin 접근 IP를 관리할 수 있습니다.
# 가비아 방화벽에 IP를 추가한 뒤, 이 파일에도 동일하게 추가하세요.
#
# 적용 대상: cinemabucketlist.conf (phpMyAdmin location 블록)
# *.conf 와일드카드 자동 로딩 방지를 위해 확장자를 .inc로 유지해야 합니다.
# 수정 후 Nginx 리로드 필요:
#   sudo docker exec hyanglin-home-infra-nginx-1 nginx -s reload

allow 211.177.80.14;   # mountain 머신 (주 개발/관리 PC)
allow 58.233.222.210;  # 추가 허용 IP
allow 127.0.0.1;       # 서버 로컬
deny all;
